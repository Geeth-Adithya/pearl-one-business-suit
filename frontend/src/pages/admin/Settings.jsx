import React, { useState, useEffect } from 'react';
import { Settings as SettingsIcon, AlertTriangle, Save, Loader2, User as UserIcon, CheckCircle, XCircle } from 'lucide-react';
import axios from 'axios';
import { useAuth } from '../../context/AuthContext';

const API_URL = 'http://localhost:8000/api';
const inputCls = "w-full px-4 py-2.5 rounded-xl border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";

export default function Settings() {
  const { user } = useAuth();
  
  // Profile State
  const [profile, setProfile] = useState({ username: user?.username || '', email: user?.email || '', password: '' });
  const [profileLoading, setProfileLoading] = useState(false);
  
  // Maintenance State
  const [maintenanceMsg, setMaintenanceMsg] = useState('');
  const [targetDate, setTargetDate] = useState('');
  const [activeAlert, setActiveAlert] = useState({ message: '', targetDate: '', updatedAt: '' });
  const [maintLoading, setMaintLoading] = useState(false);
  
  const [toast, setToast] = useState(null);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  useEffect(() => {
    if (user?.role === 'superadmin') {
      axios.get(`${API_URL}/settings.php?action=get_maintenance&t=${Date.now()}`)
        .then(res => {
          if (res.data.success) {
            setMaintenanceMsg(res.data.message || '');
            setTargetDate(res.data.target_date || '');
            setActiveAlert({
              message: res.data.message || '',
              targetDate: res.data.target_date || '',
              updatedAt: res.data.updated_at || ''
            });
          }
        })
        .catch(console.error);
    }
  }, [user]);

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    setProfileLoading(true);
    try {
      const res = await axios.post(`${API_URL}/settings.php`, {
        action: 'update_profile',
        ...profile
      }, { withCredentials: true });
      
      if (res.data.success) {
        showToast(res.data.message);
        setProfile({...profile, password: ''}); // clear password field
      } else {
        showToast(res.data.message, 'error');
      }
    } catch (err) {
      showToast('Error updating profile', 'error');
    }
    setProfileLoading(false);
  };

  const handleSaveMaintenance = async (e) => {
    e.preventDefault();
    if (user.role !== 'superadmin') return;
    
    if (!maintenanceMsg.trim()) {
      return handleDeleteAlert(); 
    }

    setMaintLoading(true);
    try {
      const res = await axios.post(`${API_URL}/settings.php`, {
        action: 'set_maintenance',
        message: maintenanceMsg,
        target_date: targetDate
      }, { withCredentials: true });
      
      if (res.data.success) {
        showToast('Maintenance alert updated');
        setActiveAlert({ message: maintenanceMsg, targetDate: targetDate, updatedAt: new Date().toISOString() });
      } else {
        showToast(res.data.message, 'error');
      }
    } catch (err) {
      showToast('Error saving maintenance alert', 'error');
    }
    setMaintLoading(false);
  };

  const handleDeleteAlert = async () => {
    setMaintLoading(true);
    try {
      const res = await axios.post(`${API_URL}/settings.php`, {
        action: 'set_maintenance',
        message: '',
        target_date: ''
      }, { withCredentials: true });
      
      if (res.data.success) {
        setMaintenanceMsg('');
        setTargetDate('');
        setActiveAlert({ message: '', targetDate: '', updatedAt: '' });
        showToast('Maintenance alert removed');
      } else {
        showToast(res.data.message, 'error');
      }
    } catch (err) {
      showToast('Error removing maintenance alert', 'error');
    }
    setMaintLoading(false);
  };

  return (
    <div className="max-w-3xl mx-auto space-y-6 animate-in fade-in duration-300 pb-10">
      
      {toast && (
        <div className={`fixed top-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 text-white text-sm font-medium transition-all ${toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'}`}>
          {toast.type === 'error' ? <XCircle size={18} /> : <CheckCircle size={18} />}
          {toast.message}
        </div>
      )}

      <div className="flex items-center justify-center gap-3 mb-8">
        <div className="w-12 h-12 bg-brand-500/10 text-brand-500 rounded-xl flex items-center justify-center">
          <SettingsIcon size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white">System Settings</h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm">Configure global platform preferences</p>
        </div>
      </div>

      {user?.role === 'superadmin' ? (
        <div className="space-y-6">
          
          {/* PROFILE SECTION */}
          <div className="glass-card p-6 border-t-4 border-t-brand-500">
            <div className="flex items-center gap-2 text-brand-500 mb-6">
              <UserIcon size={20} />
              <h2 className="text-lg font-bold text-text-light dark:text-white">My Profile (Super Admin)</h2>
            </div>
            
            <form onSubmit={handleUpdateProfile} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Username *</label>
                  <input
                    type="text"
                    required
                    className={inputCls}
                    value={profile.username}
                    onChange={(e) => setProfile({...profile, username: e.target.value})}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Email *</label>
                  <input
                    type="email"
                    required
                    className={inputCls}
                    value={profile.email}
                    onChange={(e) => setProfile({...profile, email: e.target.value})}
                  />
                </div>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">New Password (leave blank to keep current)</label>
                <input
                  type="password"
                  className={inputCls}
                  placeholder="Minimum 8 characters"
                  value={profile.password}
                  onChange={(e) => setProfile({...profile, password: e.target.value})}
                />
              </div>

              <div className="flex justify-end pt-2">
                <button type="submit" disabled={profileLoading} className="px-5 py-2.5 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition flex items-center gap-2">
                  {profileLoading ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Save Profile
                </button>
              </div>
            </form>
          </div>


          {/* MAINTENANCE SECTION */}
          <div className="glass-card p-6 border-t-4 border-t-red-500 mt-10">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-2 text-red-500">
                <AlertTriangle size={20} />
                <h2 className="text-lg font-bold text-text-light dark:text-white">Maintenance / System Alerts</h2>
              </div>
              {activeAlert.message && (
                <span className="bg-red-500/10 text-red-500 text-xs font-semibold px-2 py-1 rounded-md animate-pulse">ACTIVE</span>
              )}
            </div>
            
            <p className="text-text-muted dark:text-text-mutedDark text-sm mb-6">
              Set a global announcement message for all users.
            </p>

            <form onSubmit={handleSaveMaintenance} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Announcement Message</label>
                <textarea
                  rows="3"
                  className={inputCls + " resize-y"}
                  placeholder="e.g. ⚠️ The system will be down for scheduled maintenance..."
                  value={maintenanceMsg}
                  onChange={(e) => setMaintenanceMsg(e.target.value)}
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Target Date & Time (Optional)</label>
                <input
                  type="datetime-local"
                  className={inputCls}
                  value={targetDate}
                  onChange={(e) => setTargetDate(e.target.value)}
                />
              </div>

              <div className="flex items-center justify-between pt-2">
                {activeAlert.message ? (
                  <button type="button" onClick={handleDeleteAlert} className="px-4 py-2 text-red-500 hover:bg-red-500/10 rounded-lg text-sm font-medium transition">
                    Remove Alert
                  </button>
                ) : <div/>}
                
                <button type="submit" disabled={maintLoading} className="px-5 py-2.5 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition flex items-center gap-2">
                  {maintLoading ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} 
                  {activeAlert.message ? 'Update Notification' : 'Send Notification'}
                </button>
              </div>
            </form>
          </div>

        </div>
      ) : (
        <div className="glass-card p-12 text-center">
          <SettingsIcon size={48} className="mx-auto text-text-muted/20 mb-4" />
          <h2 className="text-xl font-bold text-text-light dark:text-white mb-2">Shop Settings</h2>
          <p className="text-text-muted dark:text-text-mutedDark">Shop specific settings will be available here soon.</p>
        </div>
      )}
    </div>
  );
}
