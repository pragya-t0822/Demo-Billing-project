// Central re-export barrel — import from here or directly from individual service files.
export { default as axiosInstance, tokenStorage } from './axiosInstance';
export { default as authService } from './authService';
export { default as billingService } from './billingService';
export { default as accountingService } from './accountingService';
export { default as inventoryService } from './inventoryService';
export { default as reconciliationService } from './reconciliationService';
export { default as recoveryService } from './recoveryService';
export { default as reportingService } from './reportingService';

// Type re-exports
export type { LoginResponse, AuthUser } from './authService';
export type { Invoice, CreateInvoiceInput, PaymentInput, LineItem } from './billingService';
export type { JournalEntry, LedgerEntry, TrialBalance, CreateJournalInput } from './accountingService';
export type { StockLevel, MetalRate, WeightPriceResult, MetalType } from './inventoryService';
export type { ReconciliationResult, BankEntry, ImportInput } from './reconciliationService';
export type { OverdueInvoice, RecoveryCycleResult, PaymentLink } from './recoveryService';
export type { ProfitLossReport, BalanceSheetReport, CashFlowReport, GstSummaryReport, ReportParams } from './reportingService';
