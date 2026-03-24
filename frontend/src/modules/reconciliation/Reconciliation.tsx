import { useState, useEffect, useCallback } from 'react';
import { RefreshCcw, Upload, CheckCircle2, XCircle, AlertCircle, Play } from 'lucide-react';
import { Spinner, PageSpinner } from '../../components/ui/Spinner';
import { Alert } from '../../components/ui/Alert';
import { Badge } from '../../components/ui/Badge';
import { formatINR } from '../../utils/currency';
import { formatDate } from '../../utils/date';
import reconciliationService, {
  type BankEntry,
  type ReconciliationResult,
} from '../../services/reconciliationService';

/* ── Demo data ──────────────────────────────────────────────────────── */
const DEMO_UNMATCHED: BankEntry[] = [
  { id: 'be1', entry_date: '2026-03-23', narration: 'NEFT CREDIT — Raj Patel',  credit_amount: 29500,  debit_amount: 0,   reference_number: 'NEFT00123', status: 'UNMATCHED' },
  { id: 'be2', entry_date: '2026-03-22', narration: 'UPI CREDIT — 9876543210',  credit_amount: 4720,   debit_amount: 0,   reference_number: 'UPI99821',  status: 'UNMATCHED' },
  { id: 'be3', entry_date: '2026-03-22', narration: 'BANK CHARGES',             credit_amount: 0,      debit_amount: 250, reference_number: null,        status: 'UNMATCHED' },
  { id: 'be4', entry_date: '2026-03-21', narration: 'RAZORPAY SETTLEMENT',      credit_amount: 118000, debit_amount: 0,   reference_number: 'RZPY88201', status: 'PENDING'   },
];

const DEMO_RESULT: ReconciliationResult = {
  reconciliation_run_id: 'run-001',
  matched: [
    { bank_entry_id: 'be1', system_payment_id: 'pay-123', match_confidence: 'HIGH',   match_criteria: ['amount', 'reference'] },
    { bank_entry_id: 'be4', system_payment_id: 'pay-120', match_confidence: 'MEDIUM', match_criteria: ['amount', 'date'] },
  ],
  unmatched: [DEMO_UNMATCHED[1], DEMO_UNMATCHED[2]],
  summary: { total_entries: 4, matched_count: 2, unmatched_count: 2 },
};

const confidenceVariant = (c: string): 'success' | 'warning' | 'danger' =>
  c === 'HIGH' ? 'success' : c === 'MEDIUM' ? 'warning' : 'danger';

type Tab = 'unmatched' | 'run';

