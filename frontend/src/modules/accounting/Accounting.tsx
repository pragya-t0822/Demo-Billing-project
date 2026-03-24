import { useState, useEffect, useCallback } from 'react';
import {
  Wallet, Plus, RefreshCcw, CheckCircle2, XCircle,
  ChevronDown, ChevronUp, Scale,
} from 'lucide-react';
import { Spinner, PageSpinner } from '../../components/ui/Spinner';
import { Alert } from '../../components/ui/Alert';
import { Badge } from '../../components/ui/Badge';
import { Modal } from '../../components/ui/Modal';
import { formatINR } from '../../utils/currency';
import { formatDate } from '../../utils/date';
import accountingService, {
  type JournalEntry,
  type TrialBalance,
  type CreateJournalInput,
} from '../../services/accountingService';

/* ── Demo data fallback ─────────────────────────────────────────────── */
const DEMO_ENTRIES: JournalEntry[] = [
  {
    id: '1', entry_number: 'JE-2026-0001', store_id: 's1',
    entry_date: '2026-03-23', narration: 'Sales receipt — INV-2026-00123',
    reference_type: 'INVOICE', reference_id: 'INV-2026-00123',
    total_debit: 11800, total_credit: 11800, status: 'POSTED', created_at: '2026-03-23T10:00:00Z',
  },
  {
    id: '2', entry_number: 'JE-2026-0002', store_id: 's1',
    entry_date: '2026-03-22', narration: 'Sales receipt — INV-2026-00122',
    reference_type: 'INVOICE', reference_id: 'INV-2026-00122',
    total_debit: 29500, total_credit: 29500, status: 'POSTED', created_at: '2026-03-22T14:30:00Z',
  },
  {
    id: '3', entry_number: 'JE-2026-0003', store_id: 's1',
    entry_date: '2026-03-21', narration: 'Payment gateway settlement',
    reference_type: null, reference_id: null,
    total_debit: 118000, total_credit: 118000, status: 'REVERSED', created_at: '2026-03-21T09:00:00Z',
  },
];

const DEMO_TRIAL: TrialBalance = {
  accounts: [
    { account_code: '1001', account_name: 'Cash Account',            total_debit: 243300, total_credit: 0       },
    { account_code: '1100', account_name: 'Accounts Receivable',     total_debit: 124000, total_credit: 0       },
    { account_code: '1200', account_name: 'Inventory Asset',         total_debit: 850000, total_credit: 0       },
    { account_code: '2001', account_name: 'Accounts Payable',        total_debit: 0,       total_credit: 320000 },
    { account_code: '2100', account_name: 'CGST Payable',            total_debit: 0,       total_credit: 18000  },
    { account_code: '2101', account_name: 'SGST Payable',            total_debit: 0,       total_credit: 18000  },
    { account_code: '4001', account_name: 'Sales Revenue',           total_debit: 0,       total_credit: 861300 },
  ],
  total_debit: 1217300, total_credit: 1217300, balanced: true,
};

/* ── Journal entry form types ───────────────────────────────────────── */
interface LineRow { account_code: string; account_name: string; debit: string; credit: string; }

const BLANK_LINE: LineRow = { account_code: '', account_name: '', debit: '', credit: '' };

/* ── Tab type ───────────────────────────────────────────────────────── */
type Tab = 'journal' | 'trial';

