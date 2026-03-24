<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\BusinessRuleException;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\Store;
use App\Repositories\Billing\InvoiceRepository;
use App\Services\Accounting\JournalService;
use App\Services\AuditService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * InvoiceService — orchestrates the complete billing workflow.
 *
 * Billing Workflow:
 *  1. Validate customer + cart
 *  2. calculate_gst per line item
 *  3. apply_discount
 *  4. create_invoice (DRAFT)
 *  5. inventory.agent → update_stock (deduct)
 *  6. confirm invoice → CONFIRMED (immutable)
 *  7. accounting.agent → post_journal (Sales entry)
 *  8. process_payment
 */
class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly GstService $gstService,
        private readonly StockService $stockService,
        private readonly JournalService $journalService,
        private readonly PaymentService $paymentService,
        private readonly AuditService $audit,
    ) {}

    /**
     * Create a draft invoice with full GST computation per line item.
     */
    public function createDraft(array $data): Invoice
    {
        $store    = Store::findOrFail($data['store_id']);
        $customer = isset($data['customer_id']) ? Customer::findOrFail($data['customer_id']) : null;

        $supplyType = $customer
            ? $customer->getSupplyType($store)
            : 'INTRA';

        $period = FiscalPeriod::where('status', 'OPEN')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->firstOrFail();

        $lineItems = $this->buildLineItems($data['line_items'], $supplyType);
        $totals    = $this->computeTotals($lineItems);

        $invoiceNumber = $this->repository->generateInvoiceNumber($store->id);

        $invoice = $this->repository->createWithLineItems(
            [
                'invoice_number'      => $invoiceNumber,
                'store_id'            => $store->id,
                'customer_id'         => $customer?->id,
                'fiscal_period_id'    => $period->id,
                'invoice_date'        => now()->toDateString(),
                'due_date'            => $data['due_date'] ?? null,
                'subtotal'            => $totals['subtotal'],
                'total_discount'      => $totals['total_discount'],
                'taxable_amount'      => $totals['taxable_amount'],
                'cgst_total'          => $totals['cgst_total'],
                'sgst_total'          => $totals['sgst_total'],
                'igst_total'          => $totals['igst_total'],
                'grand_total'         => $totals['grand_total'],
                'outstanding_balance' => $totals['grand_total'],
                'supply_type'         => $supplyType,
                'status'              => 'DRAFT',
                'payment_mode'        => $data['payment_mode'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => Auth::id(),
            ],
            $lineItems
        );

        $this->audit->log('CREATE_INVOICE_DRAFT', 'Invoice', $invoice->id,
            null, ['invoice_number' => $invoiceNumber, 'status' => 'DRAFT'],
            storeId: $store->id
        );

        return $invoice;
    }

    /**
     * Confirm a draft invoice:
     * 1. Validate and deduct stock
     * 2. Post sales journal entry
     * 3. Lock invoice as CONFIRMED
     */
    public function confirm(string $invoiceId): Invoice
    {
        $invoice = $this->repository->findByIdOrFail($invoiceId);

        if (! $invoice->isDraft()) {
            throw new BusinessRuleException(
                "Invoice {$invoice->invoice_number} cannot be confirmed — status is {$invoice->status}.",
                'INVALID_STATUS_TRANSITION'
            );
        }

        return DB::transaction(function () use ($invoice) {
            // Step 1: Deduct stock for all line items
            foreach ($invoice->lineItems as $lineItem) {
                $this->stockService->deductStock(
                    productId: $lineItem->product_id,
                    storeId: $invoice->store_id,
                    quantity: (float) $lineItem->quantity,
                    referenceId: $invoice->id,
                    movedBy: Auth::id()
                );
            }

            // Step 2: Post journal entry — Sales Dr / Tax Payable Cr / Inventory Cr
            $period = FiscalPeriod::findOrFail($invoice->fiscal_period_id);
            $journalEntry = $this->journalService->postJournal(
                [
                    'fiscal_period_id' => $period->id,
                    'store_id'         => $invoice->store_id,
                    'entry_date'       => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                    'reference_type'   => 'INVOICE',
                    'reference_id'     => $invoice->id,
                    'narration'        => "Sales: {$invoice->invoice_number}",
                ],
                $this->buildSalesJournalLines($invoice)
            );

            // Step 3: Confirm invoice (immutable)
            $confirmed = $this->repository->confirm($invoice, $journalEntry->id);

            $this->audit->log('CONFIRM_INVOICE', 'Invoice', $invoice->id,
                ['status' => 'DRAFT'],
                ['status' => 'CONFIRMED', 'journal_entry_id' => $journalEntry->id],
                storeId: $invoice->store_id
            );

            return $confirmed;
        });
    }

    /**
     * Cancel a confirmed (unpaid) invoice — creates credit note + reversal journal.
     */
    public function cancel(string $invoiceId, string $reason): Invoice
    {
        $invoice = $this->repository->findByIdOrFail($invoiceId);

        if (! $invoice->isConfirmed()) {
            throw new BusinessRuleException(
                "Only CONFIRMED invoices can be cancelled. Current status: {$invoice->status}.",
                'INVALID_STATUS_TRANSITION'
            );
        }

        return DB::transaction(function () use ($invoice, $reason) {
            // Reverse journal entry
            $this->journalService->reverseJournal($invoice->journal_entry_id, $reason);

            // Restore stock
            foreach ($invoice->lineItems as $lineItem) {
                $this->stockService->restoreStock(
                    productId: $lineItem->product_id,
                    storeId: $invoice->store_id,
                    quantity: (float) $lineItem->quantity,
                    referenceId: $invoice->id,
                    movedBy: Auth::id()
                );
            }

            // Cancel invoice
            $invoice->update(['status' => 'CANCELLED']);

            $this->audit->log('CANCEL_INVOICE', 'Invoice', $invoice->id,
                ['status' => 'CONFIRMED'],
                ['status' => 'CANCELLED', 'reason' => $reason],
                storeId: $invoice->store_id
            );

            return $invoice->fresh();
        });
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildLineItems(array $rawItems, string $supplyType): array
    {
        $lineItems = [];

        foreach ($rawItems as $item) {
            $basePrice = (float) $item['base_price'];
            $discount  = (float) ($item['discount_amount'] ?? 0);
            $taxable   = $basePrice - $discount;

            $gst = $this->gstService->calculate(
                taxableAmount: $taxable,
                gstRate: (float) $item['gst_rate'],
                supplyType: $supplyType,
                hsnCode: $item['hsn_code']
            );

            $lineItems[] = [
                'product_id'            => $item['product_id'],
                'product_name'          => $item['product_name'],
                'hsn_code'              => $item['hsn_code'],
                'quantity'              => $item['quantity'] ?? 1,
                'gross_weight_grams'    => $item['gross_weight_grams'] ?? null,
                'net_weight_grams'      => $item['net_weight_grams'] ?? null,
                'unit_price'            => $item['unit_price'] ?? $basePrice,
                'making_charges'        => $item['making_charges'] ?? 0,
                'wastage_amount'        => $item['wastage_amount'] ?? 0,
                'hallmark_charge'       => $item['hallmark_charge'] ?? 0,
                'base_price'            => $basePrice,
                'discount_amount'       => $discount,
                'taxable_amount'        => $taxable,
                'gst_rate'              => $item['gst_rate'],
                'cgst_rate'             => $gst['cgst_rate'],
                'sgst_rate'             => $gst['sgst_rate'],
                'igst_rate'             => $gst['igst_rate'],
                'cgst_amount'           => $gst['cgst_amount'],
                'sgst_amount'           => $gst['sgst_amount'],
                'igst_amount'           => $gst['igst_amount'],
                'total_tax'             => $gst['total_tax'],
                'line_total'            => $gst['total_with_tax'],
                'metal_rate_per_gram'   => $item['metal_rate_per_gram'] ?? null,
                'metal_rate_timestamp'  => $item['metal_rate_timestamp'] ?? null,
            ];
        }

        return $lineItems;
    }

    private function computeTotals(array $lineItems): array
    {
        $subtotal      = array_sum(array_column($lineItems, 'base_price'));
        $totalDiscount = array_sum(array_column($lineItems, 'discount_amount'));
        $taxableAmount = array_sum(array_column($lineItems, 'taxable_amount'));
        $cgstTotal     = array_sum(array_column($lineItems, 'cgst_amount'));
        $sgstTotal     = array_sum(array_column($lineItems, 'sgst_amount'));
        $igstTotal     = array_sum(array_column($lineItems, 'igst_amount'));
        $grandTotal    = array_sum(array_column($lineItems, 'line_total'));

        return compact('subtotal', 'totalDiscount', 'taxableAmount', 'cgstTotal', 'sgstTotal', 'igstTotal', 'grandTotal');
    }

    private function buildSalesJournalLines(Invoice $invoice): array
    {
        $lines = [];

        // DR Accounts Receivable / Cash (full invoice amount)
        $arAccount = $invoice->payment_mode === 'CASH'
            ? ChartOfAccount::CASH_IN_HAND
            : ChartOfAccount::ACCOUNTS_RECEIVABLE;

        $lines[] = [
            'account_code' => $arAccount,
            'debit_amount' => (float) $invoice->grand_total,
            'credit_amount'=> 0,
            'description'  => "Invoice {$invoice->invoice_number}",
        ];

        // CR Sales Revenue (taxable amount)
        $lines[] = [
            'account_code' => ChartOfAccount::SALES_REVENUE,
            'debit_amount' => 0,
            'credit_amount'=> (float) $invoice->taxable_amount,
            'description'  => "Sales Revenue — {$invoice->invoice_number}",
        ];

        // CR CGST / SGST / IGST Payable
        if ((float) $invoice->cgst_total > 0) {
            $lines[] = ['account_code' => ChartOfAccount::CGST_PAYABLE, 'debit_amount' => 0, 'credit_amount' => (float) $invoice->cgst_total, 'description' => 'CGST Payable'];
            $lines[] = ['account_code' => ChartOfAccount::SGST_PAYABLE, 'debit_amount' => 0, 'credit_amount' => (float) $invoice->sgst_total, 'description' => 'SGST Payable'];
        }

        if ((float) $invoice->igst_total > 0) {
            $lines[] = ['account_code' => ChartOfAccount::IGST_PAYABLE, 'debit_amount' => 0, 'credit_amount' => (float) $invoice->igst_total, 'description' => 'IGST Payable'];
        }

        return $lines;
    }
}
