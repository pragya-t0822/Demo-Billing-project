import axiosInstance from './axiosInstance';

export interface BankEntry {
  id: string;
  entry_date: string;
  narration: string;
  credit_amount: number;
  debit_amount: number;
  reference_number: string | null;
  status: 'PENDING' | 'MATCHED' | 'UNMATCHED';
}

export interface ReconciliationResult {
  reconciliation_run_id: string;
  matched: Array<{
    bank_entry_id: string;
    system_payment_id: string;
    match_confidence: 'HIGH' | 'MEDIUM' | 'LOW';
    match_criteria: string[];
  }>;
  unmatched: BankEntry[];
  summary: {
    total_entries: number;
    matched_count: number;
    unmatched_count: number;
  };
}

export interface ImportInput {
  store_id: string;
  bank_name: string;
  account_number: string;
  statement_date: string;
  entries: Array<{
    entry_date: string;
    narration: string;
    credit_amount: number;
    debit_amount: number;
    reference_number?: string;
  }>;
}

const reconciliationService = {
  async import(input: ImportInput): Promise<{ bank_statement_id: string }> {
    const { data } = await axiosInstance.post<{ data: { bank_statement_id: string } }>('/reconciliation/import', input);
    return data.data;
  },

  async run(bankStatementId: string, options?: { tolerance?: number; date_window?: number }): Promise<ReconciliationResult> {
    const { data } = await axiosInstance.post<{ data: ReconciliationResult }>('/reconciliation/run', {
      bank_statement_id: bankStatementId,
      ...options,
    });
    return data.data;
  },

  async unmatched(params?: { store_id?: string; bank_statement_id?: string }): Promise<BankEntry[]> {
    const { data } = await axiosInstance.get<{ data: BankEntry[] }>('/reconciliation/unmatched', { params });
    return data.data;
  },

  async processSettlement(settlementId: string): Promise<{ id: string; status: string; journal_entry_id: string }> {
    const { data } = await axiosInstance.post<{ data: { id: string; status: string; journal_entry_id: string } }>(
      `/settlements/${settlementId}/process`,
    );
    return data.data;
  },
};

export default reconciliationService;
