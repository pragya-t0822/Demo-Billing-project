<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Exceptions\BusinessRuleException;
use App\Services\Accounting\LedgerService;
use Illuminate\Support\Facades\DB;

/**
 * ReportingService — generates P&L, Balance Sheet, and Cash Flow.
 *
 * Rules:
 *  - Read-only — never modifies financial records
 *  - P&L and Cash Flow require trial balance to be balanced first
 *  - Balance Sheet MUST balance (Assets = Liabilities + Equity), throws exception if not
 *  - All figures sourced from posted journal entries
 */
class ReportingService
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Generate Profit & Loss statement for a date range.
     */
    public function profitAndLoss(string $startDate, string $endDate, ?string $storeId = null): array
    {
        // Validate trial balance first
        $tb = $this->ledgerService->getTrialBalance($endDate, $storeId);
        if ($tb['status'] === 'IMBALANCED') {
            throw new BusinessRuleException(
                "Cannot generate P&L: Trial balance is IMBALANCED with variance {$tb['variance']}.",
                'TRIAL_BALANCE_IMBALANCED'
            );
        }

        $revenue  = $this->sumAccountRange($startDate, $endDate, 4000, 4999, $storeId);
        $cogs     = $this->sumAccountRange($startDate, $endDate, 5000, 5999, $storeId);
        $opex     = $this->sumAccountRange($startDate, $endDate, 6000, 6999, $storeId);
        $taxAccts = $this->sumAccountRange($startDate, $endDate, 7000, 7999, $storeId);

        $grossProfit    = round($revenue['total_credit'] - $revenue['total_debit'] - $cogs['total_debit'] + $cogs['total_credit'], 2);
        $operatingProfit= round($grossProfit - ($opex['total_debit'] - $opex['total_credit']), 2);
        $netProfit      = round($operatingProfit - ($taxAccts['total_debit'] - $taxAccts['total_credit']), 2);

        $totalRevenue   = round($revenue['total_credit'] - $revenue['total_debit'], 2);
        $totalCogs      = round($cogs['total_debit'] - $cogs['total_credit'], 2);
        $totalOpex      = round($opex['total_debit'] - $opex['total_credit'], 2);

        return [
            'period'            => ['start' => $startDate, 'end' => $endDate],
            'store_id'          => $storeId,
            'revenue'           => ['total_revenue' => $totalRevenue, 'breakdown' => $revenue['accounts']],
            'cost_of_goods_sold'=> ['total_cogs' => $totalCogs, 'breakdown' => $cogs['accounts']],
            'gross_profit'      => $grossProfit,
            'gross_margin_pct'  => $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0,
            'operating_expenses'=> ['total_opex' => $totalOpex, 'breakdown' => $opex['accounts']],
            'operating_profit'  => $operatingProfit,
            'net_profit'        => $netProfit,
            'net_margin_pct'    => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0,
            'generated_at'      => now()->toIso8601String(),
        ];
    }

    /**
     * Generate Balance Sheet as of a specific date.
     * THROWS if Assets ≠ Liabilities + Equity.
     */
    public function balanceSheet(string $asOfDate, ?string $storeId = null): array
    {
        $assets      = $this->sumAccountRange(null, $asOfDate, 1000, 1999, $storeId, cumulative: true);
        $liabilities = $this->sumAccountRange(null, $asOfDate, 2000, 2999, $storeId, cumulative: true);
        $equity      = $this->sumAccountRange(null, $asOfDate, 3000, 3999, $storeId, cumulative: true);
        $revenue     = $this->sumAccountRange(null, $asOfDate, 4000, 4999, $storeId, cumulative: true);
        $cogs        = $this->sumAccountRange(null, $asOfDate, 5000, 5999, $storeId, cumulative: true);
        $opex        = $this->sumAccountRange(null, $asOfDate, 6000, 6999, $storeId, cumulative: true);

        $totalAssets        = round($assets['total_debit'] - $assets['total_credit'], 2);
        $totalLiabilities   = round($liabilities['total_credit'] - $liabilities['total_debit'], 2);
        $totalEquity        = round($equity['total_credit'] - $equity['total_debit'], 2);

        // Retained earnings = revenue - cogs - opex
        $retainedEarnings   = round(
            ($revenue['total_credit'] - $revenue['total_debit'])
            - ($cogs['total_debit'] - $cogs['total_credit'])
            - ($opex['total_debit'] - $opex['total_credit']),
            2
        );

        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity + $retainedEarnings, 2);
        $variance = round(abs($totalAssets - $totalLiabilitiesAndEquity), 2);

        if ($variance > 0.02) {
            throw new BusinessRuleException(
                "Balance Sheet imbalance: Assets={$totalAssets}, Liabilities+Equity={$totalLiabilitiesAndEquity}, Variance={$variance}",
                'BALANCE_SHEET_IMBALANCE'
            );
        }

        return [
            'as_of_date'        => $asOfDate,
            'store_id'          => $storeId,
            'assets'            => ['total' => $totalAssets, 'breakdown' => $assets['accounts']],
            'liabilities'       => ['total' => $totalLiabilities, 'breakdown' => $liabilities['accounts']],
            'equity'            => [
                'total'             => round($totalEquity + $retainedEarnings, 2),
                'share_capital'     => $totalEquity,
                'retained_earnings' => $retainedEarnings,
            ],
            'balance_check'     => [
                'total_assets'                  => $totalAssets,
                'total_liabilities_and_equity'  => $totalLiabilitiesAndEquity,
                'balanced'                      => $variance < 0.02,
                'variance'                      => $variance,
            ],
            'generated_at'      => now()->toIso8601String(),
        ];
    }

    /**
     * Generate Cash Flow statement (indirect method).
     */
    public function cashFlow(string $startDate, string $endDate, ?string $storeId = null): array
    {
        $pl = $this->profitAndLoss($startDate, $endDate, $storeId);

        // Get changes in working capital accounts
        $arOpen  = $this->getAccountBalance('1200', $startDate, $storeId);
        $arClose = $this->getAccountBalance('1200', $endDate, $storeId);
        $invOpen = $this->getAccountBalance('1300', $startDate, $storeId);
        $invClose= $this->getAccountBalance('1300', $endDate, $storeId);
        $apOpen  = $this->getAccountBalance('2100', $startDate, $storeId);
        $apClose = $this->getAccountBalance('2100', $endDate, $storeId);

        $changeAR  = round($arOpen - $arClose, 2);   // decrease in AR = cash inflow
        $changeInv = round($invOpen - $invClose, 2);  // decrease in inventory = cash inflow
        $changeAP  = round($apClose - $apOpen, 2);    // increase in AP = cash inflow

        $netOperating = round($pl['net_profit'] + $changeAR + $changeInv + $changeAP, 2);

        // Get opening and closing cash/bank balances
        $openingCash = $this->getAccountBalance('1100', $startDate, $storeId)
                     + $this->getAccountBalance('1110', $startDate, $storeId);
        $closingCash = $this->getAccountBalance('1100', $endDate, $storeId)
                     + $this->getAccountBalance('1110', $endDate, $storeId);

        return [
            'period'                => ['start' => $startDate, 'end' => $endDate],
            'store_id'              => $storeId,
            'operating_activities'  => [
                'net_profit'                => $pl['net_profit'],
                'change_in_receivables'     => $changeAR,
                'change_in_inventory'       => $changeInv,
                'change_in_payables'        => $changeAP,
                'net_operating_cashflow'    => $netOperating,
            ],
            'net_change_in_cash'    => round($closingCash - $openingCash, 2),
            'opening_cash_balance'  => round($openingCash, 2),
            'closing_cash_balance'  => round($closingCash, 2),
            'generated_at'          => now()->toIso8601String(),
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function sumAccountRange(
        ?string $startDate,
        string $endDate,
        int $codeFrom,
        int $codeTo,
        ?string $storeId,
        bool $cumulative = false
    ): array {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('chart_of_accounts as coa', 'coa.code', '=', 'jel.account_code')
            ->whereRaw('CAST(coa.code AS UNSIGNED) >= ?', [$codeFrom])
            ->whereRaw('CAST(coa.code AS UNSIGNED) <= ?', [$codeTo])
            ->where('je.entry_date', '<=', $endDate);

        if ($startDate && ! $cumulative) {
            $query->where('je.entry_date', '>=', $startDate);
        }

        if ($storeId) {
            $query->where('je.store_id', $storeId);
        }

        $rows = $query->groupBy('jel.account_code', 'coa.name')
            ->selectRaw('jel.account_code, coa.name, SUM(jel.debit_amount) as total_debit, SUM(jel.credit_amount) as total_credit')
            ->get();

        $totalDebit  = 0;
        $totalCredit = 0;
        $accounts    = [];

        foreach ($rows as $row) {
            $totalDebit  += (float) $row->total_debit;
            $totalCredit += (float) $row->total_credit;
            $accounts[]   = [
                'code'         => $row->account_code,
                'name'         => $row->name,
                'total_debit'  => round((float) $row->total_debit, 2),
                'total_credit' => round((float) $row->total_credit, 2),
            ];
        }

        return [
            'total_debit'  => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'accounts'     => $accounts,
        ];
    }

    private function getAccountBalance(string $accountCode, string $asOfDate, ?string $storeId): float
    {
        $result = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('jel.account_code', $accountCode)
            ->where('je.entry_date', '<=', $asOfDate)
            ->when($storeId, fn($q) => $q->where('je.store_id', $storeId))
            ->selectRaw('SUM(jel.debit_amount) as d, SUM(jel.credit_amount) as c')
            ->first();

        return round((float) ($result->d ?? 0) - (float) ($result->c ?? 0), 2);
    }
}
