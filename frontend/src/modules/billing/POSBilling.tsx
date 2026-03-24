import { useState, useEffect, useRef, useCallback } from 'react';
import {
  Search, Plus, Minus, Trash2, User, CreditCard,
  Receipt, CheckCircle2, Keyboard, Package,
} from 'lucide-react';
import { Button }           from '../../components/ui/Button';
import { Badge, statusVariant } from '../../components/ui/Badge';
import { Alert }            from '../../components/ui/Alert';
import { Spinner }          from '../../components/ui/Spinner';
import { Modal }            from '../../components/ui/Modal';
import { formatINR }        from '../../utils/currency';
import billingService       from '../../services/billingService';
import inventoryService     from '../../services/inventoryService';
import type { Invoice, LineItem } from '../../services/billingService';
import type { StockLevel }  from '../../services/inventoryService';

/* ── Types ───────────────────────────────────────────────────────────── */
interface CartItem {
  sku: string;
  name: string;
  unit_price: number;
  quantity: number;
  gst_rate: number;
  hsn_sac_code: string;
  is_weight_based: boolean;
}

interface CustomerInfo {
  name: string;
  phone: string;
  gstin: string;
}

const STORE_ID = '00000000-0000-0000-0000-000000000001'; // fallback default

/* ── Helpers ─────────────────────────────────────────────────────────── */
const calcLineTax = (item: CartItem) => {
  const base  = item.unit_price * item.quantity;
  const cgst  = +(base * (item.gst_rate / 2) / 100).toFixed(2);
  const sgst  = cgst;
  return { base, cgst, sgst, total: +(base + cgst + sgst).toFixed(2) };
};

