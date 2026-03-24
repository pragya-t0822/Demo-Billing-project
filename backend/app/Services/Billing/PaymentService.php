<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\BusinessRuleException;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\Billing\InvoiceRepository;
use App\Services\Accounting\JournalService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly JournalService $journalService,
        private readonly AuditService $audit,
    ) {}

    /**
     * Record a payment against an invoice.
     * ALWAYS posts a journal entry — never records payment without accounting entry.
     */
    public function processPayment(string $invoiceId, array $data): Payment
    {
        $invoice = $this->invoiceRepository->findByIdOrFail($invoiceId);

        if (! in_array($invoice->status, ['CONFIRMED', 'PARTIAL'])) {
            throw new BusinessRuleException(
                "Cannot record payment for invoice in status: {$invoice->status}",
                'INVALID_PAYMENT_STATUS'
            );
        }

        $amountPaid = (float) $data['amount_paid'];

        if ($amountPaid <= 0) {
            throw new BusinessRuleException('Payment amount must be greater than zero.', 'INVALID_AMOUNT');
        }

        $outstanding = (float) $invoice->outstanding_balance;
        if ($amountPaid > $outstanding + 0.005) {
            throw new BusinessRuleException(
                "Payment amount ₹{$amountPaid} exceeds outstanding balance ₹{$outstanding}.",
                'OVERPAYMENT_NOT_ALLOWED'
            );
        }

        return DB::transaction(function () use ($invoice, $data, $amountPaid) {
            // Generate payment number
            $paymentNumber = $this->generatePaymentNumber();

            // Post journal entry for payment receipt
            $journalEntry = $this->journalService->postJournal(
                [
                    'fiscal_period_id' => $invoice->fiscal_period_id,
                    'store_id'         => $invoice->store_id,
                    'entry_date'       => $data['payment_date'] ?? now()->toDateString(),
                    'reference_type'   => 'PAYMENT',
                    'reference_id'     => $invoice->id,
                    'narration'        => "Payment received: {$invoice->invoice_number} — {$paymentNumber}",
                ],
                $this->buildPaymentJournalLines($invoice, $data['payment_mode'], $amountPaid)
            );

            // Record payment
            /** @var \App\Models\Payment $payment */
            $payment = Payment::create([
                'payment_number'        => $paymentNumber,
                'invoice_id'            => $invoice->id,
                'store_id'              => $invoice->store_id,
                'customer_id'           => $invoice->customer_id,
                'payment_mode'          => $data['payment_mode'],
                'amount_paid'           => $amountPaid,
                'gateway_transaction_id'=> $data['gateway_transaction_id'] ?? null,
                'cheque_number'         => $data['cheque_number'] ?? null,
                'bank_reference'        => $data['bank_reference'] ?? null,
                'status'                => 'CONFIRMED',
                'journal_entry_id'      => $journalEntry->id,
                'recorded_by'           => Auth::id(),
                'payment_date'          => $data['payment_date'] ?? now()->toDateString(),
            ]);

            // Update invoice outstanding balance
            $updatedInvoice = $this->invoiceRepository->updatePayment($invoice, $amountPaid);

            $this->audit->log('PROCESS_PAYMENT', 'Payment', $payment->id,
                null,
                [
                    'payment_number'     => $paymentNumber,
                    'amount'             => $amountPaid,
                    'invoice_status'     => $updatedInvoice->status,
                    'journal_entry_id'   => $journalEntry->id,
                ],
                storeId: $invoice->store_id
            );

            return $payment;
        });
    }

    private function buildPaymentJournalLines(Invoice $invoice, string $paymentMode, float $amount): array
    {
        // Digital payments go to Gateway Clearing (not directly to bank)
        $debitAccount = in_array($paymentMode, ['CARD', 'UPI', 'NETBANKING'])
            ? ChartOfAccount::GATEWAY_CLEARING
            : ChartOfAccount::CASH_IN_HAND;

        return [
            [
                'account_code' => $debitAccount,
                'debit_amount' => $amount,
                'credit_amount'=> 0,
                'description'  => "Payment received — {$paymentMode}",
            ],
            [
                'account_code' => ChartOfAccount::ACCOUNTS_RECEIVABLE,
                'debit_amount' => 0,
                'credit_amount'=> $amount,
                'description'  => "AR cleared — {$invoice->invoice_number}",
            ],
        ];
    }

    private function generatePaymentNumber(): string
    {
        // Must be called inside an active DB::transaction (enforced by processPayment)
        $year = now()->year;
        $last = DB::table('payments')
            ->where('payment_number', 'like', "PAY-{$year}-%")
            ->orderByDesc('payment_number')
            ->lockForUpdate()
            ->value('payment_number');

        $nextSeq = $last ? (int) substr($last, -5) + 1 : 1;

        return sprintf('PAY-%d-%05d', $year, $nextSeq);
    }
}
