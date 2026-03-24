import { BrowserRouter as Router, Routes, Route, Link, useLocation, Navigate } from 'react-router-dom';
import {
  LayoutDashboard, Receipt, Wallet, Package,
  RefreshCcw, History, BarChart3,
  Settings as SettingsIcon, Bell, Search, TrendingUp,
  AlertTriangle,
} from 'lucide-react';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { ToastContainer }        from './components/ui/Toast';
import { useToast }              from './hooks/useToast';
import { formatINR }             from './utils/currency';
import { formatDate }            from './utils/date';
import Login                     from './modules/auth/Login';
import POSBilling                from './modules/billing/POSBilling';
import Accounting                from './modules/accounting/Accounting';
import Inventory                 from './modules/inventory/Inventory';
import Reconciliation            from './modules/reconciliation/Reconciliation';
import Recovery                  from './modules/recovery/Recovery';
import Reporting                 from './modules/reporting/Reporting';
import Settings                  from './modules/settings/Settings';
import AdminProfile              from './modules/admin/AdminProfile';

/* ── Sidebar nav item ─────────────────────────────────────────────────── */
const NavItem = ({ to, icon: Icon, label }: { to: string; icon: React.ElementType; label: string }) => {
  const { pathname } = useLocation();
  const active = pathname === to || (to !== '/' && pathname.startsWith(to));
  return (
    <Link
      to={to}
      className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
        ${active ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}
    >
      <Icon size={18} className={active ? 'text-white' : 'text-slate-500 group-hover:text-blue-400'} />
      {label}
      {active && <div className="ml-auto w-1.5 h-1.5 rounded-full bg-blue-200 animate-pulse" />}
    </Link>
  );
};

/* ── Protected layout ────────────────────────────────────────────────── */
const AppLayout = () => {
  const { user, logout, isLoading } = useAuth();
  const { toasts, toast, remove } = useToast();

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center bg-slate-50">
        <div className="flex flex-col items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg">R</div>
          <p className="text-sm text-slate-500">Loading RetailFlow…</p>
        </div>
      </div>
    );
  }

  if (!user) return <Navigate to="/login" replace />;

  const initials  = user.name.split(' ').map((w: string) => w[0]).join('').slice(0, 2).toUpperCase();
  const roleLabel = (user.roles?.[0] ?? 'User').replace(/_/g, ' ');

  const handleLogout = async () => {
    try { await logout(); }
    catch { toast.error('Logout failed. Please try again.'); }
  };

  return (
    <div className="flex h-screen bg-slate-100">
      {/* Sidebar */}
      <aside className="w-60 bg-[#0f172a] flex flex-col p-3 shrink-0 z-20 shadow-2xl">
        {/* Logo */}
        <div className="flex items-center gap-3 px-3 py-5 mb-3">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center font-bold text-white text-sm shadow-lg">
            R
          </div>
          <span className="text-white font-bold text-base tracking-tight">RetailFlow</span>
        </div>

        <nav className="flex-1 space-y-0.5">
          <NavItem to="/"               icon={LayoutDashboard} label="Dashboard"      />
          <NavItem to="/billing"         icon={Receipt}         label="POS Billing"    />
          <NavItem to="/accounting"      icon={Wallet}          label="Accounting"     />
          <NavItem to="/inventory"       icon={Package}         label="Inventory"      />
          <NavItem to="/reconciliation"  icon={RefreshCcw}      label="Reconciliation" />
          <NavItem to="/recovery"        icon={History}         label="Recovery"       />
          <NavItem to="/reporting"       icon={BarChart3}       label="Reporting"      />
        </nav>

        <div className="border-t border-slate-800 pt-2 mt-2 space-y-0.5">
          <NavItem to="/settings" icon={SettingsIcon} label="Settings" />
          {/* User pill */}
          <Link to="/admin" className="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 transition-all group">
            <div className="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
              {initials}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-white text-xs font-semibold truncate">{user.name}</p>
              <p className="text-slate-500 text-[10px] truncate uppercase tracking-wide">{roleLabel}</p>
            </div>
          </Link>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-10">
          <div className="relative w-80">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="search"
              placeholder="Search invoices, customers…"
              className="w-full pl-9 pr-4 py-1.5 text-sm bg-slate-100 rounded-full border border-transparent focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition-all"
            />
          </div>
          <div className="flex items-center gap-2">
            <button className="relative p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors">
              <Bell size={18} />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
            </button>
            <button
              onClick={handleLogout}
              className="px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
            >
              Sign out
            </button>
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-auto p-6">
          <Routes>
            <Route path="/"               element={<Dashboard />}    />
            <Route path="/billing"         element={<POSBilling />}   />
            <Route path="/accounting"      element={<Accounting />}   />
            <Route path="/inventory"       element={<Inventory />}    />
            <Route path="/reconciliation"  element={<Reconciliation />} />
            <Route path="/recovery"        element={<Recovery />}     />
            <Route path="/reporting"       element={<Reporting />}    />
            <Route path="/settings"        element={<Settings />}     />
            <Route path="/admin"           element={<AdminProfile showStatus={() => {}} />} />
            <Route path="*"               element={<Navigate to="/" replace />} />
          </Routes>
        </main>
      </div>

      <ToastContainer toasts={toasts} onRemove={remove} />
    </div>
  );
};

