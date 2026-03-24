import { useState } from 'react';
import { User, Building, Lock, Bell, Globe, CheckCircle2 } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Card } from '../../components/ui/Card';

const Settings = () => {
  const [isSaving, setIsSaving] = useState(false);
  const [activeTab, setActiveTab] = useState('Profile Settings');
  const [statusMsg, setStatusMsg] = useState<string | null>(null);

  const showStatus = (msg: string) => {
    setStatusMsg(msg);
    setTimeout(() => setStatusMsg(null), 3000);
  };

  const handleSave = () => {
    setIsSaving(true);
    setTimeout(() => {
      setIsSaving(false);
      showStatus('Settings saved successfully!');
    }, 1200);
  };

  const tabs = [
    { icon: User, label: 'Profile Settings' },
    { icon: Building, label: 'Business Profile' },
    { icon: Lock, label: 'Security & Access' },
    { icon: Bell, label: 'Notifications' },
    { icon: Globe, label: 'Localization & Currency' },
  ];

  return (
    <div className="max-w-4xl mx-auto space-y-8 pb-12 animate-in fade-in duration-500 relative">
      {statusMsg && (
        <div className="fixed top-24 right-8 z-[100] bg-blue-600 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 animate-in slide-in-from-right-8 duration-500">
          <CheckCircle2 size={20} />
          <p className="font-bold text-sm">{statusMsg}</p>
        </div>
      )}

      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight text-slate-900">System Settings</h2>
          <p className="text-slate-500 mt-1">Configure your business preferences and account details.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => showStatus('Changes discarded.')}>Cancel</Button>
          <Button onClick={handleSave} isLoading={isSaving}>Save Changes</Button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Navigation Sidebar for Settings */}
        <div className="space-y-2">
          {tabs.map((item, i) => (
            <button 
              key={i} 
              onClick={() => setActiveTab(item.label)}
              className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all ${
                activeTab === item.label 
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                : 'text-slate-500 hover:bg-white hover:shadow-sm border border-transparent hover:border-slate-100'
              }`}
            >
              <item.icon size={18} />
              {item.label}
            </button>
          ))}
        </div>

        {/* Settings Content Area */}
        <div className="lg:col-span-2 space-y-6">
          <Card 
            title={activeTab} 
            subtitle={
              activeTab === 'Business Profile' 
              ? 'Public and internal business identification' 
              : 'Manage your preferences for this section'
            }
          >
            {activeTab === 'Business Profile' ? (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div className="space-y-2">
                  <label className="text-sm font-semibold text-slate-700">Business Name</label>
                  <input 
                    type="text" 
                    defaultValue="Modern Retail Solutions Ltd."
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-semibold text-slate-700">GSTIN / Tax ID</label>
                  <input 
                    type="text" 
                    defaultValue="27AAAAA0000A1Z5"
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                  />
                </div>
                <div className="md:col-span-2 space-y-2">
                  <label className="text-sm font-semibold text-slate-700">Business Address</label>
                  <textarea 
                    rows={3}
                    defaultValue="123 Tech Park, Silicon Valley, CA 94025, USA"
                    className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                  />
                </div>
              </div>
            ) : (
              <div className="py-12 flex flex-col items-center justify-center text-center space-y-4">
                <div className="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300">
                  <Building size={32} />
                </div>
                <div>
                  <p className="text-sm font-bold text-slate-900">{activeTab} coming soon</p>
                  <p className="text-xs text-slate-500">This configuration panel is under development.</p>
                </div>
              </div>
            )}
          </Card>

          <Card title="Global Preferences" subtitle="System-wide defaults for transactions">
            <div className="space-y-4">
              <div className="flex items-center justify-between py-2">
                <div>
                  <p className="text-sm font-semibold">Base Currency</p>
                  <p className="text-xs text-slate-500">All financial reports will use this currency.</p>
                </div>
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                  <option>USD ($)</option>
                  <option>EUR (€)</option>
                  <option>INR (₹)</option>
                </select>
              </div>
              <div className="flex items-center justify-between py-2 border-t border-slate-100">
                <div>
                  <p className="text-sm font-semibold">Auto-Reconciliation</p>
                  <p className="text-xs text-slate-500">Enable AI-driven matching for bank statements.</p>
                </div>
                <div 
                  className="w-12 h-6 bg-blue-600 rounded-full relative cursor-pointer"
                  onClick={() => showStatus('Auto-Reconciliation preference updated.')}
                >
                  <div className="absolute right-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm"></div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default Settings;
