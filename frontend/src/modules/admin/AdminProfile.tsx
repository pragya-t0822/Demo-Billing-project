import { Shield, Key, Bell, Globe, LogOut, Mail, BadgeCheck, Clock } from 'lucide-react';
import { Button } from '../../components/ui/Button';
import { Card } from '../../components/ui/Card';
import { useAuth } from '../../contexts/AuthContext';

const AdminProfile = ({ showStatus }: { showStatus: (msg: string) => void }) => {
  const { user, logout } = useAuth();
  return (
    <div className="max-w-5xl mx-auto space-y-8 pb-12 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight text-slate-900">Admin Profile</h2>
          <p className="text-slate-500 mt-1">Manage your account settings and security preferences.</p>
        </div>
        <Button variant="danger" className="gap-2" onClick={() => { showStatus('Signing out of session...'); logout(); }}>
          <LogOut size={16} /> Sign Out
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left Column: User Card */}
        <div className="lg:col-span-1 space-y-6">
          <Card className="text-center p-8">
            <div className="w-24 h-24 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-3xl mx-auto mb-4 border-4 border-white shadow-sm">
              {user?.initials}
            </div>
            <h3 className="text-xl font-bold text-slate-900">{user?.name}</h3>
            <p className="text-xs font-bold uppercase tracking-widest text-blue-600 bg-blue-50 px-3 py-1 rounded-full display-inline-block mt-2">
              {user?.role}
            </p>
            <div className="mt-6 flex flex-col gap-2 text-sm text-slate-500">
              <div className="flex items-center justify-center gap-2">
                <Mail size={14} /> {user?.email}
              </div>
              <div className="flex items-center justify-center gap-2">
                <BadgeCheck size={14} className="text-green-500" /> Account Verified
              </div>
            </div>
            <Button variant="outline" className="w-full mt-8" onClick={() => showStatus('Opening profile editor...')}>Edit Profile</Button>
          </Card>

          <Card title="Quick Stats">
            <div className="space-y-4 pt-2">
              <div className="flex items-center justify-between">
                <span className="text-sm text-slate-500">Transactions Today</span>
                <span className="text-sm font-bold text-slate-900">142</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-slate-500">Last Login</span>
                <span className="text-sm font-medium text-slate-900">10 min ago</span>
              </div>
            </div>
          </Card>
        </div>

        {/* Right Column: Detailed Settings */}
        <div className="lg:col-span-2 space-y-8">
          <Card title="Security & Authentication">
            <div className="space-y-6 pt-4">
              <div 
                className="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors group cursor-pointer"
                onClick={() => showStatus('Opening password reset flow...')}
              >
                <div className="flex items-center gap-4">
                  <div className="p-2 bg-white rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-all shadow-sm">
                    <Key size={18} />
                  </div>
                  <div>
                    <p className="text-sm font-bold text-slate-900">Change Password</p>
                    <p className="text-xs text-slate-500">Last changed 3 months ago</p>
                  </div>
                </div>
                <Button variant="ghost" size="sm">Update</Button>
              </div>

              <div 
                className="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors group cursor-pointer"
                onClick={() => showStatus('Managing 2FA settings...')}
              >
                <div className="flex items-center gap-4">
                  <div className="p-2 bg-white rounded-lg group-hover:bg-green-600 group-hover:text-white transition-all shadow-sm">
                    <Shield size={18} />
                  </div>
                  <div>
                    <p className="text-sm font-bold text-slate-900">Two-Factor Authentication</p>
                    <p className="text-xs text-green-600 font-medium">Enabled (Mobile App)</p>
                  </div>
                </div>
                <Button variant="ghost" size="sm">Manage</Button>
              </div>
            </div>
          </Card>

          <Card title="Preferences & Notifications">
            <div className="space-y-6 pt-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <Bell size={18} />
                  </div>
                  <div>
                    <p className="text-sm font-bold text-slate-900">Email Notifications</p>
                    <p className="text-xs text-slate-500">Daily summaries and emergency alerts</p>
                  </div>
                </div>
                <input 
                  type="checkbox" 
                  defaultChecked 
                  className="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500"
                  onChange={(e) => showStatus(`Email notifications ${e.target.checked ? 'enabled' : 'disabled'}`)}
                />
              </div>

              <div className="flex items-center justify-between pt-4 border-t border-slate-100">
                <div className="flex items-center gap-4">
                  <div className="p-2 bg-orange-50 text-orange-600 rounded-lg">
                    <Globe size={18} />
                  </div>
                  <div>
                    <p className="text-sm font-bold text-slate-900">Primary Language</p>
                    <p className="text-xs text-slate-500">English (United States)</p>
                  </div>
                </div>
                <select 
                  className="text-xs font-bold bg-slate-100 border-none rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none"
                  onChange={(e) => showStatus(`Language changed to ${e.target.value}`)}
                >
                  <option>English</option>
                  <option>Hindi</option>
                  <option>Spanish</option>
                </select>
              </div>
            </div>
          </Card>

          <Card title="Login Activity">
            <div className="space-y-4 pt-4">
              {[
                { browser: 'Chrome', os: 'Windows 11', ip: '192.168.1.1', time: 'Active Now', status: 'Current Session' },
                { browser: 'Safari', os: 'iOS 17', ip: '10.0.0.45', time: '2 hours ago', status: 'Mumbai, India' },
              ].map((activity, i) => (
                <div key={i} className="flex items-center justify-between py-3 border-b border-slate-50 last:border-0 border-t-0">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-slate-50 rounded text-slate-400">
                      <Clock size={16} />
                    </div>
                    <div>
                      <p className="text-sm font-bold text-slate-900">{activity.browser} on {activity.os}</p>
                      <p className="text-xs text-slate-500">{activity.ip} • {activity.status}</p>
                    </div>
                  </div>
                  <span className={`text-[10px] font-bold uppercase rounded px-2 py-1 ${activity.time === 'Active Now' ? 'text-green-600 bg-green-50' : 'text-slate-400 bg-slate-50'}`}>
                    {activity.time}
                  </span>
                </div>
              ))}
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default AdminProfile;
