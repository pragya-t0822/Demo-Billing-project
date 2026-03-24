import { useState, useCallback } from 'react';
import { BarChart3, Download, RefreshCcw, TrendingUp, TrendingDown, FileText } from 'lucide-react';
import { PageSpinner } from '../../components/ui/Spinner';
import { Alert } from '../../components/ui/Alert';
import { Badge } from '../../components/ui/Badge';
import { formatINR, formatPct } from '../../utils/currency';
import { formatDate } from '../../utils/date';
import reportingService, {
  type ProfitLossReport,
  type BalanceSheetReport,
  type CashFlowReport,
  type GstSummaryReport,
} from '../../services/reportingService';

/* ── Demo data ──────────────────────────────────────────────────────── */
const DEMO_PL: ProfitLossReport = {
  period: { from: '2026-03-01', to: '2026-03-23' },
  revenue: {
    total: 384500,
    breakdown: { 'Gold Jewellery Sales': 290000, 'Silver Jewellery Sales': 72000, 'Accessories': 22500 },
  },
  expenses: {
    total: 218000,
    breakdown: { 'COGS — Jewellery': 185000, 'Staff Salaries': 25000, 'Rent': 8000 },
  },
  gross_profit: 166500, net_profit: 125000, gross_margin_pct: 43.3, net_margin_pct: 32.5,
};

const DEMO_BS: BalanceSheetReport = {
  as_of: '2026-03-23',
  assets: {
    total: 1218000,
    current:     { 'Cash & Bank': 243300, 'Accounts Receivable': 124000 },
    non_current: { 'Inventory Asset': 850000, 'Fixed Assets': 700 },
  },
  liabilities: {
    total: 376000,
    current:     { 'Accounts Payable': 320000, 'CGST Payable': 18000, 'SGST Payable': 18000 },
    non_current: { 'Long-term Loan': 20000 },
  },
  equity: { total: 842000, breakdown: { 'Capital': 717000, 'Retained Earnings': 125000 } },
  balanced: true,
};

const DEMO_CF: CashFlowReport = {
  period: { from: '2026-03-01', to: '2026-03-23' },
  operating: 135000, investing: -12000, financing: 0,
  net_change: 123000, opening_balance: 120300, closing_balance: 243300,
};

const DEMO_GST: GstSummaryReport = {
  period: { from: '2026-03-01', to: '2026-03-23' },
  total_taxable_value: 384500, total_cgst: 5767.50, total_sgst: 5767.50, total_igst: 0,
  total_gst: 11535, input_credit: 4200, net_payable: 7335,
};

type Tab = 'pl' | 'bs' | 'cf' | 'gst';

const TAB_LABELS: Record<Tab, string> = {
  pl: 'P & L', bs: 'Balance Sheet', cf: 'Cash Flow', gst: 'GST Summary',
};