/* ── Component ───────────────────────────────────────────────────────── */
export default function POSBilling() {
  const [products, setProducts]           = useState<StockLevel[]>([]);
  const [cart, setCart]                   = useState<CartItem[]>([]);
  const [search, setSearch]               = useState('');
  const [customer, setCustomer]           = useState<CustomerInfo>({ name: '', phone: '', gstin: '' });
  const [paymentMode, setPaymentMode]     = useState<'CASH' | 'CARD' | 'UPI' | 'CREDIT'>('CASH');
  const [loading, setLoading]             = useState(true);
  const [processing, setProcessing]       = useState(false);
  const [successInvoice, setSuccessInvoice] = useState<Invoice | null>(null);
  const [error, setError]                 = useState<string | null>(null);
  const [showCustomer, setShowCustomer]   = useState(false);
  const [recentInvoices, setRecentInvoices] = useState<Invoice[]>([]);
  const searchRef                         = useRef<HTMLInputElement>(null);

  /* load low-stock / product list */
  useEffect(() => {
    inventoryService.lowStock({ store_id: STORE_ID })
      .then(setProducts)
      .catch(() => {
        /* backend not yet running — use demo data */
        setProducts([
          { sku: 'GOLD-22K-001',   product_name: '22K Gold Chain 10g',      store_id: STORE_ID, quantity: 5,  weight_grams: 10,  reorder_level: 2,  is_weight_based: true  },
          { sku: 'SILV-BANGLE-01', product_name: 'Silver Bangle Set',        store_id: STORE_ID, quantity: 12, weight_grams: 50,  reorder_level: 5,  is_weight_based: true  },
          { sku: 'ELEC-WH-001',    product_name: 'Wireless ANC Headphones',  store_id: STORE_ID, quantity: 45, weight_grams: null, reorder_level: 10, is_weight_based: false },
          { sku: 'APP-TS-001',     product_name: 'Premium Cotton T-Shirt',   store_id: STORE_ID, quantity: 120,weight_grams: null, reorder_level: 20, is_weight_based: false },
          { sku: 'ELEC-SW-003',    product_name: 'Smart Fitness Watch',      store_id: STORE_ID, quantity: 12, weight_grams: null, reorder_level: 5,  is_weight_based: false },
          { sku: 'JEW-DR-002',     product_name: 'Diamond Solitaire Ring',   store_id: STORE_ID, quantity: 2,  weight_grams: 3.5, reorder_level: 1,  is_weight_based: true  },
        ]);
      })
      .finally(() => setLoading(false));

    billingService.list({ per_page: 5 })
      .then(r => setRecentInvoices(r.data))
      .catch(() => {});
  }, []);

  /* keyboard shortcuts */
  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'F2')  { e.preventDefault(); searchRef.current?.focus(); }
      if (e.key === 'F4')  { e.preventDefault(); setShowCustomer(true); }
      if (e.key === 'F9')  { e.preventDefault(); cart.length && handleCheckout(); }
      if (e.key === 'Escape' && !showCustomer) { setSearch(''); }
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [cart, showCustomer]);

  const filtered = products.filter(p =>
    p.product_name.toLowerCase().includes(search.toLowerCase()) ||
    p.sku.toLowerCase().includes(search.toLowerCase())
  );

  const addToCart = useCallback((p: StockLevel) => {
    setCart(prev => {
      const existing = prev.find(c => c.sku === p.sku);
      if (existing) {
        return prev.map(c => c.sku === p.sku ? { ...c, quantity: c.quantity + 1 } : c);
      }
      const gstRate = p.is_weight_based ? 3 : 18;
      return [...prev, {
        sku: p.sku, name: p.product_name,
        unit_price: p.is_weight_based ? 7200 * (p.weight_grams ?? 1) : 1000,
        quantity: 1, gst_rate: gstRate, hsn_sac_code: '7113',
        is_weight_based: p.is_weight_based,
      }];
    });
  }, []);

  const updateQty = (sku: string, delta: number) => {
    setCart(prev => prev
      .map(c => c.sku === sku ? { ...c, quantity: Math.max(0, c.quantity + delta) } : c)
      .filter(c => c.quantity > 0)
    );
  };

  /* totals */
  const totals = cart.reduce((acc, item) => {
    const { base, cgst, sgst, total } = calcLineTax(item);
    return { subtotal: acc.subtotal + base, cgst: acc.cgst + cgst, sgst: acc.sgst + sgst, total: acc.total + total };
  }, { subtotal: 0, cgst: 0, sgst: 0, total: 0 });

  const handleCheckout = async () => {
    if (!cart.length) return;
    if (!customer.name) { setShowCustomer(true); return; }
    setProcessing(true); setError(null);
    try {
      const lineItems: LineItem[] = cart.map(c => ({
        sku: c.sku, quantity: c.quantity, unit_price: c.unit_price,
        hsn_sac_code: c.hsn_sac_code, gst_rate: c.gst_rate,
      }));
      const draft = await billingService.create({
        store_id: STORE_ID, customer_name: customer.name,
        customer_phone: customer.phone || undefined,
        customer_gstin: customer.gstin || undefined,
        line_items: lineItems, payment_mode: paymentMode,
      });
      const confirmed = await billingService.confirm(draft.id);
      const paid = await billingService.processPayment(confirmed.id, {
        amount_paid: totals.total, payment_mode: paymentMode,
      });
      setSuccessInvoice(paid);
      setCart([]); setCustomer({ name: '', phone: '', gstin: '' });
      setRecentInvoices(prev => [paid, ...prev.slice(0, 4)]);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Checkout failed. Please try again.';
      setError(msg);
    } finally {
      setProcessing(false);
    }
  };

  const paymentModes: Array<{ value: typeof paymentMode; label: string; icon: React.ElementType }> = [
    { value: 'CASH',   label: 'Cash',   icon: Receipt    },
    { value: 'CARD',   label: 'Card',   icon: CreditCard },
    { value: 'UPI',    label: 'UPI',    icon: CheckCircle2 },
    { value: 'CREDIT', label: 'Credit', icon: User       },
  ];

  return (
    <div className="h-[calc(100vh-3.5rem-3rem)] flex gap-4 max-w-full">

      {/* ── Left: products ─────────────────────────────────────────── */}
      <div className="flex-1 flex flex-col min-w-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        {/* search bar */}
        <div className="p-4 border-b border-slate-100">
          <div className="relative">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              ref={searchRef} type="search" placeholder="Search by SKU or name… [F2]"
              value={search} onChange={e => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        {/* product grid */}
        <div className="flex-1 overflow-y-auto p-4">
          {loading ? (
            <div className="flex h-full items-center justify-center"><Spinner size="lg" /></div>
          ) : filtered.length === 0 ? (
            <div className="flex h-full flex-col items-center justify-center text-slate-400 gap-2">
              <Package size={36} />
              <p className="text-sm">No products found</p>
            </div>
          ) : (
            <div className="grid grid-cols-2 xl:grid-cols-3 gap-3">
              {filtered.map(p => (
                <button
                  key={p.sku}
                  onClick={() => addToCart(p)}
                  className="text-left p-4 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50 transition-all group active:scale-95"
                >
                  <p className="font-semibold text-sm text-slate-800 leading-tight group-hover:text-blue-700 line-clamp-2">
                    {p.product_name}
                  </p>
                  <p className="text-[11px] text-slate-400 font-mono mt-1">{p.sku}</p>
                  <div className="flex items-center justify-between mt-2">
                    <span className="text-xs font-medium text-slate-500">
                      {p.is_weight_based ? `${p.weight_grams?.toFixed(3)} g` : `Qty: ${p.quantity}`}
                    </span>
                    <Badge variant={p.quantity > 0 ? 'success' : 'danger'}>
                      {p.quantity > 0 ? 'In Stock' : 'Out'}
                    </Badge>
                  </div>
                </button>
              ))}
            </div>
          )}
        </div>

        {/* keyboard hint */}
        <div className="px-4 py-2 border-t border-slate-100 bg-slate-50 flex items-center gap-1 text-[11px] text-slate-400">
          <Keyboard size={12} className="mr-1" />
          {[['F2','Search'],['F4','Customer'],['F9','Checkout'],['Esc','Clear']].map(([k,l]) => (
            <span key={k} className="flex items-center gap-1 mr-3">
              <kbd className="bg-white border border-slate-200 rounded px-1 font-mono text-[10px]">{k}</kbd>
              <span>{l}</span>
            </span>
          ))}
        </div>
      </div>

      {/* ── Right: cart & payment ───────────────────────────────────── */}
      <div className="w-80 xl:w-96 flex flex-col gap-3 shrink-0">

        {/* customer strip */}
        <div
          onClick={() => setShowCustomer(true)}
          className="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 flex items-center gap-3 cursor-pointer hover:border-blue-300 transition-colors"
        >
          <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
            <User size={16} />
          </div>
          <div className="flex-1 min-w-0">
            {customer.name
              ? <p className="text-sm font-semibold text-slate-800 truncate">{customer.name}</p>
              : <p className="text-sm text-slate-400">Add customer… [F4]</p>}
            {customer.phone && <p className="text-xs text-slate-400 truncate">{customer.phone}</p>}
          </div>
          <Plus size={16} className="text-slate-400 shrink-0" />
        </div>

        {/* cart */}
        <div className="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
          <div className="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h3 className="font-semibold text-sm text-slate-800">Current Order</h3>
            {cart.length > 0 && (
              <button onClick={() => setCart([])} className="text-xs text-red-500 hover:text-red-700 font-medium">
                Clear all
              </button>
            )}
          </div>

          <div className="flex-1 overflow-y-auto">
            {cart.length === 0 ? (
              <div className="flex h-full flex-col items-center justify-center text-slate-400 gap-2 py-12">
                <Receipt size={28} />
                <p className="text-sm">Cart is empty</p>
                <p className="text-xs">Click a product to add</p>
              </div>
            ) : (
              <div className="divide-y divide-slate-100">
                {cart.map(item => {
                  const { total } = calcLineTax(item);
                  return (
                    <div key={item.sku} className="px-4 py-3 flex items-center gap-3">
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-slate-800 truncate">{item.name}</p>
                        <p className="text-xs text-slate-400 font-mono">{item.sku}</p>
                        <p className="text-xs text-slate-500 mt-0.5">GST {item.gst_rate}% (CGST+SGST)</p>
                      </div>
                      <div className="flex flex-col items-end gap-1.5 shrink-0">
                        <p className="text-sm font-semibold font-mono text-slate-900">{formatINR(total)}</p>
                        <div className="flex items-center gap-1">
                          <button onClick={() => updateQty(item.sku, -1)} className="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                            <Minus size={12} />
                          </button>
                          <span className="text-sm font-semibold w-6 text-center">{item.quantity}</span>
                          <button onClick={() => updateQty(item.sku, +1)} className="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                            <Plus size={12} />
                          </button>
                          <button onClick={() => updateQty(item.sku, -item.quantity)} className="w-6 h-6 rounded-md hover:bg-red-50 text-slate-300 hover:text-red-500 flex items-center justify-center transition-colors">
                            <Trash2 size={12} />
                          </button>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* GST breakdown */}
          {cart.length > 0 && (
            <div className="border-t border-slate-100 px-4 py-3 bg-slate-50 space-y-1.5 text-sm">
              <div className="flex justify-between text-slate-500">
                <span>Subtotal</span>
                <span className="font-mono">{formatINR(totals.subtotal)}</span>
              </div>
              <div className="flex justify-between text-slate-500">
                <span>CGST</span>
                <span className="font-mono">{formatINR(totals.cgst)}</span>
              </div>
              <div className="flex justify-between text-slate-500">
                <span>SGST</span>
                <span className="font-mono">{formatINR(totals.sgst)}</span>
              </div>
              <div className="flex justify-between font-bold text-slate-900 text-base border-t border-slate-200 pt-1.5 mt-1">
                <span>Total</span>
                <span className="font-mono text-blue-700">{formatINR(totals.total)}</span>
              </div>
            </div>
          )}
        </div>

        {/* payment mode */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-3">
          <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Payment Mode</p>
          <div className="grid grid-cols-4 gap-1.5">
            {paymentModes.map(({ value, label, icon: Icon }) => (
              <button
                key={value}
                onClick={() => setPaymentMode(value)}
                className={`flex flex-col items-center gap-1 py-2 rounded-lg border text-xs font-semibold transition-all
                  ${paymentMode === value ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500 hover:border-slate-300'}`}
              >
                <Icon size={16} />
                {label}
              </button>
            ))}
          </div>
        </div>

        {error && <Alert variant="error" dismissible>{error}</Alert>}
        {successInvoice && (
          <Alert variant="success" dismissible>
            Invoice <span className="font-mono font-bold">{successInvoice.invoice_number}</span> created — {formatINR(successInvoice.total_amount)}
          </Alert>
        )}

        <Button
          onClick={handleCheckout}
          isLoading={processing}
          disabled={cart.length === 0}
          size="lg"
          className="w-full shadow-lg"
        >
          {cart.length === 0 ? 'Add items to checkout' : `Charge ${formatINR(totals.total)}  [F9]`}
        </Button>
      </div>

      {/* ── Customer modal ─────────────────────────────────────────── */}
      <Modal
        open={showCustomer} onClose={() => setShowCustomer(false)}
        title="Customer Details" size="sm"
        footer={
          <>
            <Button variant="outline" size="sm" onClick={() => setShowCustomer(false)}>Cancel</Button>
            <Button size="sm" onClick={() => setShowCustomer(false)}>Save</Button>
          </>
        }
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Name <span className="text-red-500">*</span></label>
            <input value={customer.name} onChange={e => setCustomer(p => ({ ...p, name: e.target.value }))}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Customer name" />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Phone</label>
            <input value={customer.phone} onChange={e => setCustomer(p => ({ ...p, phone: e.target.value }))}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="+91 98765 43210" />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">GSTIN</label>
            <input value={customer.gstin} onChange={e => setCustomer(p => ({ ...p, gstin: e.target.value }))}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="27AAPFU0939F1ZV" />
          </div>
        </div>
      </Modal>

      {/* ── Recent invoices (bottom sheet-style row) — shown on wider screens */}
      {recentInvoices.length > 0 && (
        <div className="hidden 2xl:flex flex-col gap-2 w-64 shrink-0">
          <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide px-1">Recent</p>
          {recentInvoices.map(inv => (
            <div key={inv.id} className="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
              <div className="flex items-center justify-between mb-1">
                <span className="font-mono text-xs text-slate-500">{inv.invoice_number}</span>
                <Badge variant={statusVariant(inv.status)}>{inv.status}</Badge>
              </div>
              <p className="text-sm font-semibold text-slate-800 truncate">{inv.customer_name}</p>
              <p className="text-sm font-mono font-bold text-blue-700 mt-0.5">{formatINR(inv.total_amount)}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
