import { useState, useEffect, useCallback } from 'react';
import { Package, Search, AlertTriangle, RefreshCcw, Scale, TrendingUp } from 'lucide-react';
import { PageSpinner } from '../../components/ui/Spinner';
import { Alert } from '../../components/ui/Alert';
import { Badge } from '../../components/ui/Badge';
import { formatINR, formatGrams } from '../../utils/currency';
import { formatDate } from '../../utils/date';
import inventoryService, { type StockLevel, type MetalRate, type MetalType } from '../../services/inventoryService';

/* ── Demo data ──────────────────────────────────────────────────────── */
const DEMO_STOCK: StockLevel[] = [
  { sku: 'GLD-RING-001',  product_name: 'Gold Ring 22K',      store_id: 's1', quantity: 12,  weight_grams: 45.750,  reorder_level: 5,  is_weight_based: true  },
  { sku: 'GLD-CHAIN-002', product_name: 'Gold Chain 18K 20"', store_id: 's1', quantity: 8,   weight_grams: 124.250, reorder_level: 3,  is_weight_based: true  },
  { sku: 'SLV-BANGLE-001',product_name: 'Silver Bangle Set',  store_id: 's1', quantity: 24,  weight_grams: 380.000, reorder_level: 10, is_weight_based: true  },
  { sku: 'GLD-EARRING-003',product_name: 'Gold Earrings 22K', store_id: 's1', quantity: 3,   weight_grams: 12.100,  reorder_level: 5,  is_weight_based: true  },
  { sku: 'ACC-BOX-001',   product_name: 'Jewellery Gift Box', store_id: 's1', quantity: 45,  weight_grams: null,    reorder_level: 20, is_weight_based: false },
  { sku: 'ACC-CLEAN-001', product_name: 'Cleaning Cloth',     store_id: 's1', quantity: 2,   weight_grams: null,    reorder_level: 10, is_weight_based: false },
];

const DEMO_RATES: Record<MetalType, MetalRate> = {
  GOLD:     { metal_type: 'GOLD',     rate_per_gram: 6850.00, updated_at: '2026-03-23T09:00:00Z' },
  SILVER:   { metal_type: 'SILVER',   rate_per_gram: 84.50,   updated_at: '2026-03-23T09:00:00Z' },
  PLATINUM: { metal_type: 'PLATINUM', rate_per_gram: 2950.00, updated_at: '2026-03-23T09:00:00Z' },
};

type Tab = 'stock' | 'rates' | 'calculator';

