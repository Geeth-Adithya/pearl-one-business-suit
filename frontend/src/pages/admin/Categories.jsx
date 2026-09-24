import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Tag, Plus, Edit, Trash2, Loader2, ArrowLeft, CheckCircle, XCircle } from 'lucide-react';
import { Link } from 'react-router-dom';

const API_URL = 'http://localhost:8000/api';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";

export default function Categories() {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [toast, setToast] = useState(null);

  const [editId, setEditId] = useState(null);
  const [editName, setEditName] = useState('');
  
  const [newName, setNewName] = useState('');

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 3000);
  };

  const fetchCategories = async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/categories.php`);
      if (res.data.success) setCategories(res.data.categories);
    } catch { showToast('Failed to load categories', 'error'); }
    setLoading(false);
  };

  useEffect(() => { fetchCategories(); }, []);

  const handleCreate = async (e) => {
    e.preventDefault();
    if (!newName.trim()) return;
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/categories.php`, { action: 'create', name: newName });
      if (res.data.success) {
        setNewName('');
        showToast(res.data.message);
        fetchCategories();
      } else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
    setSubmitting(false);
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    if (!editName.trim()) return;
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/categories.php`, { action: 'update', id: editId, name: editName });
      if (res.data.success) {
        setEditId(null);
        showToast(res.data.message);
        fetchCategories();
      } else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
    setSubmitting(false);
  };

  const handleDelete = async (id, name) => {
    if (!window.confirm(`Delete category "${name}"?`)) return;
    try {
      const res = await axios.post(`${API_URL}/categories.php`, { action: 'delete', id });
      if (res.data.success) {
        showToast(res.data.message);
        fetchCategories();
      } else showToast(res.data.message, 'error');
    } catch { showToast('Server error', 'error'); }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-300 max-w-4xl mx-auto">
      {/* Toast */}
      {toast && (
        <div className={`fixed top-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl flex items-center gap-3 text-white text-sm font-medium ${toast.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'}`}>
          {toast.type === 'error' ? <XCircle size={18} /> : <CheckCircle size={18} />}
          {toast.message}
        </div>
      )}

      {/* Header */}
      <div className="flex items-center gap-4">
        <Link to="/inventory" className="p-2 rounded-lg bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark transition">
          <ArrowLeft size={20} className="text-text-muted" />
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
            <Tag className="text-brand-500" size={26} />
            Manage Categories
          </h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm mt-1">Organize your products into categories.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Create Form */}
        <div className="glass-card p-6 shadow-xl h-fit">
          <h2 className="text-base font-semibold text-text-light dark:text-white mb-4 border-b border-border-light dark:border-border-dark pb-3">Add Category</h2>
          <form onSubmit={handleCreate} className="space-y-4">
            <div>
              <label className="block text-xs font-medium text-text-muted dark:text-text-mutedDark mb-1">Category Name</label>
              <input value={newName} onChange={e => setNewName(e.target.value)} required className={inputCls} placeholder="e.g. Electronics" />
            </div>
            <button type="submit" disabled={submitting || !newName.trim()} className="w-full flex justify-center items-center gap-2 bg-brand-500 hover:bg-brand-700 disabled:opacity-60 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-brand-500/20">
              {submitting ? <Loader2 size={16} className="animate-spin" /> : <Plus size={16} />}
              Add Category
            </button>
          </form>
        </div>

        {/* List */}
        <div className="md:col-span-2 glass-card p-6 shadow-xl">
          <h2 className="text-base font-semibold text-text-light dark:text-white mb-4 border-b border-border-light dark:border-border-dark pb-3">All Categories</h2>
          
          {loading ? (
            <div className="flex justify-center py-10"><Loader2 className="animate-spin text-brand-500" size={28} /></div>
          ) : (
            <div className="space-y-3">
              {categories.map(cat => (
                <div key={cat.id} className="flex items-center justify-between p-3 rounded-lg border border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark transition">
                  {editId === cat.id ? (
                    <form onSubmit={handleUpdate} className="flex items-center gap-2 w-full">
                      <input autoFocus value={editName} onChange={e => setEditName(e.target.value)} className={`${inputCls} flex-1`} />
                      <button type="submit" disabled={submitting} className="p-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition"><CheckCircle size={16} /></button>
                      <button type="button" onClick={() => setEditId(null)} className="p-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition"><XCircle size={16} /></button>
                    </form>
                  ) : (
                    <>
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-500 flex items-center justify-center">
                          <Tag size={16} />
                        </div>
                        <span className="font-medium text-text-light dark:text-white">{cat.name}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <button onClick={() => { setEditId(cat.id); setEditName(cat.name); }} className="p-1.5 rounded-lg bg-brand-500/10 text-brand-500 hover:bg-brand-500/20 transition">
                          <Edit size={16} />
                        </button>
                        <button onClick={() => handleDelete(cat.id, cat.name)} className="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition">
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </>
                  )}
                </div>
              ))}
              {categories.length === 0 && <p className="text-center py-6 text-text-muted">No categories created yet.</p>}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

