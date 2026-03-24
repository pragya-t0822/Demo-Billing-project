import axiosInstance from './axiosInstance';

export interface LineItem {
  sku: string;
  quantity: number;
  unit_price: number;
  discount_amount?: number;
  hsn_sac_code: string;
  gst_rate: number;
}

export interface CreateInvoiceInput {
  store_id: string;
  customer_name: string;
  customer_phone?: string;
  customer_gstin?: string;
  line_items: LineItem[];
  payment_mode: 'CASH' | 'CARD' | 'UPI' | 'CREDIT';
  discount_amount?: number;
  notes?: string;
}

export interface PaymentInput {
  amount_paid: number;
  payment_mode: 'CASH' | 'CARD' | 'UPI' | 'CREDIT';
  gateway_transaction_id?: string;
  bank_reference?: string;
}

export interface Invoice {
  id: string;
  invoice_number: string;
  store_id: string;
  customer_name: string;
  customer_phone: string | null;
  customer_gstin: string | null;
  subtotal: number;
  discount_amount: number;
  taxable_amount: number;
  cgst_amount: number;
  sgst_amount: number;
  igst_amount: number;
  total_amount: number;
  status: 'DRAFT' | 'CONFIRMED' | 'PAID' | 'CANCELLED';
  payment_mode: string;
  notes: string | null;
  invoice_date: string;
  created_at: string;
  line_items?: LineItem[];
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: { page: number; per_page: number; total: number };
}

const billingService = {
  async list(params?: { page?: number; per_page?: number; status?: string; store_id?: string }): Promise<PaginatedResponse<Invoice>> {
    const { data } = await axiosInstance.get<{ success: true; data: Invoice[]; pagination: PaginatedResponse<Invoice>['pagination'] }>('/invoices', { params });
    return { data: data.data, pagination: data.pagination };
  },

  async get(id: string): Promise<Invoice> {
    const { data } = await axiosInstance.get<{ data: Invoice }>(`/invoices/${id}`);
    return data.data;
  },

  async create(input: CreateInvoiceInput): Promise<Invoice> {
    const { data } = await axiosInstance.post<{ data: Invoice }>('/invoices', input);
    return data.data;
  },

  async confirm(id: string): Promise<Invoice> {
    const { data } = await axiosInstance.put<{ data: Invoice }>(`/invoices/${id}/confirm`);
    return data.data;
  },

  async processPayment(id: string, input: PaymentInput): Promise<Invoice> {
    const { data } = await axiosInstance.post<{ data: Invoice }>(`/invoices/${id}/payment`, input);
    return data.data;
  },

  async cancel(id: string, reason?: string): Promise<Invoice> {
    const { data } = await axiosInstance.post<{ data: Invoice }>(`/invoices/${id}/cancel`, { reason });
    return data.data;
  },
};

export default billingService;