/* ── Dashboard ───────────────────────────────────────────────────────── */
const Dashboard = () => {
  const recentInvoices = [
    { id: 'INV-2026-00123', customer: 'Priya Sharma',   amount: 11800,  status: 'PAID',    date: '2026-03-23' },
    { id: 'INV-2026-00122', customer: 'Raj Patel',      amount: 29500,  status: 'CONFIRMED', date: '2026-03-22' },
    { id: 'INV-2026-00121', customer: 'Anita Singh',    amount: 4720,   status: 'DRAFT',   date: '2026-03-22' },
    { id: 'INV-2026-00120', customer: 'Vikram Mehta',   amount: 118000, status: 'PAID',    date: '2026-03-21' },
  ];

  const metrics = [
    { label: 'Revenue (MTD)',      value: formatINR(384500),  change: '+12.5%', up: true,  icon: TrendingUp   },
    { label: 'Active Invoices',    value: '1,248',             change: '+3.2%',  up: true,  icon: Receipt      },
    { label: 'Stock Valuation',    value: formatINR(8500000),  change: '-1.4%',  up: false, icon: Package      },
    { label: 'Pending Recovery',   value: formatINR(124000),   change: '+8.1%',  up: false, icon: AlertTriangle },
  ];

  const statusCls: Record<string, string> = {
    PAID:      'bg-green-100 text-green-700',
    CONFIRMED: 'bg-blue-100  text-blue-700',
    DRAFT:     'bg-yellow-100 text-yellow-700',
    CANCELLED: 'bg-red-100   text-red-700',
  };

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Financial Overview</h1>
          <p className="text-sm text-slate-500 mt-0.5">Real-time business metrics · {formatDate(new Date().toISOString())}</p>
        </div>
        <Link to="/billing" className="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
          <Receipt size={15} /> New Invoice
        </Link>
      </div>

      {/* Metric cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {metrics.map((m) => (
          <div key={m.label} className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between mb-3">
              <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide">{m.label}</p>
              <m.icon size={16} className="text-slate-400" />
            </div>
            <p className="text-xl font-bold text-slate-900 font-mono">{m.value}</p>
            <span className={`mt-1 inline-block text-[11px] font-semibold px-1.5 py-0.5 rounded ${m.up ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
              {m.change}
            </span>
          </div>
        ))}
      </div>

      {/* Recent invoices */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 className="font-semibold text-sm text-slate-900">Recent Invoices</h2>
          <Link to="/billing" className="text-xs font-semibold text-blue-600 hover:underline">View all →</Link>
        </div>
        <table className="w-full text-left">
          <thead className="bg-slate-50 border-b border-slate-100">
            <tr>
              {['Invoice #', 'Customer', 'Amount', 'Status', 'Date'].map(h => (
                <th key={h} className="px-6 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {recentInvoices.map(row => (
              <tr key={row.id} className="hover:bg-slate-50 transition-colors">
                <td className="px-6 py-3 font-mono text-xs font-semibold text-slate-800">{row.id}</td>
                <td className="px-6 py-3 text-sm text-slate-700">{row.customer}</td>
                <td className="px-6 py-3 font-mono text-sm font-semibold text-slate-900 text-right">{formatINR(row.amount)}</td>
                <td className="px-6 py-3">
                  <span className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase ${statusCls[row.status] ?? 'bg-slate-100 text-slate-600'}`}>
                    {row.status}
                  </span>
                </td>
                <td className="px-6 py-3 text-xs text-slate-500">{formatDate(row.date)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

/* ── Root ─────────────────────────────────────────────────────────────── */
export default function App() {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/*"     element={<AppLayout />} />
        </Routes>
      </AuthProvider>
    </Router>
  );
}
