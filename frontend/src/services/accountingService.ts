import axiosInstance from './axiosInstance';

export interface JournalLine {
  account_code: string;
  debit_amount: number;
  credit_amount: number;
  description?: string;
}

export interface CreateJournalInput {
  store_id: string;
  fiscal_period_id: string;
  entry_date: string;
  narration: string;
  reference_type?: string;
  reference_id?: string;
  lines: JournalLine[];
}

export interface JournalEntry {
  id: string;
  entry_number: string;
  store_id: string;
  entry_date: string;
  narration: string;
  reference_type: string | null;
  reference_id: string | null;
  total_debit: number;
  total_credit: number;
  status: 'POSTED' | 'REVERSED';
  lines?: JournalLine[];
  created_at: string;
}

export interface LedgerEntry {
  date: string;
  narration: string;
  debit: number;
  credit: number;
  balance: number;
  reference: string | null;
}

export interface TrialBalance {
  accounts: Array<{
    account_code: string;
    account_name: string;
    total_debit: number;
    total_credit: number;
  }>;
  total_debit: number;
  total_credit: number;
  balanced: boolean;
}

const accountingService = {
  async postJournal(input: CreateJournalInput): Promise<JournalEntry> {
    const { data } = await axiosInstance.post<{ data: JournalEntry }>('/accounting/journal-entries', input);
    return data.data;
  },

  async getJournal(id: string): Promise<JournalEntry> {
    const { data } = await axiosInstance.get<{ data: JournalEntry }>(`/accounting/journal-entries/${id}`);
    return data.data;
  },

  async reverseJournal(id: string, reason: string): Promise<JournalEntry> {
    const { data } = await axiosInstance.post<{ data: JournalEntry }>(`/accounting/journal-entries/${id}/reverse`, { reason });
    return data.data;
  },

  async getLedger(accountCode: string, params?: { from?: string; to?: string; store_id?: string }): Promise<LedgerEntry[]> {
    const { data } = await axiosInstance.get<{ data: LedgerEntry[] }>(`/accounting/ledger/${accountCode}`, { params });
    return data.data;
  },

  async trialBalance(params?: { store_id?: string; as_of?: string }): Promise<TrialBalance> {
    const { data } = await axiosInstance.get<{ data: TrialBalance }>('/accounting/trial-balance', { params });
    return data.data;
  },
};

export default accountingService;
