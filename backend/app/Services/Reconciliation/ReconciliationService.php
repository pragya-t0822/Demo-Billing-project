<?php

declare(strict_types=1);

namespace App\Services\Reconciliation;

use App\Models\BankEntry;
use App\Models\BankStatement;
use App\Models\GatewaySettlement;
use App\Models\Payment;
use App\Models\ReconciliationMatch;
use App\Models\ChartOfAccount;
use App\Services\Accounting\JournalService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    // Amount tolerance for fuzzy matching (₹0.50)
    const AMOUNT_TOLERANCE = 0.50;
    const DATE_WINDOW_DAYS = 2;

    public function __construct(
        private readonly JournalService $journalService,
        private readonly AuditService $audit,
    ) {}

    /**
     * Run auto-matching for a bank statement.
     * Priority: exact reference → amount+date+digits → amount±tolerance
     */
    public function autoMatch(string $bankStatementId, ?float $tolerance = null, ?int $dateWindow = null): array
    {
        /** @var BankStatement $statement */
        $statement   = BankStatement::findOrFail($bankStatementId);
        $tolerance   = $tolerance ?? self::AMOUNT_TOLERANCE;
        $dateWindow  = $dateWindow ?? self::DATE_WINDOW_DAYS;

        /** @var \Illuminate\Database\Eloquent\Collection<int, BankEntry> $unmatchedEntries */
        $unmatchedEntries = BankEntry::where('bank_statement_id', $bankStatementId)
            ->where('status', 'PENDING')
            ->get();

        $matched   = [];
        $unmatched = [];

        foreach ($unmatchedEntries as $entry) {
            /** @var BankEntry $entry */
            $result = $this->matchEntry($entry, $tolerance, $dateWindow);

            if ($result) {
                /** @var \App\Models\ReconciliationMatch $match */
                $match = ReconciliationMatch::create([
                    'bank_entry_id'     => $entry->id,
                    'system_payment_id' => $result['payment_id'],
                    'match_confidence'  => $result['confidence'],
                    'match_criteria'    => $result['criteria'],
                    'status'            => 'MATCHED',
                ]);

                $entry->update(['status' => 'MATCHED']);

                $matched[] = [
                    'bank_entry_id'     => $entry->id,
                    'system_payment_id' => $result['payment_id'],
                    'match_confidence'  => $result['confidence'],
                    'match_criteria'    => $result['criteria'],
                ];
            } else {
                $unmatched[] = [
                    'bank_entry_id' => $entry->id,
                    'amount'        => $entry->credit_amount ?: $entry->debit_amount,
                    'date'          => $entry->entry_date,
                    'narration'     => $entry->narration,
                ];
            }
        }

        return [
            'reconciliation_run_id' => uniqid('RR-'),
            'matched'               => $matched,
            'unmatched_count'       => count($unmatched),
            'unmatched'             => $unmatched,
            'summary' => [
                'total_entries' => $unmatchedEntries->count(),
                'matched_count' => count($matched),
                'unmatched_count'=> count($unmatched),
            ],
        ];
    }

    private function matchEntry(BankEntry $entry, float $tolerance, int $dateWindow): ?array
    {
        $amount = (float) ($entry->credit_amount > 0 ? $entry->credit_amount : $entry->debit_amount);

        // Priority 1: exact amount + reference number
        if ($entry->reference_number) {
            /** @var Payment|null $payment */
            $payment = Payment::where('gateway_transaction_id', $entry->reference_number)
                ->orWhere('bank_reference', $entry->reference_number)
                ->first();

            if ($payment && abs((float) $payment->amount_paid - $amount) < 0.005) {
                return ['payment_id' => $payment->id, 'confidence' => 'HIGH', 'criteria' => ['amount', 'reference_id']];
            }
        }

        // Priority 2: exact amount + date window
        // Use copy() to avoid mutating the original Carbon instance
        $windowStart = $entry->entry_date->copy()->subDays($dateWindow)->toDateString();
        $windowEnd   = $entry->entry_date->copy()->addDays($dateWindow)->toDateString();

        /** @var Payment|null $payment */
        $payment = Payment::where('amount_paid', $amount)
            ->whereBetween('payment_date', [$windowStart, $windowEnd])
            ->first();

        if ($payment) {
            return ['payment_id' => $payment->id, 'confidence' => 'MEDIUM', 'criteria' => ['amount', 'date_window']];
        }

        // Priority 3: amount ± tolerance + date window
        /** @var Payment|null $payment */
        $payment = Payment::whereBetween('amount_paid', [$amount - $tolerance, $amount + $tolerance])
            ->whereBetween('payment_date', [$windowStart, $windowEnd])
            ->first();

        if ($payment) {
            return ['payment_id' => $payment->id, 'confidence' => 'LOW', 'criteria' => ['amount_tolerance', 'date_window']];
        }

        return null;
    }

    /**
     * Process a gateway settlement — post journal entry and verify clearing account.
     */
    public function processSettlement(string $settlementId): GatewaySettlement
    {
        /** @var GatewaySettlement $settlement */
        $settlement = GatewaySettlement::findOrFail($settlementId);

        if ($settlement->status !== 'PENDING') {
            return $settlement;
        }

        return DB::transaction(function () use ($settlement) {
            // Post settlement journal entry:
            // DR Bank Account (net settled)
            // DR Gateway Fee Expense (fee)
            // DR GST Input Credit (18% GST on fee)
            // CR Payment Gateway Clearing (gross)
            $journalEntry = $this->journalService->postJournal(
                [
                    'fiscal_period_id' => $this->getCurrentPeriodId($settlement->store_id),
                    'store_id'         => $settlement->store_id,
                    'entry_date'       => $settlement->settlement_date,
                    'reference_type'   => 'SETTLEMENT',
                    'reference_id'     => $settlement->id,
                    'narration'        => "Gateway settlement: {$settlement->gateway} — {$settlement->gateway_txn_id}",
                ],
                [
                    ['account_code' => ChartOfAccount::BANK_ACCOUNT,        'debit_amount' => (float) $settlement->net_settled,  'credit_amount' => 0, 'description' => 'Bank credit (net)'],
                    ['account_code' => ChartOfAccount::GATEWAY_FEE_EXPENSE, 'debit_amount' => (float) $settlement->fee_amount,   'credit_amount' => 0, 'description' => 'Gateway fee'],
                    ['account_code' => ChartOfAccount::GST_INPUT_CREDIT,    'debit_amount' => (float) $settlement->gst_on_fee,   'credit_amount' => 0, 'description' => 'GST on gateway fee (ITC)'],
                    ['account_code' => ChartOfAccount::GATEWAY_CLEARING,    'debit_amount' => 0, 'credit_amount' => (float) $settlement->gross_amount, 'description' => 'Clearing cleared'],
                ]
            );

            $settlement->update([
                'status'           => 'SETTLED',
                'journal_entry_id' => $journalEntry->id,
            ]);

            return $settlement->fresh();
        });
    }

    private function getCurrentPeriodId(string $storeId): string
    {
        /** @var \App\Models\FiscalPeriod $period */
        $period = \App\Models\FiscalPeriod::where('status', 'OPEN')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->firstOrFail();

        return $period->id;
    }
}
