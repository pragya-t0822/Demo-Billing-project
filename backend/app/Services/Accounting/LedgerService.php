<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Repositories\Accounting\JournalEntryRepository;

class LedgerService
{
    public function __construct(
        private readonly JournalEntryRepository $repository
    ) {}

    /**
     * Generate a ledger for a specific account with running balance.
     */
    public function getLedger(
        string $accountCode,
        string $startDate,
        string $endDate,
        ?string $storeId = null
    ): array {
        $account = ChartOfAccount::where('code', $accountCode)
            ->where('is_active', true)
            ->firstOrFail();

        $openingBalance = $this->repository->getOpeningBalance(
            $accountCode,
            $startDate,
            $account->normal_balance,
            $storeId
        );

        $lines = $this->repository->getLedgerLines($accountCode, $startDate, $endDate, $storeId);

        $runningBalance = $openingBalance;
        $totalDebits = 0;
        $totalCredits = 0;

        $entries = array_map(function ($line) use (&$runningBalance, &$totalDebits, &$totalCredits, $account) {
            $debit  = (float) $line->debit_amount;
            $credit = (float) $line->credit_amount;

            $totalDebits  += $debit;
            $totalCredits += $credit;

            // Adjust running balance based on normal balance
            $runningBalance += $account->normal_balance === 'DEBIT'
                ? ($debit - $credit)
                : ($credit - $debit);

            return [
                'date'             => $line->date,
                'entry_number'     => $line->entry_number,
                'narration'        => $line->narration,
                'description'      => $line->description,
                'debit'            => $debit,
                'credit'           => $credit,
                'running_balance'  => round($runningBalance, 2),
            ];
        }, $lines);

        return [
            'account_code'     => $accountCode,
            'account_name'     => $account->name,
            'account_type'     => $account->type,
            'normal_balance'   => $account->normal_balance,
            'opening_balance'  => round($openingBalance, 2),
            'closing_balance'  => round($runningBalance, 2),
            'total_debits'     => round($totalDebits, 2),
            'total_credits'    => round($totalCredits, 2),
            'entries'          => $entries,
        ];
    }

    /**
     * Trial balance — verifies total debits = total credits across all accounts.
     * MUST show variance = 0.00 for financial integrity.
     */
    public function getTrialBalance(string $asOfDate, ?string $storeId = null): array
    {
        $rows = $this->repository->getTrialBalanceData($asOfDate, $storeId);

        $accounts = [];
        $grandTotalDebit  = 0;
        $grandTotalCredit = 0;

        foreach ($rows as $row) {
            $debitBalance  = 0;
            $creditBalance = 0;

            if ($row->normal_balance === 'DEBIT') {
                $net = (float) $row->total_debit - (float) $row->total_credit;
                $debitBalance  = max($net, 0);
                $creditBalance = max(-$net, 0);
            } else {
                $net = (float) $row->total_credit - (float) $row->total_debit;
                $creditBalance = max($net, 0);
                $debitBalance  = max(-$net, 0);
            }

            $grandTotalDebit  += $debitBalance;
            $grandTotalCredit += $creditBalance;

            $accounts[] = [
                'account_code'   => $row->account_code,
                'account_name'   => $row->account_name,
                'type'           => $row->type,
                'debit_balance'  => round($debitBalance, 2),
                'credit_balance' => round($creditBalance, 2),
            ];
        }

        // Round to 2 decimal places before comparison; use < 0.005 tolerance
        // to guard against floating-point precision drift after summing many lines
        $variance = round(abs($grandTotalDebit - $grandTotalCredit), 2);
        $balanced = $variance < 0.005;

        return [
            'as_of_date'    => $asOfDate,
            'store_id'      => $storeId,
            'status'        => $balanced ? 'BALANCED' : 'IMBALANCED',
            'total_debits'  => round($grandTotalDebit, 2),
            'total_credits' => round($grandTotalCredit, 2),
            'variance'      => $variance,
            'accounts'      => $accounts,
            'alert'         => $balanced ? null : "CRITICAL: Trial balance variance of {$variance} detected. Investigate immediately.",
        ];
    }
}