export default function Reporting() {
  const [tab, setTab]       = useState<Tab>('pl');
  const [fromDate, setFrom] = useState('2026-03-01');
  const [toDate, setTo]     = useState('2026-03-23');
  const [loading, setLoading] = useState(false);
  const [error, setError]   = useState<string | null>(null);

  const [pl,  setPl]  = useState<ProfitLossReport  | null>(null);
  const [bs,  setBs]  = useState<BalanceSheetReport | null>(null);
  const [cf,  setCf]  = useState<CashFlowReport     | null>(null);
  const [gst, setGst] = useState<GstSummaryReport   | null>(null);

  const params = { from_date: fromDate, to_date: toDate };

  const load = useCallback(async (t: Tab) => {
    setLoading(true);
    setError(null);
    try {
      if (t === 'pl')  { const d = await reportingService.profitLoss(params);   setPl(d);  }
      if (t === 'bs')  { const d = await reportingService.balanceSheet(params);  setBs(d);  }
      if (t === 'cf')  { const d = await reportingService.cashFlow(params);      setCf(d);  }
      if (t === 'gst') { const d = await reportingService.gstSummary(params);   setGst(d); }
    } catch {
      // Use demo data
      if (t === 'pl')  setPl(DEMO_PL);
      if (t === 'bs')  setBs(DEMO_BS);
      if (t === 'cf')  setCf(DEMO_CF);
      if (t === 'gst') setGst(DEMO_GST);
    } finally {
      setLoading(false);
    }
  }, [fromDate, toDate]); // eslint-disable-line react-hooks/exhaustive-deps

  const switchTab = (t: Tab) => {
    setTab(t);
    const already = t === 'pl' ? pl : t === 'bs' ? bs : t === 'cf' ? cf : gst;
    if (!already) load(t);
  };

  // Load P&L on mount
  useState(() => { load('pl'); });

  /* ── Section helper ─────────────────────────────────────────────── */
  const Section = ({ title, items, total, highlight }: {
    title: string; items: Record<string, number>; total: number; highlight?: 'green' | 'red';
  }) => (
    <div>
      <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{title}</h3>
      <div className="space-y-1">
        {Object.entries(items).map(([k, v]) => (
          <div key={k} className="flex justify-between text-sm">
            <span className="text-slate-600 pl-3">{k}</span>
            <span className="font-mono text-slate-900">{formatINR(v)}</span>
          </div>
        ))}
        <div className={`flex justify-between text-sm font-bold border-t border-slate-200 pt-1 mt-1 ${highlight === 'green' ? 'text-green-700' : highlight === 'red' ? 'text-red-700' : 'text-slate-900'}`}>
          <span>Total {title}</span>
          <span className="font-mono">{formatINR(total)}</span>
        </div>
      </div>
    </div>
  );

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <BarChart3 size={22} className="text-blue-600" /> Reporting
          </h1>
          <p className="text-sm text-slate-500 mt-0.5">Financial reports — P&L · Balance Sheet · Cash Flow · GST</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
          <Download size={14} /> Export
        </button>
      </div>

      {error && <Alert variant="error" dismissible>{error}</Alert>}

      {/* Date range */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-4 flex items-center gap-4 flex-wrap">
        <span className="text-sm font-medium text-slate-600">Period:</span>
        <div className="flex items-center gap-2">
          <input type="date" value={fromDate} onChange={e => setFrom(e.target.value)}
            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          <span className="text-slate-400 text-sm">to</span>
          <input type="date" value={toDate} onChange={e => setTo(e.target.value)}
            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <button
          onClick={() => load(tab)}
          className="flex items-center gap-2 px-4 py-1.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors"
        >
          <RefreshCcw size={13} /> Generate
        </button>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
        {(Object.keys(TAB_LABELS) as Tab[]).map(t => (
          <button
            key={t}
            onClick={() => switchTab(t)}
            className={`px-4 py-1.5 text-sm font-medium rounded-lg transition-all ${
              tab === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            {TAB_LABELS[t]}
          </button>
        ))}
      </div>

      {loading && <div className="py-16"><PageSpinner /></div>}

      {/* P & L */}
      {!loading && tab === 'pl' && pl && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="font-bold text-slate-900">Profit & Loss Statement</h2>
              <p className="text-xs text-slate-500 mt-0.5">{formatDate(pl.period.from)} — {formatDate(pl.period.to)}</p>
            </div>
            <div className="flex gap-3">
              <div className="text-right">
                <p className="text-xs text-slate-500">Gross Margin</p>
                <p className="font-mono font-bold text-green-700">{formatPct(pl.gross_margin_pct)}</p>
              </div>
              <div className="text-right">
                <p className="text-xs text-slate-500">Net Margin</p>
                <p className="font-mono font-bold text-blue-700">{formatPct(pl.net_margin_pct)}</p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <Section title="Revenue"  items={pl.revenue.breakdown}  total={pl.revenue.total}  highlight="green" />
            <Section title="Expenses" items={pl.expenses.breakdown} total={pl.expenses.total} highlight="red"   />
          </div>

          <div className="border-t-2 border-slate-200 pt-4 space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-slate-700 font-medium">Gross Profit</span>
              <span className={`font-mono font-semibold ${pl.gross_profit >= 0 ? 'text-green-700' : 'text-red-700'}`}>
                {formatINR(pl.gross_profit)}
              </span>
            </div>
            <div className="flex justify-between text-base font-bold">
              <span className="text-slate-900 flex items-center gap-2">
                {pl.net_profit >= 0 ? <TrendingUp size={16} className="text-green-600" /> : <TrendingDown size={16} className="text-red-600" />}
                Net Profit
              </span>
              <span className={`font-mono text-lg ${pl.net_profit >= 0 ? 'text-green-700' : 'text-red-700'}`}>
                {formatINR(pl.net_profit)}
              </span>
            </div>
          </div>
        </div>
      )}

      {/* Balance Sheet */}
      {!loading && tab === 'bs' && bs && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="font-bold text-slate-900">Balance Sheet</h2>
              <p className="text-xs text-slate-500 mt-0.5">As of {formatDate(bs.as_of)}</p>
            </div>
            <Badge variant={bs.balanced ? 'success' : 'danger'}>
              {bs.balanced ? 'BALANCED' : 'IMBALANCED — CRITICAL'}
            </Badge>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Assets */}
            <div className="space-y-4">
              <Section title="Current Assets"     items={bs.assets.current}     total={Object.values(bs.assets.current).reduce((a, b) => a + b, 0)}     />
              <Section title="Non-Current Assets" items={bs.assets.non_current} total={Object.values(bs.assets.non_current).reduce((a, b) => a + b, 0)} />
              <div className="flex justify-between font-bold text-base border-t-2 border-blue-300 pt-2 text-blue-800">
                <span>Total Assets</span>
                <span className="font-mono">{formatINR(bs.assets.total)}</span>
              </div>
            </div>

            {/* Liabilities + Equity */}
            <div className="space-y-4">
              <Section title="Current Liabilities"     items={bs.liabilities.current}     total={Object.values(bs.liabilities.current).reduce((a, b) => a + b, 0)}     />
              <Section title="Non-Current Liabilities" items={bs.liabilities.non_current} total={Object.values(bs.liabilities.non_current).reduce((a, b) => a + b, 0)} />
              <Section title="Equity"                  items={bs.equity.breakdown}         total={bs.equity.total}                                                        />
              <div className="flex justify-between font-bold text-base border-t-2 border-blue-300 pt-2 text-blue-800">
                <span>Liabilities + Equity</span>
                <span className="font-mono">{formatINR(bs.liabilities.total + bs.equity.total)}</span>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Cash Flow */}
      {!loading && tab === 'cf' && cf && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
          <div>
            <h2 className="font-bold text-slate-900">Cash Flow Statement</h2>
            <p className="text-xs text-slate-500 mt-0.5">{formatDate(cf.period.from)} — {formatDate(cf.period.to)}</p>
          </div>

          <div className="space-y-3">
            {[
              { label: 'Operating Activities',  value: cf.operating,  color: cf.operating  >= 0 ? 'text-green-700' : 'text-red-700' },
              { label: 'Investing Activities',  value: cf.investing,  color: cf.investing  >= 0 ? 'text-green-700' : 'text-red-700' },
              { label: 'Financing Activities',  value: cf.financing,  color: cf.financing  >= 0 ? 'text-green-700' : 'text-red-700' },
            ].map(r => (
              <div key={r.label} className="flex justify-between text-sm items-center py-2 border-b border-slate-100">
                <span className="text-slate-700">{r.label}</span>
                <span className={`font-mono font-semibold ${r.color}`}>{formatINR(r.value)}</span>
              </div>
            ))}
            <div className="flex justify-between text-sm font-bold">
              <span className="text-slate-900">Net Cash Change</span>
              <span className={`font-mono text-base ${cf.net_change >= 0 ? 'text-green-700' : 'text-red-700'}`}>
                {formatINR(cf.net_change)}
              </span>
            </div>
          </div>

          <div className="bg-slate-50 rounded-lg p-4 grid grid-cols-2 gap-4 text-sm">
            <div>
              <p className="text-xs text-slate-500 mb-1">Opening Balance</p>
              <p className="font-mono font-bold text-slate-900">{formatINR(cf.opening_balance)}</p>
            </div>
            <div>
              <p className="text-xs text-slate-500 mb-1">Closing Balance</p>
              <p className="font-mono font-bold text-blue-700">{formatINR(cf.closing_balance)}</p>
            </div>
          </div>
        </div>
      )}

      {/* GST Summary */}
      {!loading && tab === 'gst' && gst && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
          <div>
            <h2 className="font-bold text-slate-900 flex items-center gap-2">
              <FileText size={16} className="text-blue-600" /> GST Summary
            </h2>
            <p className="text-xs text-slate-500 mt-0.5">{formatDate(gst.period.from)} — {formatDate(gst.period.to)}</p>
          </div>

          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {[
              { label: 'Taxable Value', value: gst.total_taxable_value, color: 'text-slate-900'  },
              { label: 'CGST',          value: gst.total_cgst,          color: 'text-blue-700'   },
              { label: 'SGST',          value: gst.total_sgst,          color: 'text-blue-700'   },
              { label: 'IGST',          value: gst.total_igst,          color: 'text-purple-700' },
            ].map(c => (
              <div key={c.label} className="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">{c.label}</p>
                <p className={`font-mono font-bold ${c.color}`}>{formatINR(c.value)}</p>
              </div>
            ))}
          </div>

          <div className="space-y-2 border-t border-slate-200 pt-4">
            <div className="flex justify-between text-sm">
              <span className="text-slate-600">Total GST Collected</span>
              <span className="font-mono font-semibold text-slate-900">{formatINR(gst.total_gst)}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-slate-600">Input Tax Credit</span>
              <span className="font-mono font-semibold text-green-700">({formatINR(gst.input_credit)})</span>
            </div>
            <div className="flex justify-between text-base font-bold border-t border-slate-300 pt-2">
              <span className="text-slate-900">Net GST Payable</span>
              <span className={`font-mono ${gst.net_payable >= 0 ? 'text-red-700' : 'text-green-700'}`}>
                {formatINR(gst.net_payable)}
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
