import axiosInstance from './axiosInstance';

export type MetalType = 'GOLD' | 'SILVER' | 'PLATINUM';

export interface StockLevel {
  sku: string;
  product_name: string;
  store_id: string;
  quantity: number;
  weight_grams: number | null;
  reorder_level: number;
  is_weight_based: boolean;
}

export interface MetalRate {
  metal_type: MetalType;
  rate_per_gram: number;
  updated_at: string;
}

export interface WeightPriceResult {
  weight_grams: number;
  rate_per_gram: number;
  making_charge: number;
  base_price: number;
  gst_amount: number;
  total_price: number;
}

export interface StockAdjustmentInput {
  store_id: string;
  sku: string;
  adjustment_type: 'ADD' | 'REMOVE' | 'SET';
  quantity?: number;
  weight_grams?: number;
  reason: string;
}

const inventoryService = {
  async getStock(sku: string): Promise<StockLevel> {
    const { data } = await axiosInstance.get<{ data: StockLevel }>(`/inventory/${sku}`);
    return data.data;
  },

  async lowStock(params?: { store_id?: string }): Promise<StockLevel[]> {
    const { data } = await axiosInstance.get<{ data: StockLevel[] }>('/inventory/low-stock', { params });
    return data.data;
  },

  async getMetalRate(type: MetalType): Promise<MetalRate> {
    const { data } = await axiosInstance.get<{ data: MetalRate }>(`/inventory/metal-rate/${type}`);
    return data.data;
  },

  async setMetalRate(input: { metal_type: MetalType; rate_per_gram: number; store_id: string }): Promise<MetalRate> {
    const { data } = await axiosInstance.post<{ data: MetalRate }>('/inventory/metal-rate', input);
    return data.data;
  },

  async calculateWeightPrice(input: { sku: string; weight_grams: number; store_id: string }): Promise<WeightPriceResult> {
    const { data } = await axiosInstance.post<{ data: WeightPriceResult }>('/inventory/weight-price', input);
    return data.data;
  },

  async adjustment(input: StockAdjustmentInput): Promise<StockLevel> {
    const { data } = await axiosInstance.post<{ data: StockLevel }>('/inventory/adjustment', input);
    return data.data;
  },
};

export default inventoryService;