export default function Inventory() {
  const [tab, setTab]             = useState<Tab>('stock');
  const [stock, setStock]         = useState<StockLevel[]>([]);
  const [rates, setRates]         = useState<Record<string, MetalRate>>({});
  const [loading, setLoading]     = useState(true);
  const [error, setError]         = useState<string | null>(null);
  const [search, setSearch]       = useState('');

  /* Weight calculator state */
  const [calcMetal, setCalcMetal] = useState<MetalType>('GOLD');
  const [calcWeight, setCalcWeight] = useState('');
  const [calcMaking, setCalcMaking] = useState('12');
  const [calcResult, setCalcResult] = useState<{ base: number; making: number; gst: number; total: number } | null>(null);

  const loadStock = useCallback(() => {
    setLoading(true);
    setError(null);
    inventoryService.lowStock()
      .then(data => setStock(data.length ? data : DEMO_STOCK))
      .catch(() => setStock(DEMO_STOCK))
      .finally(() => setLoading(false));
  }, []);

  const loadRates = useCallback(() => {
    Promise.all([
      inventoryService.getMetalRate('GOLD').catch(() => DEMO_RATES.GOLD),
      inventoryService.getMetalRate('SILVER').catch(() => DEMO_RATES.SILVER),
      inventoryService.getMetalRate('PLATINUM').catch(() => DEMO_RATES.PLATINUM),
    ]).then(([gold, silver, platinum]) => {
      setRates({ GOLD: gold, SILVER: silver, PLATINUM: platinum });
    });
  }, []);

  useEffect(() => { loadStock(); }, [loadStock]);
  useEffect(() => { if (tab === 'rates') loadRates(); }, [tab, loadRates]);

  const filtered = stock.filter(
    s => s.product_name.toLowerCase().includes(search.toLowerCase()) ||
         s.sku.toLowerCase().includes(search.toLowerCase()),
  );

  const lowStockCount = stock.filter(s => s.quantity <= s.reorder_level).length;

  /* Weight calculator */
  const calcPrice = () => {
    const w   = parseFloat(calcWeight) || 0;
    const mkp = parseFloat(calcMaking) || 0;
    const r   = rates[calcMetal]?.rate_per_gram ?? DEMO_RATES[calcMetal].rate_per_gram;
    const base    = w * r;
    const making  = base * (mkp / 100);
    const taxable = base + making;
    const gst     = taxable * 0.03; // 3% GST on gold jewellery
    setCalcResult({ base, making, gst, total: taxable + gst });
  };

  /* ── Render ─────────────────────────────────────────────────────── */
  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <Package size={22} className="text-blue-600" /> Inventory
          </h1>
          <p className="text-sm text-slate-500 mt-0.5">Stock levels · metal rates · weight pricing</p>
        </div>
        {lowStockCount > 0 && (
          <div className="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm font-medium">
            <AlertTriangle size={15} />
            {lowStockCount} item{lowStockCount > 1 ? 's' : ''} below reorder level
          </div>
        )}
      </div>

      {error && <Alert variant="error" dismissible>{error}</Alert>}

      {/* Tabs */}
      <div className="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
        {(['stock', 'rates', 'calculator'] as Tab[]).map(t => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-1.5 text-sm font-medium rounded-lg transition-all capitalize ${
              tab === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            {t === 'calculator' ? 'Weight Calculator' : t === 'rates' ? 'Metal Rates' : 'Stock'}
          </button>
        ))}
      </div>

      {/* Stock table */}
      {tab === 'stock' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
            <div className="relative w-72">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="search" value={search} onChange={e => setSearch(e.target.value)}
                placeholder="Search by name or SKU…"
                className="w-full pl-9 pr-4 py-1.5 text-sm bg-slate-100 rounded-full border border-transparent focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none"
              />
            </div>
            <button onClick={loadStock} className="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500">
              <RefreshCcw size={14} />
            </button>
          </div>

          {loading ? (
            <div className="py-16"><PageSpinner /></div>
          ) : filtered.length === 0 ? (
            <div className="py-16 text-center text-sm text-slate-400">No stock items found.</div>
          ) : (
            <table className="w-full text-left">
              <thead className="bg-slate-50 border-b border-slate-100">
                <tr>
                  {['SKU', 'Product Name', 'Type', 'Qty / Weight', 'Reorder Level', 'Status'].map(h => (
                    <th key={h} className="px-6 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filtered.map(item => {
                  const isLow = item.quantity <= item.reorder_level;
                  return (
                    <tr key={item.sku} className={`hover:bg-slate-50 transition-colors ${isLow ? 'bg-red-50/40' : ''}`}>
                      <td className="px-6 py-3 font-mono text-xs font-semibold text-blue-700">{item.sku}</td>
                      <td className="px-6 py-3 text-sm text-slate-800 font-medium">{item.product_name}</td>
                      <td className="px-6 py-3">
                        <Badge variant={item.is_weight_based ? 'warning' : 'neutral'}>
                          {item.is_weight_based ? 'Weight' : 'Unit'}
                        </Badge>
                      </td>
                      <td className="px-6 py-3 font-mono text-sm text-slate-900">
                        {item.is_weight_based && item.weight_grams != null
                          ? formatGrams(item.weight_grams)
                          : `${item.quantity} pcs`}
                      </td>
                      <td className="px-6 py-3 font-mono text-sm text-slate-500">{item.reorder_level}</td>
                      <td className="px-6 py-3">
                        {isLow
                          ? <Badge variant="danger"><AlertTriangle size={10} className="inline mr-1" />Low Stock</Badge>
                          : <Badge variant="success">OK</Badge>}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* Metal rates */}
      {tab === 'rates' && (
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {(['GOLD', 'SILVER', 'PLATINUM'] as MetalType[]).map(metal => {
            const r = rates[metal] ?? DEMO_RATES[metal];
            return (
              <div key={metal} className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-2">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold ${
                      metal === 'GOLD' ? 'bg-yellow-500' : metal === 'SILVER' ? 'bg-slate-400' : 'bg-cyan-600'
                    }`}>
                      {metal[0]}
                    </div>
                    <span className="font-semibold text-slate-900 capitalize">{metal.toLowerCase()}</span>
                  </div>
                  <TrendingUp size={16} className="text-slate-400" />
                </div>
                <p className="text-2xl font-bold font-mono text-slate-900">{formatINR(r.rate_per_gram)}</p>
                <p className="text-xs text-slate-500 mt-1">per gram · updated {formatDate(r.updated_at)}</p>
              </div>
            );
          })}
        </div>
      )}

      {/* Weight calculator */}
      {tab === 'calculator' && (
        <div className="max-w-md bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
          <div className="flex items-center gap-2 mb-2">
            <Scale size={18} className="text-blue-600" />
            <h2 className="font-semibold text-slate-900">Jewellery Weight Pricing</h2>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Metal</label>
            <select
              value={calcMetal} onChange={e => setCalcMetal(e.target.value as MetalType)}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="GOLD">Gold</option>
              <option value="SILVER">Silver</option>
              <option value="PLATINUM">Platinum</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Weight (grams)</label>
            <input
              type="number" step="0.001" min="0" value={calcWeight}
              onChange={e => setCalcWeight(e.target.value)}
              placeholder="e.g. 10.250"
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Making Charges (%)</label>
            <input
              type="number" step="0.5" min="0" max="100" value={calcMaking}
              onChange={e => setCalcMaking(e.target.value)}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <button
            onClick={calcPrice}
            className="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors"
          >
            Calculate Price
          </button>

          {calcResult && (
            <div className="bg-slate-50 rounded-lg p-4 space-y-2 text-sm border border-slate-200">
              <div className="flex justify-between">
                <span className="text-slate-600">Metal value</span>
                <span className="font-mono font-semibold text-slate-900">{formatINR(calcResult.base)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-600">Making charges</span>
                <span className="font-mono font-semibold text-slate-900">{formatINR(calcResult.making)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-600">GST (3%)</span>
                <span className="font-mono font-semibold text-slate-900">{formatINR(calcResult.gst)}</span>
              </div>
              <div className="flex justify-between border-t border-slate-300 pt-2 font-bold text-base">
                <span className="text-slate-900">Total</span>
                <span className="font-mono text-blue-700">{formatINR(calcResult.total)}</span>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
