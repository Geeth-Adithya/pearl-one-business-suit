import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Wallet, Plus, Search, Edit2, Trash2, X, Loader2, DollarSign, Calendar, Tag, FileText, Filter } from 'lucide-react';

const API_URL = 'http://localhost:8000/api';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";
const labelCls = "block text-xs font-medium text-text-muted dark:text-text-mutedDark mb-1";

const categoryColors = {
  'Rent': 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
  'Utilities': 'bg-cyan-100 text-cyan-700 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20',
  'Salaries': 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
  'Supplies': 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
  'Marketing': 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
  'Maintenance': 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
  'Taxes': 'bg-red-100 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
  'Other': 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:border-gray-500/20'
};

const getCategoryColor = (category) => {
  return categoryColors[category] || categoryColors['Other'];
};

export default function Expenses() {
  const [expenses, setExpenses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All');
  
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState('add');
  const [submitting, setSubmitting] = useState(false);
  
  const [form, setForm] = useState({
    id: null,
    category: '',
    amount: '',
    expense_date: new Date().toISOString().split('T')[0],
    description: ''
  });

  const categories = ['Rent', 'Utilities', 'Salaries', 'Supplies', 'Marketing', 'Maintenance', 'Taxes', 'Other'];

  const fetchExpenses = () => {
    setLoading(true);
    axios.get(`${API_URL}/expenses.php?action=list`)
      .then(res => {
        if (res.data.success) {
          setExpenses(res.data.expenses);
        }
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    fetchExpenses();
  }, []);

  const openAddModal = () => {
    setModalMode('add');
    setForm({
      id: null,
      category: categories[0],
      amount: '',
      expense_date: new Date().toISOString().split('T')[0],
      description: ''
    });
    setIsModalOpen(true);
  };

  const openEditModal = (exp) => {
    setModalMode('edit');
    setForm({
      id: exp.id,
      category: exp.category,
      amount: exp.amount,
      expense_date: exp.expense_date,
      description: exp.description || ''
    });
    setIsModalOpen(true);
  };

  const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this expense?")) return;
    try {
      const res = await axios.post(`${API_URL}/expenses.php`, { action: 'delete', id });
      if (res.data.success) {
        fetchExpenses();
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert("Error deleting expense");
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/expenses.php`, {
        action: modalMode === 'add' ? 'create' : 'update',
        ...form
      });
      if (res.data.success) {
        setIsModalOpen(false);
        fetchExpenses();
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert("Error saving expense");
    }
    setSubmitting(false);
  };

  const filteredExpenses = expenses.filter(exp => {
    const matchesSearch = exp.category.toLowerCase().includes(searchQuery.toLowerCase()) ||
                          (exp.description && exp.description.toLowerCase().includes(searchQuery.toLowerCase()));
    const matchesCategory = selectedCategory === 'All' || exp.category === selectedCategory;
    
    return matchesSearch && matchesCategory;
  });

  const totalAmount = filteredExpenses.reduce((sum, exp) => sum + parseFloat(exp.amount), 0);

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-2">
            <Wallet className="text-brand-500" /> Expenses
          </h1>
          <p className="text-sm text-text-muted dark:text-text-mutedDark">Track and manage your business expenses.</p>
        </div>
        <button 
          onClick={openAddModal}
          className="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-lg shadow-brand-500/20 w-fit"
        >
          <Plus size={18} /> Add Expense
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="md:col-span-2 glass-card p-2 flex items-center gap-2">
          <Search className="text-text-muted dark:text-text-mutedDark ml-2 min-w-fit" size={18} />
          <input 
            type="text" 
            placeholder="Search expenses by category or description..." 
            className="w-full bg-transparent border-none text-text-light dark:text-white text-sm focus:ring-0 px-2"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
        
        <div className="glass-card p-2 flex items-center gap-2">
          <Filter className="text-text-muted dark:text-text-mutedDark ml-2 min-w-fit" size={18} />
          <select 
            className="w-full bg-transparent border-none text-text-light dark:text-white text-sm focus:ring-0 px-2 outline-none cursor-pointer"
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
          >
            <option value="All" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">All Categories</option>
            {categories.map(c => <option key={c} value={c} className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">{c}</option>)}
          </select>
        </div>

        <div className="glass-card p-4 flex items-center justify-between border-l-4 border-l-brand-500">
          <div>
            <p className="text-xs text-text-muted dark:text-text-mutedDark font-medium mb-1">Total (Filtered)</p>
            <p className="text-lg font-bold text-text-light dark:text-white">
              Rs. {totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </p>
          </div>
        </div>
      </div>

      <div className="glass-card rounded-xl overflow-hidden border border-border-light dark:border-border-dark">
        {loading ? (
          <div className="flex justify-center p-12">
            <Loader2 className="animate-spin text-brand-500" size={32} />
          </div>
        ) : filteredExpenses.length === 0 ? (
          <div className="p-12 text-center text-text-muted dark:text-text-mutedDark">
            <Wallet size={48} className="mx-auto mb-4 opacity-20" />
            <p>No expenses found.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-bg-light/50 dark:bg-bg-dark/50 border-b border-border-light dark:border-border-dark text-xs uppercase text-text-muted dark:text-text-mutedDark font-semibold">
                  <th className="p-4">Date</th>
                  <th className="p-4">Category</th>
                  <th className="p-4">Description</th>
                  <th className="p-4 text-right">Amount (Rs)</th>
                  <th className="p-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredExpenses.map(exp => (
                  <tr key={exp.id} className="border-b border-border-light/50 dark:border-border-dark/50 hover:bg-bg-light/20 dark:hover:bg-bg-dark/20 transition group">
                    <td className="p-4 text-sm text-text-light dark:text-white whitespace-nowrap">
                      {new Date(exp.expense_date).toLocaleDateString()}
                    </td>
                    <td className="p-4">
                      <span className={"px-2.5 py-1 rounded-full text-xs font-medium border " + getCategoryColor(exp.category)}>
                        {exp.category}
                      </span>
                    </td>
                    <td className="p-4 text-sm text-text-muted dark:text-text-mutedDark truncate max-w-[200px]">
                      {exp.description || '-'}
                    </td>
                    <td className="p-4 text-sm font-semibold text-text-light dark:text-white text-right">
                      {parseFloat(exp.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </td>
                    <td className="p-4">
                      <div className="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onClick={() => openEditModal(exp)} className="p-1.5 text-blue-500 hover:bg-blue-500/10 rounded-lg transition" title="Edit">
                          <Edit2 size={16} />
                        </button>
                        <button onClick={() => handleDelete(exp.id)} className="p-1.5 text-red-500 hover:bg-red-500/10 rounded-lg transition" title="Delete">
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
                {modalMode === 'add' ? 'Add New Expense' : 'Edit Expense'}
              </h2>
              <button onClick={() => setIsModalOpen(false)} className="text-text-muted hover:text-text-light dark:text-text-mutedDark dark:hover:text-white transition">
                <X size={20} />
              </button>
            </div>
            
            <div className="p-5 overflow-y-auto">
              <form id="expenseForm" onSubmit={handleSubmit} className="space-y-4">
                
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className={labelCls}>Date <span className="text-red-500">*</span></label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Calendar size={14} className="text-text-muted" />
                      </div>
                      <input 
                        type="date" 
                        required
                        className={inputCls + " pl-9"}
                        value={form.expense_date}
                        onChange={e => setForm({...form, expense_date: e.target.value})}
                      />
                    </div>
                  </div>
                  <div>
                    <label className={labelCls}>Amount (Rs) <span className="text-red-500">*</span></label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <DollarSign size={14} className="text-text-muted" />
                      </div>
                      <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        required
                        placeholder="0.00"
                        className={inputCls + " pl-9"}
                        value={form.amount}
                        onChange={e => setForm({...form, amount: e.target.value})}
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <label className={labelCls}>Category <span className="text-red-500">*</span></label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <Tag size={14} className="text-text-muted" />
                    </div>
                    <select 
                      required
                      className={inputCls + " pl-9"}
                      value={form.category}
                      onChange={e => setForm({...form, category: e.target.value})}
                    >
                      {categories.map(c => <option key={c} value={c} className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">{c}</option>)}
                    </select>
                  </div>
                </div>

                <div>
                  <label className={labelCls}>Description</label>
                  <div className="relative">
                    <div className="absolute top-2.5 left-3 pointer-events-none">
                      <FileText size={14} className="text-text-muted" />
                    </div>
                    <textarea 
                      rows="3"
                      placeholder="Optional notes about this expense..."
                      className={inputCls + " pl-9 resize-none"}
                      value={form.description}
                      onChange={e => setForm({...form, description: e.target.value})}
                    ></textarea>
                  </div>
                </div>

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
                form="expenseForm"
                disabled={submitting}
                className="px-4 py-2 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 transition flex items-center gap-2 shadow-lg shadow-brand-500/20 disabled:opacity-70"
              >
                {submitting && <Loader2 size={16} className="animate-spin" />}
                {modalMode === 'add' ? 'Save Expense' : 'Update Expense'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
