import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../../context/AuthContext';
import { Settings as SettingsIcon, AlertTriangle, Save, Loader2 } from 'lucide-react';

const API_URL = 'http://localhost:8000/api';

export default function Settings() {
  const { user } = useAuth();
  
  const [maintenanceMsg, setMaintenanceMsg] = useState('');
  const [targetDate, setTargetDate] = useState('');
  const [activeAlert, setActiveAlert] = useState({ message: '', targetDate: '', updatedAt: '' });
  const [loading, setLoading] = useState(false);
  const [saveStatus, setSaveStatus] = useState('');

  useEffect(() => {
    // Fetch current maintenance alert
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
  }, []);

  const handleSaveMaintenance = async (e) => {
    e.preventDefault();
    if (user.role !== 'superadmin') return;
    
    if (!maintenanceMsg.trim()) {
      return handleDeleteAlert(); // If saved empty, just delete it
    }

    setLoading(true);
    setSaveStatus('');
    try {
      const res = await axios.post(`${API_URL}/settings.php`, {
        action: 'set_maintenance',
        message: maintenanceMsg,
        target_date: targetDate
      }, { withCredentials: true });
      
      if (res.data.success) {
        setSaveStatus('success');
        setActiveAlert({ message: maintenanceMsg, targetDate: targetDate, updatedAt: new Date().toISOString() });
      } else {
        setSaveStatus('error');
      }
    } catch (err) {
      setSaveStatus('error');
    } finally {
      setLoading(false);
      setTimeout(() => setSaveStatus(''), 3000);
    }
  };

  const handleDeleteAlert = async () => {
    if (user.role !== 'superadmin') return;
    if (!window.confirm("Are you sure you want to remove the active notification?")) return;

    setLoading(true);
    setSaveStatus('');
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
        setSaveStatus('deleted');
      } else {
        setSaveStatus('error');
      }
    } catch (err) {
      setSaveStatus('error');
    } finally {
      setLoading(false);
      setTimeout(() => setSaveStatus(''), 3000);
    }
  };

  return (
    <div className="max-w-4xl mx-auto">
      <div className="flex items-center gap-3 mb-8">
        <div className="w-12 h-12 bg-brand-500 rounded-xl flex items-center justify-center text-white shadow-lg">
          <SettingsIcon size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-brand-900 dark:text-white">System Settings</h1>
          <p className="text-text-muted dark:text-text-mutedDark">Configure global platform preferences</p>
        </div>
      </div>

      {user.role === 'superadmin' ? (
        <div className="space-y-6">
          <div className="glass-card p-6 border-l-4 border-l-red-500">
            <div className="flex items-center gap-2 text-red-500 mb-4">
              <AlertTriangle size={24} />
              <h2 className="text-xl font-bold">Maintenance / System Alerts</h2>
            </div>
            <p className="text-text-muted dark:text-text-mutedDark text-sm mb-6">
              Set a global announcement message that will pop up and be shown in the notification bell for all users. 
              Use this to notify shops about upcoming server downtime or system updates.
            </p>

            <form onSubmit={handleSaveMaintenance} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Announcement Message *</label>
                <textarea
                  required
                  rows="3"
                  className="w-full p-4 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                  placeholder="e.g. ⚠️ The system will be down for scheduled maintenance..."
                  value={maintenanceMsg}
                  onChange={(e) => setMaintenanceMsg(e.target.value)}
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Target Date & Time (Optional)</label>
                <input 
                  type="datetime-local" 
                  className="w-full sm:w-1/2 p-3 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white dark:[color-scheme:dark]"
                  value={targetDate}
                  onChange={(e) => setTargetDate(e.target.value)}
                />
              </div>
              
              <div className="flex items-center justify-between pt-2">
                <div className="text-sm">
                  {saveStatus === 'success' && <span className="text-emerald-500 font-medium">Alert sent successfully!</span>}
                  {saveStatus === 'deleted' && <span className="text-emerald-500 font-medium">Alert removed successfully!</span>}
                  {saveStatus === 'error' && <span className="text-red-500 font-medium">Failed to update alert.</span>}
                </div>
                
                <button 
                  type="submit" 
                  disabled={loading}
                  className="flex items-center gap-2 bg-brand-500 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-brand-600 transition shadow-lg shadow-brand-500/30 disabled:opacity-70"
                >
                  {loading ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
                  Send Notification
                </button>
              </div>
            </form>
          </div>

          {activeAlert.message && (
            <div className="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-900/50 rounded-xl p-6 relative">
              <div className="absolute top-0 right-0 bg-red-500 text-white px-3 py-1 text-xs font-bold rounded-bl-lg rounded-tr-xl uppercase">Active Now</div>
              <h3 className="text-red-700 dark:text-red-400 font-bold mb-2 flex items-center gap-2">
                <AlertTriangle size={18} />
                Currently Live Notification
              </h3>
              <p className="text-gray-800 dark:text-gray-200 text-sm whitespace-pre-wrap mb-4 bg-white/50 dark:bg-black/20 p-4 rounded-lg">
                {activeAlert.message}
              </p>
              
              {activeAlert.targetDate && (
                <div className="text-sm text-red-600 dark:text-red-400 font-medium mb-4">
                  Scheduled target: {new Date(activeAlert.targetDate).toLocaleString()}
                </div>
              )}

              <div className="flex justify-end pt-2 border-t border-red-200 dark:border-red-900/50">
                <button 
                  onClick={handleDeleteAlert}
                  disabled={loading}
                  className="text-red-600 bg-red-100 hover:bg-red-200 dark:text-red-300 dark:bg-red-900/40 dark:hover:bg-red-900/60 font-medium px-4 py-2 rounded-lg transition flex items-center gap-2 text-sm"
                >
                  Remove & Clear Notification
                </button>
              </div>
            </div>
          )}
        </div>
      ) : (
        <div className="glass-card p-10 text-center text-text-muted">
          Your shop settings are currently managed by the super admin.
        </div>
      )}
    </div>
  );
}
