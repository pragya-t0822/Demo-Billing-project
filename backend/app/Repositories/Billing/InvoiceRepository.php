<?php

declare(strict_types=1);

namespace App\Repositories\Billing;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class InvoiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Invoice());
    }

    public function generateInvoiceNumber(string $storeId): string
    {
        return DB::transaction(function () use ($storeId) {
            // Store-specific sequential number within fiscal year (Apr–Mar)
            $fiscalYear = now()->month >= 4 ? now()->year : now()->year - 1;
            $prefix     = 'INV';

            $last = DB::table('invoices')
                ->where('store_id', $storeId)
                ->where('invoice_number', 'like', "{$prefix}-{$fiscalYear}-%")
                ->orderByDesc('invoice_number')
                ->lockForUpdate()
                ->value('invoice_number');

            $nextSeq = $last ? (int) substr($last, -5) + 1 : 1;

            return sprintf('%s-%d-%05d', $prefix, $fiscalYear, $nextSeq);
        });
    }

    public function createWithLineItems(array $invoiceData, array $lineItems): Invoice
    {
        return DB::transaction(function () use ($invoiceData, $lineItems) {
            /** @var Invoice $invoice */
            $invoice = Invoice::create($invoiceData);

            foreach ($lineItems as $item) {
                InvoiceLineItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }

            return $invoice->load('lineItems');
        });
    }

    public function confirm(Invoice $invoice, string $journalEntryId): Invoice
    {
        $invoice->update([
            'status'           => 'CONFIRMED',
            'journal_entry_id' => $journalEntryId,
        ]);

        /** @var Invoice $fresh */
        $fresh = $invoice->fresh() ?? $invoice;
        return $fresh->load('lineItems');
    }

    public function updatePayment(Invoice $invoice, float $amountPaid): Invoice
    {
        $newAmountPaid  = (float) $invoice->amount_paid + $amountPaid;
        $outstanding    = (float) $invoice->grand_total - $newAmountPaid;
        $status         = $outstanding <= 0.005 ? 'PAID' : 'PARTIAL';

        $invoice->update([
            'amount_paid'         => $newAmountPaid,
            'outstanding_balance' => max(0, $outstanding),
            'status'              => $status,
        ]);

        return $invoice->fresh();
    }
}