export default function Accounting() {
  const [tab, setTab]               = useState<Tab>('journal');
  const [entries, setEntries]       = useState<JournalEntry[]>([]);
  const [trial, setTrial]           = useState<TrialBalance | null>(null);
  const [loadingJ, setLoadingJ]     = useState(true);
  const [loadingT, setLoadingT]     = useState(false);
  const [error, setError]           = useState<string | null>(null);
  const [expanded, setExpanded]     = useState<string | null>(null);

  /* New journal modal */
  const [showModal, setShowModal]   = useState(false);
  const [narration, setNarration]   = useState('');
  const [entryDate, setEntryDate]   = useState(new Date().toISOString().slice(0, 10));
  const [lines, setLines]           = useState<LineRow[]>([{ ...BLANK_LINE }, { ...BLANK_LINE }]);
  const [posting, setPosting]       = useState(false);
  const [postError, setPostError]   = useState<string | null>(null);

  /* Load journal entries */
  const loadEntries = useCallback(() => {
    setLoadingJ(true);
    setError(null);
    accountingService.getLedger('ALL')
      .then(() => { /* placeholder — list all entries */ })
      .catch(() => {
        setEntries(DEMO_ENTRIES);
      })
      .finally(() => setLoadingJ(false));

    // Use demo data directly since no list-all endpoint
    setTimeout(() => {
      setEntries(DEMO_ENTRIES);
      setLoadingJ(false);
    }, 300);
  }, []);

  /* Load trial balance */
  const loadTrial = useCallback(() => {
    setLoadingT(true);
    accountingService.trialBalance()
      .then(setTrial)
      .catch(() => setTrial(DEMO_TRIAL))
      .finally(() => setLoadingT(false));
  }, []);

  useEffect(() => { loadEntries(); }, [loadEntries]);
  useEffect(() => { if (tab === 'trial' && !trial) loadTrial(); }, [tab, trial, loadTrial]);

  /* Computed totals for new-journal form */
  const totalDebit  = lines.reduce((s, l) => s + (parseFloat(l.debit)  || 0), 0);
  const totalCredit = lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);
  const balanced    = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;

  const updateLine = (i: number, field: keyof LineRow, val: string) => {
    setLines(ls => ls.map((l, idx) => idx === i ? { ...l, [field]: val } : l));
  };

  const handlePost = async () => {
    if (!balanced) { setPostError('Debits must equal Credits.'); return; }
    if (!narration.trim()) { setPostError('Narration is required.'); return; }

    setPosting(true);
    setPostError(null);
    try {
      const input: CreateJournalInput = {
        store_id: 'default',
        fiscal_period_id: 'current',
        entry_date: entryDate,
        narration: narration.trim(),
        lines: lines
          .filter(l => l.account_code && (parseFloat(l.debit) > 0 || parseFloat(l.credit) > 0))
          .map(l => ({
            account_code: l.account_code,
            debit_amount: parseFloat(l.debit) || 0,
            credit_amount: parseFloat(l.credit) || 0,
            description: l.account_name,
          })),
      };
      const entry = await accountingService.postJournal(input);
      setEntries(prev => [entry, ...prev]);
      setShowModal(false);
      setNarration('');
      setLines([{ ...BLANK_LINE }, { ...BLANK_LINE }]);
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Failed to post journal entry.';
      setPostError(msg);
    } finally {
      setPosting(false);
    }
  };

  /* ── Render ─────────────────────────────────────────────────────── */
  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <Wallet size={22} className="text-blue-600" /> Accounting
          </h1>
          <p className="text-sm text-slate-500 mt-0.5">Double-entry journal entries & ledger</p>
        </div>
        <button
          onClick={() => setShowModal(true)}
          className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm"
        >
          <Plus size={15} /> Post Journal Entry
        </button>
      </div>

      {error && <Alert variant="error" dismissible>{error}</Alert>}

      {/* Tabs */}
      <div className="flex gap-1 bg-slate-100 p-1 rounded-xl w-fit">
        {(['journal', 'trial'] as Tab[]).map(t => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-1.5 text-sm font-medium rounded-lg transition-all capitalize ${
              tab === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            {t === 'journal' ? 'Journal Entries' : 'Trial Balance'}
          </button>
        ))}
      </div>

      {/* Journal entries */}
      {tab === 'journal' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 className="font-semibold text-sm text-slate-900">Posted Entries</h2>
            <button onClick={loadEntries} className="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500">
              <RefreshCcw size={14} />
            </button>
          </div>

          {loadingJ ? (
            <div className="py-16"><PageSpinner /></div>
          ) : entries.length === 0 ? (
            <div className="py-16 text-center text-sm text-slate-400">No journal entries found.</div>
          ) : (
            <div className="divide-y divide-slate-100">
              {entries.map(entry => (
                <div key={entry.id}>
                  {/* Row */}
                  <button
                    onClick={() => setExpanded(e => e === entry.id ? null : entry.id)}
                    className="w-full flex items-center gap-4 px-6 py-3.5 hover:bg-slate-50 transition-colors text-left"
                  >
                    <div className="w-32 font-mono text-xs font-semibold text-blue-700 shrink-0">
                      {entry.entry_number}
                    </div>
                    <div className="flex-1 text-sm text-slate-700 truncate">{entry.narration}</div>
                    <div className="text-xs text-slate-500 w-24 shrink-0">{formatDate(entry.entry_date)}</div>
                    <div className="text-sm font-mono font-semibold text-slate-900 w-28 text-right shrink-0">
                      {formatINR(entry.total_debit)}
                    </div>
                    <div className="w-20 shrink-0 flex justify-center">
                      <Badge variant={entry.status === 'POSTED' ? 'success' : 'danger'}>
                        {entry.status}
                      </Badge>
                    </div>
                    <div className="text-slate-400 shrink-0">
                      {expanded === entry.id ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
                    </div>
                  </button>

                  {/* Expanded lines */}
                  {expanded === entry.id && (
                    <div className="px-6 pb-4 bg-slate-50 border-t border-slate-100">
                      <table className="w-full text-xs mt-3">
                        <thead>
                          <tr className="text-slate-400 uppercase tracking-wide">
                            <th className="text-left pb-2 font-semibold">Account</th>
                            <th className="text-right pb-2 font-semibold w-28">Debit</th>
                            <th className="text-right pb-2 font-semibold w-28">Credit</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                          {entry.lines ? entry.lines.map((l, i) => (
                            <tr key={i}>
                              <td className="py-1.5 font-mono text-slate-700">{l.account_code}</td>
                              <td className="py-1.5 font-mono text-right text-slate-900">
                                {l.debit_amount > 0 ? formatINR(l.debit_amount) : '—'}
                              </td>
                              <td className="py-1.5 font-mono text-right text-slate-900">
                                {l.credit_amount > 0 ? formatINR(l.credit_amount) : '—'}
                              </td>
                            </tr>
                          )) : (
                            <tr>
                              <td className="py-1.5 text-slate-400" colSpan={3}>
                                DR Various Accounts / CR Various Accounts — {formatINR(entry.total_debit)}
                              </td>
                            </tr>
                          )}
                        </tbody>
                        <tfoot>
                          <tr className="border-t border-slate-300 font-semibold">
                            <td className="pt-2 text-slate-700">Total</td>
                            <td className="pt-2 text-right font-mono text-slate-900">{formatINR(entry.total_debit)}</td>
                            <td className="pt-2 text-right font-mono text-slate-900">{formatINR(entry.total_credit)}</td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Trial balance */}
      {tab === 'trial' && (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 className="font-semibold text-sm text-slate-900 flex items-center gap-2">
              <Scale size={15} className="text-blue-600" /> Trial Balance
            </h2>
            <button onClick={loadTrial} className="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500">
              <RefreshCcw size={14} />
            </button>
          </div>

          {loadingT ? (
            <div className="py-16"><PageSpinner /></div>
          ) : trial ? (
            <>
              <table className="w-full text-left">
                <thead className="bg-slate-50 border-b border-slate-100">
                  <tr>
                    {['Code', 'Account Name', 'Debit', 'Credit'].map(h => (
                      <th key={h} className="px-6 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                        {h}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {trial.accounts.map(acc => (
                    <tr key={acc.account_code} className="hover:bg-slate-50">
                      <td className="px-6 py-3 font-mono text-xs text-slate-600">{acc.account_code}</td>
                      <td className="px-6 py-3 text-sm text-slate-800">{acc.account_name}</td>
                      <td className="px-6 py-3 font-mono text-sm text-right text-slate-900">
                        {acc.total_debit > 0 ? formatINR(acc.total_debit) : '—'}
                      </td>
                      <td className="px-6 py-3 font-mono text-sm text-right text-slate-900">
                        {acc.total_credit > 0 ? formatINR(acc.total_credit) : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot>
                  <tr className={`border-t-2 font-bold text-sm ${trial.balanced ? 'border-green-400 bg-green-50' : 'border-red-400 bg-red-50'}`}>
                    <td className="px-6 py-3" colSpan={2}>
                      <span className={`flex items-center gap-2 ${trial.balanced ? 'text-green-700' : 'text-red-700'}`}>
                        {trial.balanced
                          ? <><CheckCircle2 size={14} /> BALANCED</>
                          : <><XCircle size={14} /> IMBALANCED — CRITICAL</>}
                      </span>
                    </td>
                    <td className={`px-6 py-3 font-mono text-right ${trial.balanced ? 'text-green-800' : 'text-red-800'}`}>
                      {formatINR(trial.total_debit)}
                    </td>
                    <td className={`px-6 py-3 font-mono text-right ${trial.balanced ? 'text-green-800' : 'text-red-800'}`}>
                      {formatINR(trial.total_credit)}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </>
          ) : null}
        </div>
      )}

      {/* Post Journal Entry Modal */}
      <Modal
        open={showModal}
        onClose={() => { setShowModal(false); setPostError(null); }}
        title="Post Journal Entry"
        size="lg"
      >
        <div className="space-y-4">
          {postError && <Alert variant="error" dismissible>{postError}</Alert>}

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Entry Date</label>
              <input
                type="date" value={entryDate} onChange={e => setEntryDate(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Narration *</label>
              <input
                type="text" value={narration} onChange={e => setNarration(e.target.value)}
                placeholder="e.g. Sales receipt — customer name"
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          {/* Lines table */}
          <div>
            <table className="w-full text-sm">
              <thead>
                <tr className="text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">
                  <th className="pb-2 text-left font-semibold">Account Code</th>
                  <th className="pb-2 text-left font-semibold px-2">Description</th>
                  <th className="pb-2 text-right font-semibold w-28">Debit (₹)</th>
                  <th className="pb-2 text-right font-semibold w-28">Credit (₹)</th>
                </tr>
              </thead>
              <tbody>
                {lines.map((line, i) => (
                  <tr key={i} className="border-b border-slate-100">
                    <td className="py-1.5 pr-2">
                      <input
                        value={line.account_code} onChange={e => updateLine(i, 'account_code', e.target.value)}
                        placeholder="e.g. 1001"
                        className="w-full rounded border border-slate-300 px-2 py-1 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-blue-400"
                      />
                    </td>
                    <td className="py-1.5 px-2">
                      <input
                        value={line.account_name} onChange={e => updateLine(i, 'account_name', e.target.value)}
                        placeholder="Account name"
                        className="w-full rounded border border-slate-300 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400"
                      />
                    </td>
                    <td className="py-1.5 px-1">
                      <input
                        type="number" min="0" step="0.01" value={line.debit}
                        onChange={e => updateLine(i, 'debit', e.target.value)}
                        className="w-full rounded border border-slate-300 px-2 py-1 text-xs font-mono text-right focus:outline-none focus:ring-1 focus:ring-blue-400"
                      />
                    </td>
                    <td className="py-1.5 pl-1">
                      <input
                        type="number" min="0" step="0.01" value={line.credit}
                        onChange={e => updateLine(i, 'credit', e.target.value)}
                        className="w-full rounded border border-slate-300 px-2 py-1 text-xs font-mono text-right focus:outline-none focus:ring-1 focus:ring-blue-400"
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className={`font-bold text-sm border-t-2 ${balanced ? 'border-green-400' : 'border-red-300'}`}>
                  <td colSpan={2} className={`pt-2 ${balanced ? 'text-green-700' : 'text-red-600'}`}>
                    {balanced ? '✓ Balanced' : '✗ Imbalanced'}
                  </td>
                  <td className="pt-2 text-right font-mono text-slate-900">{totalDebit > 0 ? formatINR(totalDebit) : '—'}</td>
                  <td className="pt-2 text-right font-mono text-slate-900">{totalCredit > 0 ? formatINR(totalCredit) : '—'}</td>
                </tr>
              </tfoot>
            </table>

            <button
              type="button"
              onClick={() => setLines(l => [...l, { ...BLANK_LINE }])}
              className="mt-2 text-xs text-blue-600 hover:underline"
            >
              + Add line
            </button>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button
              onClick={() => { setShowModal(false); setPostError(null); }}
              className="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              onClick={handlePost} disabled={posting || !balanced}
              className="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {posting ? <><Spinner size="sm" /> Posting…</> : 'Post Entry'}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
