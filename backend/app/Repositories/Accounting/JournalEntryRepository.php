<?php

declare(strict_types=1);

namespace App\Repositories\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class JournalEntryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new JournalEntry());
    }

    /**
     * Persist journal entry + lines in a single DB transaction.
     * Returns the created JournalEntry with lines eager-loaded.
     */
    public function createWithLines(array $entryData, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($entryData, $lines) {
            /** @var JournalEntry $entry */
            $entry = JournalEntry::create($entryData);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_code'     => $line['account_code'],
                    'debit_amount'     => $line['debit_amount'] ?? 0,
                    'credit_amount'    => $line['credit_amount'] ?? 0,
                    'cost_center'      => $line['cost_center'] ?? null,
                    'description'      => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines');
        });
    }

    public function markReversed(string $entryId, string $reversalEntryId): void
    {
        JournalEntry::where('id', $entryId)->update([
            'is_reversed' => true,
            'reversed_by' => $reversalEntryId,
        ]);
    }

    public function generateEntryNumber(): string
    {
        return $this->generateSequentialNumber('JE', 'entry_number', 'journal_entries');
    }

    /** Get ledger for an account: all lines with running balance */
    public function getLedgerLines(
        string $accountCode,
        string $startDate,
        string $endDate,
        ?string $storeId = null
    ): array {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_code', $accountCode)
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->when($storeId, fn($q) => $q->where('je.store_id', $storeId))
            ->orderBy('je.entry_date')
            ->orderBy('je.created_at')
            ->select(
                'je.entry_date as date',
                'je.entry_number',
                'je.narration',
                'jel.debit_amount',
                'jel.credit_amount',
                'jel.description'
            );

        return $query->get()->toArray();
    }

    /** Opening balance for an account before a given date */
    public function getOpeningBalance(
        string $accountCode,
        string $beforeDate,
        string $normalBalance,
        ?string $storeId = null
    ): float {
        $result = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_code', $accountCode)
            ->where('je.entry_date', '<', $beforeDate)
            ->when($storeId, fn($q) => $q->where('je.store_id', $storeId))
            ->selectRaw('SUM(jel.debit_amount) as total_debit, SUM(jel.credit_amount) as total_credit')
            ->first();

        $totalDebit  = (float) ($result->total_debit ?? 0);
        $totalCredit = (float) ($result->total_credit ?? 0);

        return $normalBalance === 'DEBIT'
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;
    }

    /** Trial balance: running balance for all accounts as of a date */
    public function getTrialBalanceData(string $asOfDate, ?string $storeId = null): array
    {
        return DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.code', '=', 'jel.account_code')
            ->where('je.entry_date', '<=', $asOfDate)
            ->when($storeId, fn($q) => $q->where('je.store_id', $storeId))
            ->groupBy('jel.account_code', 'coa.name', 'coa.normal_balance', 'coa.type')
            ->orderBy('jel.account_code')
            ->selectRaw('
                jel.account_code,
                coa.name as account_name,
                coa.normal_balance,
                coa.type,
                SUM(jel.debit_amount) as total_debit,
                SUM(jel.credit_amount) as total_credit
            ')
            ->get()
            ->toArray();
    }
}
