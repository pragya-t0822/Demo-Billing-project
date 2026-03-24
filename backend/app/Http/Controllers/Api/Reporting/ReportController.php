<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService) {}

    /** GET /api/reports/profit-loss */
    public function profitLoss(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'store_id'   => 'nullable|uuid|exists:stores,id',
        ]);

        $report = $this->reportingService->profitAndLoss($data['start_date'], $data['end_date'], $data['store_id'] ?? null);
        return $this->success($report);
    }

    /** GET /api/reports/balance-sheet */
    public function balanceSheet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'as_of_date' => 'required|date',
            'store_id'   => 'nullable|uuid|exists:stores,id',
        ]);

        $report = $this->reportingService->balanceSheet($data['as_of_date'], $data['store_id'] ?? null);
        return $this->success($report);
    }

    /** GET /api/reports/cash-flow */
    public function cashFlow(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'store_id'   => 'nullable|uuid|exists:stores,id',
        ]);

        $report = $this->reportingService->cashFlow($data['start_date'], $data['end_date'], $data['store_id'] ?? null);
        return $this->success($report);
    }

    /** GET /api/reports/gst-summary */
    public function gstSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'store_id'   => 'required|uuid|exists:stores,id',
        ]);

        // Aggregate GST from invoice line items
        $gst = DB::table('invoice_line_items as ili')
            ->join('invoices as inv', 'inv.id', '=', 'ili.invoice_id')
            ->where('inv.store_id', $data['store_id'])
            ->whereBetween('inv.invoice_date', [$data['start_date'], $data['end_date']])
            ->whereIn('inv.status', ['CONFIRMED', 'PAID', 'PARTIAL'])
            ->selectRaw('
                ili.hsn_code,
                SUM(ili.taxable_amount) as taxable_value,
                SUM(ili.cgst_amount) as total_cgst,
                SUM(ili.sgst_amount) as total_sgst,
                SUM(ili.igst_amount) as total_igst,
                SUM(ili.total_tax) as total_tax
            ')
            ->groupBy('ili.hsn_code')
            ->get();

        return $this->success([
            'period'   => ['start' => $data['start_date'], 'end' => $data['end_date']],
            'store_id' => $data['store_id'],
            'by_hsn'   => $gst,
            'totals'   => [
                'taxable_value' => $gst->sum('taxable_value'),
                'total_cgst'    => $gst->sum('total_cgst'),
                'total_sgst'    => $gst->sum('total_sgst'),
                'total_igst'    => $gst->sum('total_igst'),
                'total_tax'     => $gst->sum('total_tax'),
            ],
        ]);
    }
}
