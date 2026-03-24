<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Exceptions\JournalImbalanceException;
use App\Exceptions\BusinessRuleException;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Repositories\Accounting\JournalEntryRepository;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

/**
 * JournalService — enforces all double-entry accounting rules.
 *
 * Core rules (NON-NEGOTIABLE):
 *  1. sum(debits) === sum(credits) — rejects with JournalImbalanceException
 *  2. All account codes must exist in Chart of Accounts
 *  3. Fiscal period must be OPEN
 *  4. Audit log written atomically with every post
 *  5. No editing — corrections via reversal only
 */
class JournalService
{
    public function __construct(
        private readonly JournalEntryRepository $repository,
        private readonly AuditService $audit,
    ) {}

    /**
     * Post a new journal entry.
     *
     * @param array $data  {fiscal_period_id, store_id, entry_date, reference_type, reference_id, narration, posted_by}
     * @param array $lines [{account_code, debit_amount?, credit_amount?, cost_center?, description?}]
     */
    public function postJournal(array $data, array $lines): JournalEntry
    {
        // 1. Validate fiscal period is OPEN
        $period = FiscalPeriod::findOrFail($data['fiscal_period_id']);
        if ($period->isClosed()) {
            throw new BusinessRuleException(
                "Cannot post to fiscal period '{$period->name}' — it is {$period->status}.",
                'CLOSED_PERIOD_VIOLATION'
            );
        }

        // 2. Validate all account codes exist
        $codes = array_column($lines, 'account_code');
        $existingCodes = ChartOfAccount::whereIn('code', $codes)
            ->where('is_active', true)
            ->pluck('code')
            ->toArray();

        $missingCodes = array_diff($codes, $existingCodes);
        if (! empty($missingCodes)) {
            throw new BusinessRuleException(
                'Unknown account codes: ' . implode(', ', $missingCodes),
                'INVALID_ACCOUNT_CODE'
            );
        }

        // 3. Validate no zero-value lines (both debit and credit are 0)
        foreach ($lines as $idx => $line) {
            $dr = (float) ($line['debit_amount'] ?? 0);
            $cr = (float) ($line['credit_amount'] ?? 0);
            if ($dr === 0.0 && $cr === 0.0) {
                throw new BusinessRuleException(
                    "Journal line at index {$idx} has both debit and credit as zero.",
                    'ZERO_VALUE_JOURNAL_LINE'
                );
            }
        }

        // 4. Validate double-entry balance
        $totalDebit  = array_sum(array_column($lines, 'debit_amount'));
        $totalCredit = array_sum(array_column($lines, 'credit_amount'));

        if (abs($totalDebit - $totalCredit) > 0.005) {
            throw new JournalImbalanceException($totalDebit, $totalCredit);
        }

        // 5. Generate entry number and persist (inside DB transaction)
        $entryNumber = $this->repository->generateEntryNumber();

        $entry = $this->repository->createWithLines(
            array_merge($data, [
                'entry_number' => $entryNumber,
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'status'       => 'POSTED',
                'posted_by'    => Auth::id(),
            ]),
            $lines
        );

        // 6. Write audit log
        $this->audit->log(
            action: 'POST_JOURNAL',
            entityType: 'JournalEntry',
            entityId: $entry->id,
            afterState: [
                'entry_number'   => $entry->entry_number,
                'reference_type' => $entry->reference_type,
                'reference_id'   => $entry->reference_id,
                'total_debit'    => $totalDebit,
                'total_credit'   => $totalCredit,
            ],
            storeId: $data['store_id'] ?? null
        );

        return $entry;
    }

    /**
     * Create a reversal entry — mirror of original with opposite Dr/Cr.
     * This is the ONLY way to correct a posted journal entry.
     */
    public function reverseJournal(string $originalEntryId, string $reason): JournalEntry
    {
        $original = $this->repository->findByIdOrFail($originalEntryId);

        if ($original->is_reversed) {
            throw new BusinessRuleException(
                "Journal entry {$original->entry_number} is already reversed.",
                'ALREADY_REVERSED'
            );
        }

        // Build mirror lines — swap debit/credit
        $reversalLines = $original->lines->map(function ($line) {
            return [
                'account_code'  => $line->account_code,
                'debit_amount'  => $line->credit_amount,  // swapped
                'credit_amount' => $line->debit_amount,   // swapped
                'cost_center'   => $line->cost_center,
                'description'   => "REVERSAL: {$line->description}",
            ];
        })->toArray();

        $reversalEntry = $this->postJournal([
            'fiscal_period_id' => $original->fiscal_period_id,
            'store_id'         => $original->store_id,
            'entry_date'       => now()->toDateString(),
            'reference_type'   => 'REVERSAL',
            'reference_id'     => $original->id,
            'narration'        => "REVERSAL of {$original->entry_number}: {$reason}",
        ], $reversalLines);

        // Mark original as reversed
        $this->repository->markReversed($originalEntryId, $reversalEntry->id);

        $this->audit->log(
            action: 'REVERSE_JOURNAL',
            entityType: 'JournalEntry',
            entityId: $originalEntryId,
            beforeState: ['entry_number' => $original->entry_number, 'is_reversed' => false],
            afterState: ['is_reversed' => true, 'reversed_by' => $reversalEntry->id],
            storeId: $original->store_id
        );

        return $reversalEntry;
    }
}
