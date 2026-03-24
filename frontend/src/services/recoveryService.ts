import axiosInstance from './axiosInstance';

export interface OverdueInvoice {
  invoice_id: string;
  invoice_number: string;
  customer_name: string;
  customer_phone: string | null;
  total_amount: number;
  amount_paid: number;
  balance_due: number;
  due_date: string | null;
  days_overdue: number;
  escalation_stage: number;
  store_id: string;
}

export interface RecoveryCycleResult {
  processed: number;
  reminders_sent: number;
  payment_links_generated: number;
  errors: number;
}

export interface PaymentLink {
  invoice_id: string;
  link_number: string;
  payment_url: string;
  amount_due: number;
  expires_at: string;
}

const recoveryService = {
  async overdue(params?: { store_id?: string; as_of?: string }): Promise<OverdueInvoice[]> {
    const { data } = await axiosInstance.get<{ data: OverdueInvoice[] }>('/recovery/overdue', { params });
    return data.data;
  },

  async runCycle(params?: { store_id?: string }): Promise<RecoveryCycleResult> {
    const { data } = await axiosInstance.post<{ data: RecoveryCycleResult }>('/recovery/run-cycle', params);
    return data.data;
  },

  async generatePaymentLink(invoiceId: string): Promise<PaymentLink> {
    const { data } = await axiosInstance.post<{ data: PaymentLink }>('/recovery/payment-links', { invoice_id: invoiceId });
    return data.data;
  },
};

export default recoveryService;