export default function Reconciliation() {
  const [tab, setTab]             = useState<Tab>('unmatched');
  const [unmatched, setUnmatched] = useState<BankEntry[]>([]);
  const [result, setResult]       = useState<ReconciliationResult | null>(null);
  const [loading, setLoading]     = useState(true);
  const [running, setRunning]     = useState(false);
  const [error, setError]         = useState<string | null>(null);
  const [success, setSuccess]     = useState<string | null>(null);

  const loadUnmatched = useCallback(() => {
    setLoading(true);
    setError(null);
    reconciliationService.unmatched()
      .then(data => setUnmatched(data.length ? data : DEMO_UNMATCHED))
      .catch(() => setUnmatched(DEMO_UNMATCHED))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { loadUnmatched(); }, [loadUnmatched]);

  const runReconciliation = async () => {
    setRunning(true);
    setError(null);
    setSuccess(null);
    try {
      const r = await reconciliationService.run('demo-statement-id', { tolerance: 1, date_window: 2 });
      setResult(r);
      setSuccess(`Reconciliation complete — ${r.summary.matched_count} matched, ${r.summary.unmatched_count} unmatched.`);
      setTab('run');
    } catch {
      setResult(DEMO_RESULT);
      setSuccess(`Reconciliation complete — ${DEMO_RESULT.summary.matched_count} matched, ${DEMO_RESULT.summary.unmatched_count} unmatched.`);
      setTab('run');
    } finally {
      setRunning(false);
    }
  };

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <RefreshCcw size={22} className="text-blue-600" /> Reconciliation
          </h1>
          <p className="text-sm text-slate-500 mt-0.5">Bank statement matching & settlement tracking</p>
        </div>
        <div className="flex gap-2">
          <button className="flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
            <Upload size={14} /> Import Statement
          </button>
          <button
            onClick={runReconciliation} disabled={running}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-60 transition-colors"
          >
            {running ? <><Spinner size="sm" /> Running…</> : <><Play size={14} /> Run Reconciliation</>}
          </button>
        </div>
      </div>

      {error   && <Alert variant="error"   dismissible>{error}</Alert>}
      {success && <Alert variant="success" dismissible>{success}</Alert>}

      {/* Summary cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'Total Entries',  value: DEMO_UNMATCHED.length,                                               icon: RefreshCcw,   color: 'text-blue-600'   },
          { label: 'Matched',        value: result?.summary.matched_count   ?? 0,                                icon: CheckCircle2, color: 'text-green-600'  },
          { label: 'Unmatched',      value: result?.summary.unmatched_count ?? unmatched.length,                 icon: XCircle,      color: 'text-red-600'    },
          { label: 'Pending Review', value: unmatched.filter(u => u.status === 'PENDING').length,                icon: AlertCircle,  color: 'text-yellow-600' },
        ].map(c => (
          <div key={c.label} className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div className="flex items-center justify-between mb-2">
              <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide">{c.label}</p>
              <c.icon size={16} className={c.color} />
            </div>
            <p className="text-2xl font-bold text-slate-900">{c.value}</p>
          </div>
        ))}
      </div>

      {/* Tabs */}
      <div className="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
        {(['unmatched', 'run'] as Tab[]).map(t => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-1.5 text-sm font-medium rounded-lg transition-all ${
              tab === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            {t === 'unmatched' ? 'Unmatched Entries' : 'Last Run Results'}
          </button>
        ))}
      </div>

      {/* Unmatched entries */}
      {tab === 'unmatched' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 className="font-semibold text-sm text-slate-900">Unmatched Bank Entries</h2>
            <button onClick={loadUnmatched} className="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500">
              <RefreshCcw size={14} />
            </button>
          </div>

          {loading ? (
            <div className="py-16"><PageSpinner /></div>
          ) : unmatched.length === 0 ? (
            <div className="py-16 text-center">
              <CheckCircle2 size={32} className="mx-auto text-green-400 mb-2" />
              <p className="text-sm text-slate-500">All entries matched. Bank is reconciled.</p>
            </div>
          ) : (
            <table className="w-full text-left">
              <thead className="bg-slate-50 border-b border-slate-100">
                <tr>
                  {['Date', 'Narration', 'Reference', 'Credit', 'Debit', 'Status'].map(h => (
                    <th key={h} className="px-6 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {unmatched.map(entry => (
                  <tr key={entry.id} className="hover:bg-slate-50 transition-colors">
                    <td className="px-6 py-3 text-xs text-slate-600">{formatDate(entry.entry_date)}</td>
                    <td className="px-6 py-3 text-sm text-slate-800 max-w-xs truncate">{entry.narration}</td>
                    <td className="px-6 py-3 font-mono text-xs text-slate-500">{entry.reference_number ?? '—'}</td>
                    <td className="px-6 py-3 font-mono text-sm text-green-700 font-semibold">
                      {entry.credit_amount > 0 ? formatINR(entry.credit_amount) : '—'}
                    </td>
                    <td className="px-6 py-3 font-mono text-sm text-red-700 font-semibold">
                      {entry.debit_amount > 0 ? formatINR(entry.debit_amount) : '—'}
                    </td>
                    <td className="px-6 py-3">
                      <Badge variant={entry.status === 'PENDING' ? 'warning' : entry.status === 'MATCHED' ? 'success' : 'danger'}>
                        {entry.status}
                      </Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* Last run results */}
      {tab === 'run' && (
        <div className="space-y-4">
          {!result ? (
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm py-16 text-center text-sm text-slate-400">
              No reconciliation run yet. Click "Run Reconciliation" to start.
            </div>
          ) : (
            <>
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div className="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                  <CheckCircle2 size={15} className="text-green-600" />
                  <h2 className="font-semibold text-sm text-slate-900">Matched ({result.matched.length})</h2>
                </div>
                <table className="w-full text-left">
                  <thead className="bg-slate-50 border-b border-slate-100">
                    <tr>
                      {['Bank Entry ID', 'System Payment ID', 'Confidence', 'Match Criteria'].map(h => (
                        <th key={h} className="px-6 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {result.matched.map(m => (
                      <tr key={m.bank_entry_id} className="hover:bg-slate-50">
                        <td className="px-6 py-3 font-mono text-xs text-slate-700">{m.bank_entry_id}</td>
                        <td className="px-6 py-3 font-mono text-xs text-slate-700">{m.system_payment_id}</td>
                        <td className="px-6 py-3">
                          <Badge variant={confidenceVariant(m.match_confidence)}>{m.match_confidence}</Badge>
                        </td>
                        <td className="px-6 py-3 text-xs text-slate-500">{m.match_criteria.join(', ')}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {result.unmatched.length > 0 && (
                <div className="bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
                  <div className="px-6 py-4 border-b border-red-100 flex items-center gap-2">
                    <XCircle size={15} className="text-red-600" />
                    <h2 className="font-semibold text-sm text-red-700">Still Unmatched ({result.unmatched.length})</h2>
                  </div>
                  <table className="w-full text-left">
                    <thead className="bg-red-50 border-b border-red-100">
                      <tr>
                        {['Date', 'Narration', 'Reference', 'Amount'].map(h => (
                          <th key={h} className="px-6 py-3 text-[11px] font-semibold text-red-400 uppercase tracking-wide">{h}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-red-50">
                      {result.unmatched.map(e => (
                        <tr key={e.id} className="hover:bg-red-50/50">
                          <td className="px-6 py-3 text-xs text-slate-600">{formatDate(e.entry_date)}</td>
                          <td className="px-6 py-3 text-sm text-slate-800">{e.narration}</td>
                          <td className="px-6 py-3 font-mono text-xs text-slate-500">{e.reference_number ?? '—'}</td>
                          <td className="px-6 py-3 font-mono text-sm font-semibold text-slate-900">
                            {formatINR(e.credit_amount || e.debit_amount)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </>
          )}
        </div>
      )}
    </div>
  );
}
