import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Building2, Plus, RefreshCw, Trash2, CheckCircle, XCircle, AlertCircle, Loader2, ChevronLeft, Edit2, Key } from 'lucide-react';
import { useNavigate, Link } from 'react-router-dom';

const API_URL = 'http://localhost:8000/api';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";
const labelCls = "block text-xs font-medium text-text-muted dark:text-text-mutedDark mb-1";

function Toggle({ checked, onChange }) {
  return (
    <button type="button" onClick={onChange}
      className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${checked ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-600'}`}>
      <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${checked ? 'translate-x-6' : 'translate-x-1'}`} />
    </button>
  );
}

export default function ShopManagement() {
  const navigate = useNavigate();
  const [shops, setShops] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [toast, setToast] = useState(null);
  const [usernameStatus, setUsernameStatus] = useState(null); // null | 'checking' | 'available' | 'taken'

  const [editShop, setEditShop] = useState(null);
  const [editForm, setEditForm] = useState({ full_name: '', email: '', shop_name: '', shop_contact: '' });

  const [resetShop, setResetShop] = useState(null);
  const [resetPassword, setResetPassword] = useState('');

  const [form, setForm] = useState({
    shop_name: '', full_name: '', shop_contact: '',
    username: '', email: '', password: '', confirm_password: '',
    subscription_plan: '', module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1
  });

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'update', id: editShop.id, ...editForm });
      if (res.data.success) { showToast(res.data.message); setEditShop(null); fetchShops(); }
      else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
    setSubmitting(false);
  };

  const handleResetPassword = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'reset_password', id: resetShop.id, password: resetPassword });
      if (res.data.success) { showToast(res.data.message); setResetShop(null); }
      else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
    setSubmitting(false);
  };

  const fetchShops = useCallback(async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/shops.php`);
      if (res.data.success) setShops(res.data.shops);
    } catch (e) { showToast('Failed to load shops', 'error'); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchShops(); }, [fetchShops]);

  let usernameTimer = null;
  const handleUsernameChange = (val) => {
    setForm(f => ({ ...f, username: val }));
    setUsernameStatus('checking');
    clearTimeout(usernameTimer);
    usernameTimer = setTimeout(async () => {
      if (!val) { setUsernameStatus(null); return; }
      try {
        const res = await axios.post(`${API_URL}/shops.php`, { action: 'check_username', username: val });
        setUsernameStatus(res.data.available ? 'available' : 'taken');
      } catch { setUsernameStatus(null); }
    }, 500);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (form.password !== form.confirm_password) { showToast('Passwords do not match', 'error'); return; }
    if (usernameStatus === 'taken') { showToast('Username is already taken', 'error'); return; }
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { ...form, action: 'create' });
      if (res.data.success) {
        showToast(res.data.message);
        setForm({ shop_name: '', full_name: '', shop_contact: '', username: '', email: '', password: '', confirm_password: '', subscription_plan: '', module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1 });
        setUsernameStatus(null);
        fetchShops();
      } else { showToast(res.data.message, 'error'); }
    } catch { showToast('Server error', 'error'); }
    setSubmitting(false);
  };

  const handleToggle = async (shop) => {
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'toggle', id: shop.id });
      if (res.data.success) {
        setShops(shops.map(s => s.id === shop.id ? { ...s, is_active: res.data.is_active } : s));
        showToast(`Shop ${res.data.is_active ? 'activated' : 'deactivated'}`);
      } else { showToast(res.data.message, 'error'); }
    } catch { showToast('Server error', 'error'); }
  };

  const handleDelete = async (shop) => {
    if (!window.confirm(`Delete "${shop.shop_name || shop.username}" shop? This cannot be undone.`)) return;
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'delete', id: shop.id });
      if (res.data.success) { showToast('Shop deleted'); fetchShops(); }
      else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
  };

  const handleRenew = async (shop) => {
    try {
      const res = await axios.post(`${API_URL}/shops.php`, { action: 'renew', id: shop.id });
      if (res.data.success) { showToast(res.data.message); fetchShops(); }
      else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
  };

  const isExpired = (date) => date && new Date(date) < new Date();

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      {/* Toast */}
      {toast && (
        <div className={`fixed top-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 text-white text-sm font-medium transition-all ${toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'}`}>
          {toast.type === 'error' ? <XCircle size={18} /> : <CheckCircle size={18} />}
          {toast.message}
        </div>
      )}

      {/* Header */}
      <div className="flex items-center gap-4">
        <button onClick={() => navigate('/')} className="p-2 rounded-lg bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark transition">
          <ChevronLeft size={20} className="text-text-muted dark:text-text-mutedDark" />
        </button>
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
            <Building2 className="text-brand-500" size={26} />
            Manage Shops
          </h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm">Create and manage administrator accounts.</p>
        </div>
      </div>

      {/* Create Form */}
      <form onSubmit={handleSubmit} autoComplete="off" className="glass-card p-6 shadow-xl space-y-5">
        <h2 className="text-base font-semibold text-text-light dark:text-white">Create New Shop</h2>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div><label className={labelCls}>Shop Name</label><input className={inputCls} autoComplete="off" value={form.shop_name} onChange={e => setForm(f => ({ ...f, shop_name: e.target.value }))} required /></div>
          <div><label className={labelCls}>Owner's Name</label><input className={inputCls} autoComplete="off" value={form.full_name} onChange={e => setForm(f => ({ ...f, full_name: e.target.value }))} /></div>
          <div><label className={labelCls}>Phone Number</label><input className={inputCls} autoComplete="off" value={form.shop_contact} onChange={e => setForm(f => ({ ...f, shop_contact: e.target.value }))} /></div>
          <div>
            <label className={labelCls}>Username</label>
            <div className="relative">
              <input className={inputCls} autoComplete="new-password" value={form.username} onChange={e => handleUsernameChange(e.target.value)} required />
              {usernameStatus === 'checking' && <Loader2 size={14} className="absolute right-3 top-2.5 text-text-muted animate-spin" />}
              {usernameStatus === 'available' && <CheckCircle size={14} className="absolute right-3 top-2.5 text-emerald-400" />}
              {usernameStatus === 'taken' && <XCircle size={14} className="absolute right-3 top-2.5 text-red-400" />}
            </div>
            {usernameStatus === 'taken' && <p className="text-xs text-red-400 mt-1">Username already taken</p>}
            {usernameStatus === 'available' && <p className="text-xs text-emerald-400 mt-1">Username available</p>}
          </div>
          <div><label className={labelCls}>Email</label><input type="email" autoComplete="new-password" className={inputCls} value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} required /></div>
          <div><label className={labelCls}>Password</label><input type="password" autoComplete="new-password" className={inputCls} value={form.password} onChange={e => setForm(f => ({ ...f, password: e.target.value }))} required /></div>
          <div><label className={labelCls}>Confirm Password</label><input type="password" autoComplete="new-password" className={inputCls} value={form.confirm_password} onChange={e => setForm(f => ({ ...f, confirm_password: e.target.value }))} required /></div>
          <div>
            <label className={labelCls}>Subscription Plan</label>
            <select className={inputCls} value={form.subscription_plan} onChange={e => setForm(f => ({ ...f, subscription_plan: e.target.value }))} required>
              <option value="" disabled>Select a plan</option>
              <option value="7 Days">7 Days</option>
              <option value="Monthly">Monthly</option>
              <option value="Yearly">Yearly</option>
            </select>
          </div>
        </div>

        {/* Module Toggles */}
        <div className="border border-border-light dark:border-border-dark rounded-xl p-4">
          <h3 className="text-sm font-semibold text-text-light dark:text-white mb-4">Enable Features</h3>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {[
              { key: 'module_pos', label: 'POS / Sales', desc: 'Billing checkout' },
              { key: 'module_inventory', label: 'Inventory', desc: 'Stock management' },
              { key: 'module_suppliers', label: 'Suppliers', desc: 'Supplier orders' },
              { key: 'module_customers', label: 'Customers', desc: 'Customer data' },
              { key: 'module_expenses', label: 'Expenses', desc: 'Track spending' },
              { key: 'module_reports', label: 'Reports', desc: 'Analytics' },
              { key: 'module_staff', label: 'Staff', desc: 'Staff members' },
              { key: 'module_branches', label: 'Branches', desc: 'Multi-branch' }
            ].map(mod => (
              <div key={mod.key} className="flex items-center gap-3">
                <Toggle checked={form[mod.key]} onChange={() => setForm(f => ({ ...f, [mod.key]: !f[mod.key] }))} />
                <div>
                  <p className="text-sm font-medium text-text-light dark:text-white">{mod.label}</p>
                  <p className="text-xs text-text-muted dark:text-text-mutedDark">{mod.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="flex justify-end">
          <button type="submit" disabled={submitting || usernameStatus === 'taken'} className="flex items-center gap-2 bg-brand-500 hover:bg-brand-700 disabled:opacity-60 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-brand-500/20">
            {submitting ? <Loader2 size={16} className="animate-spin" /> : <Plus size={16} />}
            Create Shop
          </button>
        </div>
      </form>

      {/* Shops Table */}
      <div className="glass-card p-6 shadow-xl">
        <h2 className="text-base font-semibold text-text-light dark:text-white mb-4">Existing Shops</h2>
        {loading ? (
          <div className="flex justify-center py-10"><Loader2 className="animate-spin text-brand-500" size={28} /></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-text-muted dark:text-text-mutedDark uppercase border-b border-border-light dark:border-border-dark">
                <tr>
                  <th className="px-4 py-3">Username</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Created</th>
                  <th className="px-4 py-3">Subscription</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {shops.map(shop => (
                  <tr key={shop.id} className="border-b border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark/40 transition">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <div className="w-7 h-7 rounded-full bg-brand-500/20 text-brand-500 dark:text-brand-300 flex items-center justify-center font-bold text-xs">
                          {shop.username?.charAt(0).toUpperCase()}
                        </div>
                        <span className="font-medium text-text-light dark:text-white">{shop.username}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark">{shop.email}</td>
                    <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark text-xs">{new Date(shop.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</td>
                    <td className="px-4 py-3">
                      <div className="text-text-light dark:text-white text-xs font-medium">{shop.subscription_plan || '—'}</div>
                      {shop.subscription_end_date && (
                        <div className={`text-xs mt-0.5 ${isExpired(shop.subscription_end_date) ? 'text-red-400' : 'text-text-muted dark:text-text-mutedDark'}`}>
                          {isExpired(shop.subscription_end_date) ? '⚠ Expired ' : ''}
                          {new Date(shop.subscription_end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </div>
                      )}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${shop.is_active == 1 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}`}>
                        {shop.is_active == 1 ? 'Active' : 'Disabled'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-2">
                        <Link to={`/shops/edit/${shop.id}`} title="Edit & Manage Shop" className="p-1.5 rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 transition"><Edit2 size={14} /></Link>
                        <button onClick={() => handleRenew(shop)} title="Renew Subscription" className="p-1.5 rounded-lg bg-brand-500/10 text-brand-500 hover:bg-brand-500/20 transition"><RefreshCw size={14} /></button>
                        <button onClick={() => handleDelete(shop)} title="Delete" className="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition"><Trash2 size={14} /></button>
                        <Toggle checked={shop.is_active == 1} onChange={() => handleToggle(shop)} />
                      </div>
                    </td>
                  </tr>
                ))}
                {shops.length === 0 && <tr><td colSpan="6" className="text-center py-10 text-text-muted">No shops created yet.</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>

    </div>
  );
}

