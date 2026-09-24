import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Store, Save, Key, ChevronLeft, Loader2, CheckCircle, XCircle } from 'lucide-react';
import { useNavigate, useParams, Link } from 'react-router-dom';

const API_URL = 'http://localhost:8000/api';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";
const labelCls = "block text-xs font-medium text-text-muted dark:text-text-mutedDark mb-1";

export default function ShopEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [toast, setToast] = useState(null);

  const [form, setForm] = useState({
    shop_name: '', full_name: '', email: '', shop_contact: '', module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1
  });
  
  const [resetPassword, setResetPassword] = useState('');

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  useEffect(() => {
    axios.get(`${API_URL}/shops.php?id=${id}`)
      .then(res => {
        if (res.data.success) {
          setForm({
            shop_name: res.data.shop.shop_name || '',
            full_name: res.data.shop.full_name || '',
            email: res.data.shop.email || '',
            shop_contact: res.data.shop.shop_contact || '', module_pos: res.data.shop.module_pos ?? 1, module_inventory: res.data.shop.module_inventory ?? 1, module_suppliers: res.data.shop.module_suppliers ?? 1, module_customers: res.data.shop.module_customers ?? 1, module_expenses: res.data.shop.module_expenses ?? 1, module_reports: res.data.shop.module_reports ?? 1, module_staff: res.data.shop.module_staff ?? 1, module_branches: res.data.shop.module_branches ?? 1
          });
        } else {
          alert('Shop not found');
          navigate('/shops');
        }
      })
      .catch(() => {
        alert('Failed to load shop details');
        navigate('/shops');
      })
      .finally(() => setLoading(false));
  }, [id, navigate]);

  const handleUpdateDetails = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'update', id, ...form });
      if (res.data.success) showToast(res.data.message);
      else showToast(res.data.message, 'error');
    } catch {
      showToast('Server error', 'error');
    }
    setSubmitting(false);
  };

  const handleResetPassword = async (e) => {
    e.preventDefault();
    if (resetPassword.length < 4) {
      showToast('Password must be at least 4 characters', 'error');
      return;
    }
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'reset_password', id, password: resetPassword });
      if (res.data.success) {
        showToast(res.data.message);
        setResetPassword('');
      } else {
        showToast(res.data.message, 'error');
      }
    } catch {
      showToast('Server error', 'error');
    }
    setSubmitting(false);
  };

  if (loading) return <div className="flex justify-center py-20"><Loader2 className="animate-spin text-brand-500" size={32} /></div>;

  return (
    <div className="space-y-6 animate-in fade-in duration-300 max-w-4xl mx-auto">
      {/* Toast */}
      {toast && (
        <div className={`fixed top-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 text-white text-sm font-medium transition-all ${toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'}`}>
          {toast.type === 'error' ? <XCircle size={18} /> : <CheckCircle size={18} />}
          {toast.message}
        </div>
      )}

      {/* Header with Back Button */}
      <div className="flex items-center gap-4">
        <button onClick={() => navigate(-1)} className="p-2 rounded-lg bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark transition">
          <ChevronLeft size={20} className="text-text-muted dark:text-text-mutedDark" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
            <Store className="text-brand-500" size={26} />
            Edit Shop Details
          </h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm">Update shop information and credentials.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Edit Details */}
        <div className="glass-card p-6 shadow-xl space-y-5 h-max">
          <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3">Basic Information</h2>
          <form onSubmit={handleUpdateDetails} className="space-y-4">
            <div><label className={labelCls}>Shop Name</label><input className={inputCls} value={form.shop_name} onChange={e => setForm(f => ({ ...f, shop_name: e.target.value }))} required /></div>
            <div><label className={labelCls}>Owner's Name</label><input className={inputCls} value={form.full_name} onChange={e => setForm(f => ({ ...f, full_name: e.target.value }))} /></div>
            <div><label className={labelCls}>Email Address</label><input type="email" className={inputCls} value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} required /></div>
            <div><label className={labelCls}>Phone Number</label><input className={inputCls} value={form.shop_contact} onChange={e => setForm(f => ({ ...f, shop_contact: e.target.value }))} /></div>
            
            <div className="pt-2 flex justify-end">
              <button type="submit" disabled={submitting} className="px-5 py-2.5 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 flex items-center gap-2 transition shadow-lg shadow-brand-500/20">
                {submitting ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Save Details
              </button>
            </div>
          </form>
        </div>

        {/* Reset Password */}
        <div className="glass-card p-6 shadow-xl space-y-5 h-max">
          <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3 flex items-center gap-2">
            <Key size={18} className="text-purple-500" /> Reset Password
          </h2>
          <form onSubmit={handleResetPassword} autoComplete="off" className="space-y-4">
            <p className="text-sm text-text-muted dark:text-text-mutedDark mb-4">Set a new password for this shop's admin account. They will use this to log in.</p>
            <div>
              <label className={labelCls}>New Password</label>
              <input type="password" minLength={4} autoComplete="new-password" className={inputCls} value={resetPassword} onChange={e => setResetPassword(e.target.value)} required />
            </div>
            
            <div className="pt-2 flex justify-end">
              <button type="submit" disabled={submitting || !resetPassword} className="px-5 py-2.5 rounded-lg text-sm font-medium bg-purple-500 text-white hover:bg-purple-600 flex items-center gap-2 transition shadow-lg shadow-purple-500/20">
                {submitting ? <Loader2 size={16} className="animate-spin" /> : <Key size={16} />} Reset Password
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="glass-card p-6 shadow-xl space-y-5">
        <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3">
          Enabled Features (Modules)
        </h2>
        <form onSubmit={handleUpdateDetails} className="space-y-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {[
              { id: 'module_pos', label: 'POS / Sales' },
              { id: 'module_inventory', label: 'Inventory' },
              { id: 'module_suppliers', label: 'Suppliers' },
              { id: 'module_customers', label: 'Customers' },
              { id: 'module_expenses', label: 'Expenses' },
              { id: 'module_reports', label: 'Reports' },
              { id: 'module_staff', label: 'Staff' },
              { id: 'module_branches', label: 'Branches' }
            ].map(mod => (
              <label key={mod.id} className="flex items-center gap-3 p-3 border border-border-light dark:border-border-dark rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition">
                <input 
                  type="checkbox" 
                  checked={!!form[mod.id]}
                  onChange={e => setForm(f => ({ ...f, [mod.id]: e.target.checked ? 1 : 0 }))}
                  className="w-4 h-4 text-brand-500 rounded focus:ring-brand-500"
                />
                <span className="text-sm font-medium text-text-light dark:text-gray-200">{mod.label}</span>
              </label>
            ))}
          </div>
          <div className="pt-2 flex justify-end">
            <button type="submit" disabled={submitting} className="px-5 py-2.5 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 flex items-center gap-2 transition shadow-lg shadow-brand-500/20">
              {submitting ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Save Modules
            </button>
          </div>
        </form>
      </div>

    </div>
  );
}

