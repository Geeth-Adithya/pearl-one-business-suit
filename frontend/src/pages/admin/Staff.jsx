import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { UserCircle, Plus, Search, Edit2, Trash2, X, Loader2, Mail, Lock, CheckCircle, XCircle } from 'lucide-react';

const API_URL = 'http://localhost:8000/api';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none transition";
const labelCls = "block text-xs font-medium text-text-muted dark:text-text-mutedDark mb-1";

export default function Staff() {
  const [staff, setStaff] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState('add');
  const [submitting, setSubmitting] = useState(false);
  
  const [form, setForm] = useState({
    id: null,
    username: '',
    full_name: '',
    email: '',
    password: '',
    role: 'user',
    is_active: 1
  });

  const [usernameStatus, setUsernameStatus] = useState('idle'); // idle, checking, available

  const fetchStaff = () => {
    setLoading(true);
    axios.get(`${API_URL}/staff.php?action=list`)
      .then(res => {
        if (res.data.success) {
          setStaff(res.data.staff);
        }
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchStaff();
  }, []);

  const openAddModal = () => {
    setModalMode('add');
    setForm({
      id: null,
      username: '',
      full_name: '',
      email: '',
      password: '',
      role: 'user',
      is_active: 1
    });
    setUsernameStatus('idle');
    setIsModalOpen(true);
  };

  const openEditModal = (user) => {
    setModalMode('edit');
    setForm({
      id: user.id,
      username: user.username,
      full_name: user.full_name || '',
      email: user.email,
      password: '', 
      role: user.role || 'user',
      is_active: user.is_active
    });
    setUsernameStatus('idle'); // Edit mode doesn't strictly need validation until submit, but we reset it
    setIsModalOpen(true);
  };

  const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this staff member? They will lose all access immediately.")) return;
    try {
      const res = await axios.post(`${API_URL}/staff.php`, { action: 'delete', id });
      if (res.data.success) {
        fetchStaff();
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert("Error deleting staff member");
    }
  };

  const handleCheckUsername = async (currentUsername) => {
    if (!currentUsername) return;
    setUsernameStatus('checking');

    try {
      const res = await axios.get(`${API_URL}/staff.php?action=check_username&username=${encodeURIComponent(currentUsername)}`);
      
      if (res.data.success) {
        if (res.data.available) {
          // Available!
          setForm(prev => ({...prev, username: currentUsername}));
          setUsernameStatus('available');
        } else {
          // Not available. Generate random 2 digits and recursively check
          const randomSuffix = Math.floor(10 + Math.random() * 90); // 10-99
          const newUsername = currentUsername + randomSuffix;
          handleCheckUsername(newUsername);
        }
      }
    } catch (err) {
      console.error("Error checking username", err);
      setUsernameStatus('idle');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/staff.php`, {
        action: modalMode === 'add' ? 'create' : 'update',
        ...form
      });
      if (res.data.success) {
        setIsModalOpen(false);
        fetchStaff();
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert("Error saving staff member");
    }
    setSubmitting(false);
  };

  const filteredStaff = staff.filter(s => 
    s.username.toLowerCase().includes(searchQuery.toLowerCase()) ||
    (s.full_name && s.full_name.toLowerCase().includes(searchQuery.toLowerCase())) ||
    s.email.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-2">
            <UserCircle className="text-brand-500" /> Staff Management
          </h1>
          <p className="text-sm text-text-muted dark:text-text-mutedDark">Add and manage user accounts for your shop.</p>
        </div>
        <button 
          onClick={openAddModal}
          className="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-lg shadow-brand-500/20 w-fit"
        >
          <Plus size={18} /> Add Staff
        </button>
      </div>

      <div className="glass-card p-2 flex items-center gap-2 max-w-md">
        <Search className="text-text-muted dark:text-text-mutedDark ml-2 min-w-fit" size={18} />
        <input 
          type="text" 
          placeholder="Search by username, name, or email..." 
          className="w-full bg-transparent border-none text-text-light dark:text-white text-sm focus:ring-0 px-2"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
        />
      </div>

      <div className="glass-card rounded-xl overflow-hidden border border-border-light dark:border-border-dark">
        {loading ? (
          <div className="flex justify-center p-12">
            <Loader2 className="animate-spin text-brand-500" size={32} />
          </div>
        ) : filteredStaff.length === 0 ? (
          <div className="p-12 text-center text-text-muted dark:text-text-mutedDark">
            <UserCircle size={48} className="mx-auto mb-4 opacity-20" />
            <p>No staff members found.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-bg-light/50 dark:bg-bg-dark/50 border-b border-border-light dark:border-border-dark text-xs uppercase text-text-muted dark:text-text-mutedDark font-semibold">
                  <th className="p-4">Staff Member</th>
                  <th className="p-4">Contact</th>
                  <th className="p-4">Status</th>
                  <th className="p-4">Created Date</th>
                  <th className="p-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredStaff.map(s => (
                  <tr key={s.id} className="border-b border-border-light/50 dark:border-border-dark/50 hover:bg-bg-light/20 dark:hover:bg-bg-dark/20 transition group">
                    <td className="p-4">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-brand-500/10 text-brand-500 flex items-center justify-center font-bold uppercase">
                          {s.username.charAt(0)}
                        </div>
                        <div>
                          <p className="text-sm font-semibold text-text-light dark:text-white">{s.full_name || s.username}</p>
                          <div className="flex items-center gap-2 mt-0.5">
                            <p className="text-xs text-text-muted dark:text-text-mutedDark">@{s.username}</p>
                            {s.role === 'manager' ? (
                              <span className="text-[10px] bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 px-1.5 py-0.5 rounded-md font-bold">Admin</span>
                            ) : (
                              <span className="text-[10px] bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 px-1.5 py-0.5 rounded-md font-medium">Staff</span>
                            )}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-2 text-sm text-text-light dark:text-white">
                        <Mail size={14} className="text-text-muted dark:text-text-mutedDark" />
                        {s.email}
                      </div>
                    </td>
                    <td className="p-4">
                      <div className="flex flex-col gap-2">
                        {s.is_active ? (
                          <span className="flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <CheckCircle size={12} /> Active
                          </span>
                        ) : (
                          <span className="flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                            <XCircle size={12} /> Inactive
                          </span>
                        )}
                        
                        {s.is_online == 1 ? (
                          <span className="flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div> Online
                          </span>
                        ) : (
                          <span className="flex items-center gap-1.5 text-xs font-medium text-text-muted dark:text-text-mutedDark">
                            <div className="w-2 h-2 rounded-full bg-gray-400 dark:bg-gray-600"></div> Offline
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="p-4 text-sm text-text-muted dark:text-text-mutedDark">
                      {s.created_at ? new Date(s.created_at).toLocaleDateString() : '-'}
                    </td>
                    <td className="p-4">
                      <div className="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onClick={() => openEditModal(s)} className="p-1.5 text-blue-500 hover:bg-blue-500/10 rounded-lg transition" title="Edit">
                          <Edit2 size={16} />
                        </button>
                        <button onClick={() => handleDelete(s.id)} className="p-1.5 text-red-500 hover:bg-red-500/10 rounded-lg transition" title="Delete">
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
          <div className="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
            <div className="p-5 border-b border-border-light dark:border-border-dark flex justify-between items-center bg-bg-light/30 dark:bg-bg-dark/30">
              <h2 className="text-lg font-bold text-text-light dark:text-white flex items-center gap-2">
                {modalMode === 'add' ? <Plus size={20} className="text-brand-500"/> : <Edit2 size={20} className="text-brand-500"/>} 
                {modalMode === 'add' ? 'Add Staff Member' : 'Edit Staff Member'}
              </h2>
              <button onClick={() => setIsModalOpen(false)} className="text-text-muted hover:text-text-light dark:text-text-mutedDark dark:hover:text-white transition">
                <X size={20} />
              </button>
            </div>
            
            <div className="p-5 overflow-y-auto">
              <form id="staffForm" onSubmit={handleSubmit} className="space-y-4">
                
                <div>
                  <label className={labelCls}>Username <span className="text-red-500">*</span></label>
                  <div className="flex items-center gap-2">
                    <div className="relative flex-1">
                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <UserCircle size={14} className={usernameStatus === 'available' ? 'text-emerald-500' : 'text-text-muted'} />
                      </div>
                      <input 
                        type="text" 
                        required
                        className={inputCls + " pl-9 " + (usernameStatus === 'available' ? 'border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-600 dark:text-emerald-400 focus:ring-emerald-500' : 'focus:ring-brand-500')}
                        value={form.username}
                        onChange={e => {
                          setForm({...form, username: e.target.value});
                          setUsernameStatus('idle');
                        }}
                      />
                    </div>
                    {modalMode === 'add' && (
                      <button 
                        type="button" 
                        onClick={() => handleCheckUsername(form.username)}
                        disabled={!form.username || usernameStatus === 'checking'}
                        className="px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-text-light dark:text-white rounded-lg text-sm font-medium transition flex items-center gap-2 disabled:opacity-50"
                      >
                        {usernameStatus === 'checking' ? <Loader2 size={16} className="animate-spin" /> : 'Check'}
                      </button>
                    )}
                  </div>
                  {usernameStatus === 'available' && (
                    <p className="text-xs text-emerald-500 font-medium mt-1 flex items-center gap-1">
                      <CheckCircle size={12} /> Username OK
                    </p>
                  )}
                </div>

                <div>
                  <label className={labelCls}>Full Name</label>
                  <input 
                    type="text" 
                    className={inputCls + " focus:ring-brand-500"}
                    value={form.full_name}
                    onChange={e => setForm({...form, full_name: e.target.value})}
                  />
                </div>

                <div>
                  <label className={labelCls}>Email Address <span className="text-red-500">*</span></label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <Mail size={14} className="text-text-muted" />
                    </div>
                    <input 
                      type="email" 
                      required
                      className={inputCls + " pl-9 focus:ring-brand-500"}
                      value={form.email}
                      onChange={e => setForm({...form, email: e.target.value})}
                    />
                  </div>
                </div>

                <div>
                  <label className={labelCls}>System Role *</label>
                  <select
                    className={inputCls + " focus:ring-brand-500"}
                    value={form.role || 'user'}
                    onChange={e => setForm({...form, role: e.target.value})}
                  >
                    <option value="user">Regular Staff (Limited Access)</option>
                    <option value="manager">Shop Admin (Full Access)</option>
                  </select>
                </div>

                <div>
                  <label className={labelCls}>
                    {modalMode === 'add' ? 'Password *' : 'New Password (leave blank to keep current)'}
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <Lock size={14} className="text-text-muted" />
                    </div>
                    <input 
                      type="password" 
                      required={modalMode === 'add'}
                      minLength={4}
                      className={inputCls + " pl-9 focus:ring-brand-500"}
                      value={form.password}
                      onChange={e => setForm({...form, password: e.target.value})}
                    />
                  </div>
                </div>

                {modalMode === 'edit' && (
                  <div>
                    <label className={labelCls}>Account Status</label>
                    <select 
                      className={inputCls + " focus:ring-brand-500"}
                      value={form.is_active}
                      onChange={e => setForm({...form, is_active: parseInt(e.target.value)})}
                    >
                      <option value={1}>Active - Can log in</option>
                      <option value={0}>Inactive - Access disabled</option>
                    </select>
                  </div>
                )}

              </form>
            </div>
            
            <div className="p-5 border-t border-border-light dark:border-border-dark bg-bg-light/30 dark:bg-bg-dark/30 flex justify-end gap-3">
              <button 
                type="button" 
                onClick={() => setIsModalOpen(false)}
                className="px-4 py-2 rounded-lg text-sm font-medium text-text-light dark:text-white bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark hover:bg-gray-50 dark:hover:bg-white/5 transition"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                form="staffForm"
                disabled={submitting}
                className="px-4 py-2 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 transition flex items-center gap-2 shadow-lg shadow-brand-500/20 disabled:opacity-70"
              >
                {submitting && <Loader2 size={16} className="animate-spin" />}
                {modalMode === 'add' ? 'Save Staff' : 'Update Staff'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
