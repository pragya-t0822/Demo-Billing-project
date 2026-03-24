import axiosInstance from './axiosInstance';

export interface ReportParams {
  store_id?: string;
  from_date?: string;
  to_date?: string;
  fiscal_period_id?: string;
}

export interface ProfitLossReport {
  period: { from: string; to: string };
  revenue: { total: number; breakdown: Record<string, number> };
  expenses: { total: number; breakdown: Record<string, number> };
  gross_profit: number;
  net_profit: number;
  gross_margin_pct: number;
  net_margin_pct: number;
}

export interface BalanceSheetReport {
  as_of: string;
  assets: { total: number; current: Record<string, number>; non_current: Record<string, number> };
  liabilities: { total: number; current: Record<string, number>; non_current: Record<string, number> };
  equity: { total: number; breakdown: Record<string, number> };
  balanced: boolean;
}

export interface CashFlowReport {
  period: { from: string; to: string };
  operating: number;
  investing: number;
  financing: number;
  net_change: number;
  opening_balance: number;
  closing_balance: number;
}

export interface GstSummaryReport {
  period: { from: string; to: string };
  total_taxable_value: number;
  total_cgst: number;
  total_sgst: number;
  total_igst: number;
  total_gst: number;
  input_credit: number;
  net_payable: number;
}

const reportingService = {
  async profitLoss(params: ReportParams): Promise<ProfitLossReport> {
    const { data } = await axiosInstance.get<{ data: ProfitLossReport }>('/reports/profit-loss', { params });
    return data.data;
  },

  async balanceSheet(params: ReportParams): Promise<BalanceSheetReport> {
    const { data } = await axiosInstance.get<{ data: BalanceSheetReport }>('/reports/balance-sheet', { params });
    return data.data;
  },

  async cashFlow(params: ReportParams): Promise<CashFlowReport> {
    const { data } = await axiosInstance.get<{ data: CashFlowReport }>('/reports/cash-flow', { params });
    return data.data;
  },

  async gstSummary(params: ReportParams): Promise<GstSummaryReport> {
    const { data } = await axiosInstance.get<{ data: GstSummaryReport }>('/reports/gst-summary', { params });
    return data.data;
  },
};

export default reportingService;
