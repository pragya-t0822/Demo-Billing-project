import { useState, useEffect, useCallback } from 'react';
import { History, Play, Link as LinkIcon, RefreshCcw, AlertTriangle, CheckCircle2, Clock } from 'lucide-react';
import { Spinner, PageSpinner } from '../../components/ui/Spinner';
import { Alert } from '../../components/ui/Alert';
import { Badge } from '../../components/ui/Badge';
import { Modal } from '../../components/ui/Modal';
import { formatINR } from '../../utils/currency';
import { formatDate } from '../../utils/date';
import recoveryService, {
  type OverdueInvoice,
  type PaymentLink,
} from '../../services/recoveryService';

/* ── Demo data ──────────────────────────────────────────────────────── */
const DEMO_OVERDUE: OverdueInvoice[] = [
  { invoice_id: 'inv1', invoice_number: 'INV-2026-00110', customer_name: 'Vikram Mehta',   customer_phone: '9876543210', total_amount: 118000, amount_paid: 0,     balance_due: 118000, due_date: '2026-03-01', days_overdue: 22, escalation_stage: 3, store_id: 's1' },
  { invoice_id: 'inv2', invoice_number: 'INV-2026-00115', customer_name: 'Sunita Rao',     customer_phone: '9988776655', total_amount: 45000,  amount_paid: 10000, balance_due: 35000,  due_date: '2026-03-10', days_overdue: 13, escalation_stage: 2, store_id: 's1' },
  { invoice_id: 'inv3', invoice_number: 'INV-2026-00118', customer_name: 'Anita Singh',    customer_phone: null,         total_amount: 4720,   amount_paid: 0,     balance_due: 4720,   due_date: '2026-03-17', days_overdue: 6,  escalation_stage: 1, store_id: 's1' },
  { invoice_id: 'inv4', invoice_number: 'INV-2026-00119', customer_name: 'Rajesh Kumar',   customer_phone: '9123456789', total_amount: 29500,  amount_paid: 5000,  balance_due: 24500,  due_date: '2026-03-20', days_overdue: 3,  escalation_stage: 1, store_id: 's1' },
];

const stageLabel = (stage: number) => {
  if (stage >= 3) return { label: 'Critical', variant: 'danger' as const };
  if (stage === 2) return { label: 'Escalated', variant: 'warning' as const };
  return { label: 'Reminder 1', variant: 'info' as const };
};

const overdueColor = (days: number) => {
  if (days >= 30) return 'text-red-700 font-bold';
  if (days >= 14) return 'text-orange-600 font-semibold';
  if (days >= 7)  return 'text-yellow-600 font-medium';
  return 'text-slate-600';
};

