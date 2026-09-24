import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { UsersRound, Plus, Trash2, CheckCircle, XCircle, Loader2, ChevronLeft } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

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

export default function UserManagement() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const isSuperAdmin = user?.role === 'superadmin';

  const [users, setUsers] = useState([]);
  const [admins, setAdmins] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [toast, setToast] = useState(null);
  const [usernameStatus, setUsernameStatus] = useState(null);

  const [form, setForm] = useState({
    username: '', full_name: '', email: '',
    password: '', confirm_password: '', assigned_admin_id: ''
  });

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/users_manage.php`);
      if (res.data.success) {
        setUsers(res.data.users);
        setAdmins(res.data.admins || []);
      }
    } catch { showToast('Failed to load users', 'error'); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchUsers(); }, [fetchUsers]);

  let usernameTimer = null;
  const handleUsernameChange = (val) => {
    setForm(f => ({ ...f, username: val }));
    setUsernameStatus('checking');
    clearTimeout(usernameTimer);
    usernameTimer = setTimeout(async () => {
      if (!val) { setUsernameStatus(null); return; }
      try {
        const res = await axios.post(`${API_URL}/users_manage.php`, { action: 'check_username', username: val });
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
      const res = await axios.post(`${API_URL}/users_manage.php`, { ...form, action: 'create' });
      if (res.data.success) {
        showToast(res.data.message);
        setForm({ username: '', full_name: '', email: '', password: '', confirm_password: '', assigned_admin_id: '' });
        setUsernameStatus(null);
        fetchUsers();
      } else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
    setSubmitting(false);
  };

  const handleToggle = async (u) => {
    try {
      const res = await axios.post(`${API_URL}/users_manage.php`, { action: 'toggle', id: u.id });
      if (res.data.success) {
        setUsers(users.map(x => x.id === u.id ? { ...x, is_active: res.data.is_active } : x));
        showToast(`User ${res.data.is_active ? 'activated' : 'deactivated'}`);
      } else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
  };

  const handleDelete = async (u) => {
    if (!window.confirm(`Delete user "${u.username}"? This cannot be undone.`)) return;
    try {
      const res = await axios.post(`${API_URL}/users_manage.php`, { action: 'delete', id: u.id });
      if (res.data.success) { showToast('User deleted'); fetchUsers(); }
      else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      {/* Toast */}
      {toast && (
        <div className={`fixed top-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 text-white text-sm font-medium ${toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'}`}>
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
            <UsersRound className="text-brand-500" size={26} />
            Manage Users
          </h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm">Create new users and assign them to admins.</p>
        </div>
      </div>

      {/* Create Form */}
      <form onSubmit={handleSubmit} autoComplete="off" className="glass-card p-6 shadow-xl space-y-5">
        <h2 className="text-base font-semibold text-text-light dark:text-white">Create New User</h2>
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div>
            <label className={labelCls}>Username</label>
            <div className="relative">
              <input className={inputCls} autoComplete="new-password" value={form.username} onChange={e => handleUsernameChange(e.target.value)} required />
              {usernameStatus === 'checking' && <Loader2 size={14} className="absolute right-3 top-2.5 text-text-muted animate-spin" />}
              {usernameStatus === 'available' && <CheckCircle size={14} className="absolute right-3 top-2.5 text-emerald-400" />}
              {usernameStatus === 'taken' && <XCircle size={14} className="absolute right-3 top-2.5 text-red-400" />}
            </div>
            {usernameStatus === 'taken' && <p className="text-xs text-red-400 mt-1">Username taken</p>}
          </div>
          <div><label className={labelCls}>Full Name</label><input className={inputCls} autoComplete="off" value={form.full_name} onChange={e => setForm(f => ({ ...f, full_name: e.target.value }))} /></div>
          <div><label className={labelCls}>Email</label><input type="email" autoComplete="new-password" className={inputCls} value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} required /></div>
          <div><label className={labelCls}>Password</label><input type="password" autoComplete="new-password" className={inputCls} value={form.password} onChange={e => setForm(f => ({ ...f, password: e.target.value }))} required /></div>
          <div><label className={labelCls}>Confirm Password</label><input type="password" autoComplete="new-password" className={inputCls} value={form.confirm_password} onChange={e => setForm(f => ({ ...f, confirm_password: e.target.value }))} required /></div>
        </div>

        {isSuperAdmin && (
          <div className="max-w-xs">
            <label className={labelCls}>Assign to Admin (Shop)</label>
            <select className={inputCls} value={form.assigned_admin_id} onChange={e => setForm(f => ({ ...f, assigned_admin_id: e.target.value }))}>
              <option value="">— No specific admin —</option>
              {admins.map(a => <option key={a.id} value={a.id}>{a.username}</option>)}
            </select>
          </div>
        )}

        <div className="flex justify-end">
          <button type="submit" disabled={submitting || usernameStatus === 'taken'} className="flex items-center gap-2 bg-brand-500 hover:bg-brand-700 disabled:opacity-60 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-brand-500/20">
            {submitting ? <Loader2 size={16} className="animate-spin" /> : <Plus size={16} />}
            Create User
          </button>
        </div>
      </form>

      {/* Users Table */}
      <div className="glass-card p-6 shadow-xl">
        <h2 className="text-base font-semibold text-text-light dark:text-white mb-4">All Users</h2>
        {loading ? (
          <div className="flex justify-center py-10"><Loader2 className="animate-spin text-brand-500" size={28} /></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-text-muted dark:text-text-mutedDark uppercase border-b border-border-light dark:border-border-dark">
                <tr>
                  <th className="px-4 py-3">Username</th>
                  <th className="px-4 py-3">Email</th>
                  {isSuperAdmin && <th className="px-4 py-3">Assigned Admin</th>}
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map(u => (
                  <tr key={u.id} className="border-b border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark/40 transition">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <div className={`w-2 h-2 rounded-full ${u.is_active == 1 ? 'bg-emerald-400' : 'bg-gray-400'}`}></div>
                        <span className="font-medium text-text-light dark:text-white">{u.username}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark">{u.email}</td>
                    {isSuperAdmin && <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark">{u.admin_username || '—'}</td>}
                    <td className="px-4 py-3">
                      <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${u.is_active == 1 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}`}>
                        {u.is_active == 1 ? 'Active' : 'Disabled'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-2">
                        <button onClick={() => handleDelete(u)} title="Delete" className="text-red-500 hover:text-red-400 text-xs font-medium transition">Delete</button>
                        <Toggle checked={u.is_active == 1} onChange={() => handleToggle(u)} />
                      </div>
                    </td>
                  </tr>
                ))}
                {users.length === 0 && <tr><td colSpan={isSuperAdmin ? 5 : 4} className="text-center py-10 text-text-muted">No users found.</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