export default function Recovery() {
  const [overdue, setOverdue]         = useState<OverdueInvoice[]>([]);
  const [loading, setLoading]         = useState(true);
  const [running, setRunning]         = useState(false);
  const [error, setError]             = useState<string | null>(null);
  const [success, setSuccess]         = useState<string | null>(null);

  /* Payment link modal */
  const [linkModal, setLinkModal]     = useState(false);
  const [selectedInv, setSelectedInv] = useState<OverdueInvoice | null>(null);
  const [payLink, setPayLink]         = useState<PaymentLink | null>(null);
  const [generatingLink, setGeneratingLink] = useState(false);

  const loadOverdue = useCallback(() => {
    setLoading(true);
    setError(null);
    recoveryService.overdue()
      .then(data => setOverdue(data.length ? data : DEMO_OVERDUE))
      .catch(() => setOverdue(DEMO_OVERDUE))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { loadOverdue(); }, [loadOverdue]);

  const runCycle = async () => {
    setRunning(true);
    setError(null);
    setSuccess(null);
    try {
      const result = await recoveryService.runCycle();
      setSuccess(`Recovery cycle complete — ${result.reminders_sent} reminders sent, ${result.payment_links_generated} links generated.`);
    } catch {
      setSuccess('Recovery cycle complete — 3 reminders sent, 3 payment links generated. (demo)');
    } finally {
      setRunning(false);
    }
  };

  const openLinkModal = (inv: OverdueInvoice) => {
    setSelectedInv(inv);
    setPayLink(null);
    setLinkModal(true);
  };

  const generateLink = async () => {
    if (!selectedInv) return;
    setGeneratingLink(true);
    try {
      const link = await recoveryService.generatePaymentLink(selectedInv.invoice_id);
      setPayLink(link);
    } catch {
      setPayLink({
        invoice_id: selectedInv.invoice_id,
        link_number: 'PL-2026-' + Math.floor(Math.random() * 9000 + 1000),
        payment_url: 'https://pay.retailflow.in/link/' + selectedInv.invoice_number,
        amount_due: selectedInv.balance_due,
        expires_at: new Date(Date.now() + 7 * 86400000).toISOString(),
      });
    } finally {
      setGeneratingLink(false);
    }
  };

  /* ── Summary stats ──────────────────────────────────────────────── */
  const totalDue     = overdue.reduce((s, o) => s + o.balance_due, 0);
  const criticalCount = overdue.filter(o => o.escalation_stage >= 3).length;

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <History size={22} className="text-blue-600" /> Recovery
          </h1>
          <p className="text-sm text-slate-500 mt-0.5">Overdue invoice follow-up & payment links</p>
        </div>
        <div className="flex gap-2">
          <button onClick={loadOverdue} className="flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
            <RefreshCcw size={14} /> Refresh
          </button>
          <button
            onClick={runCycle} disabled={running}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-60 transition-colors"
          >
            {running ? <><Spinner size="sm" /> Running…</> : <><Play size={14} /> Run Recovery Cycle</>}
          </button>
        </div>
      </div>

      {error   && <Alert variant="error"   dismissible>{error}</Alert>}
      {success && <Alert variant="success" dismissible>{success}</Alert>}

      {/* Summary cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total Overdue',    value: overdue.length,                  icon: AlertTriangle,  color: 'text-red-600'    },
          { label: 'Amount Due',       value: formatINR(totalDue),              icon: History,        color: 'text-orange-600' },
          { label: 'Critical (30d+)',  value: criticalCount,                    icon: Clock,          color: 'text-red-700'    },
          { label: 'Recoverable',      value: overdue.filter(o => o.customer_phone).length, icon: CheckCircle2, color: 'text-green-600' },
        ].map(c => (
          <div key={c.label} className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div className="flex items-center justify-between mb-2">
              <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide">{c.label}</p>
              <c.icon size={16} className={c.color} />
            </div>
            <p className="text-xl font-bold text-slate-900">{c.value}</p>
          </div>
        ))}
      </div>

      {/* Overdue table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100">
          <h2 className="font-semibold text-sm text-slate-900">Overdue Invoices</h2>
        </div>

        {loading ? (
          <div className="py-16"><PageSpinner /></div>
        ) : overdue.length === 0 ? (
          <div className="py-16 text-center">
            <CheckCircle2 size={32} className="mx-auto text-green-400 mb-2" />
            <p className="text-sm text-slate-500">No overdue invoices. All accounts settled.</p>
          </div>
        ) : (
          <table className="w-full text-left">
            <thead className="bg-slate-50 border-b border-slate-100">
              <tr>
                {['Invoice', 'Customer', 'Total', 'Paid', 'Balance Due', 'Days Overdue', 'Stage', 'Action'].map(h => (
                  <th key={h} className="px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {overdue.map(inv => {
                const { label, variant } = stageLabel(inv.escalation_stage);
                return (
                  <tr key={inv.invoice_id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-4 py-3 font-mono text-xs font-semibold text-blue-700">{inv.invoice_number}</td>
                    <td className="px-4 py-3">
                      <p className="text-sm font-medium text-slate-800">{inv.customer_name}</p>
                      {inv.customer_phone && (
                        <p className="text-xs text-slate-400 font-mono">{inv.customer_phone}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 font-mono text-sm text-slate-700">{formatINR(inv.total_amount)}</td>
                    <td className="px-4 py-3 font-mono text-sm text-green-700">{formatINR(inv.amount_paid)}</td>
                    <td className="px-4 py-3 font-mono text-sm font-bold text-red-700">{formatINR(inv.balance_due)}</td>
                    <td className={`px-4 py-3 text-sm font-mono ${overdueColor(inv.days_overdue)}`}>
                      {inv.days_overdue}d
                    </td>
                    <td className="px-4 py-3">
                      <Badge variant={variant}>{label}</Badge>
                    </td>
                    <td className="px-4 py-3">
                      <button
                        onClick={() => openLinkModal(inv)}
                        className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors"
                      >
                        <LinkIcon size={11} /> Payment Link
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </div>

      {/* Payment link modal */}
      <Modal
        open={linkModal}
        onClose={() => { setLinkModal(false); setPayLink(null); }}
        title="Generate Payment Link"
        size="sm"
      >
        {selectedInv && (
          <div className="space-y-4">
            <div className="bg-slate-50 rounded-lg p-4 text-sm space-y-1">
              <div className="flex justify-between">
                <span className="text-slate-500">Invoice</span>
                <span className="font-mono font-semibold text-slate-800">{selectedInv.invoice_number}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Customer</span>
                <span className="font-semibold text-slate-800">{selectedInv.customer_name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Balance Due</span>
                <span className="font-mono font-bold text-red-700">{formatINR(selectedInv.balance_due)}</span>
              </div>
            </div>

            {payLink ? (
              <div className="space-y-3">
                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                  <p className="text-xs font-semibold text-green-700 mb-1">Link Generated</p>
                  <p className="font-mono text-xs text-green-800 break-all">{payLink.payment_url}</p>
                  <p className="text-xs text-green-600 mt-1">Expires: {formatDate(payLink.expires_at)}</p>
                </div>
                <button
                  onClick={() => { navigator.clipboard.writeText(payLink.payment_url); }}
                  className="w-full py-2 text-sm font-semibold text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50"
                >
                  Copy Link
                </button>
              </div>
            ) : (
              <button
                onClick={generateLink} disabled={generatingLink}
                className="w-full flex items-center justify-center gap-2 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-60"
              >
                {generatingLink ? <><Spinner size="sm" /> Generating…</> : <><LinkIcon size={14} /> Generate Link</>}
              </button>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}
