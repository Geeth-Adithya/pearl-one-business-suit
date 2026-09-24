import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Plus, Edit2, Trash2, Link as LinkIcon, Search, ArrowLeft, Copy, Check, PackagePlus, Send } from 'lucide-react';

const API_URL = 'http://localhost:8000/api';

export default function Suppliers() {
  const [view, setView] = useState('list'); // 'list', 'add', 'edit', 'assign'
  const [suppliers, setSuppliers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  
  // Form State
  const [formData, setFormData] = useState({ id: '', name: '', phone: '', email: '', address: '', product_types: '' });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [copiedLink, setCopiedLink] = useState(null);

  // Assign Products State
  const [selectedSupplier, setSelectedSupplier] = useState(null);
  const [assignProductsList, setAssignProductsList] = useState([]);
  const [assignSearch, setAssignSearch] = useState('');
  const [assignLoading, setAssignLoading] = useState(false);

  // Request State
  const [requestData, setRequestData] = useState({ product_id: '', quantity: '', note: '' });
  const [requestLoading, setRequestLoading] = useState(false);

  useEffect(() => {
    if (view === 'list') {
      fetchSuppliers();
    }
  }, [view]);

  const fetchSuppliers = async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/suppliers.php?action=list`, { withCredentials: true });
      if (res.data.success) {
        setSuppliers(res.data.suppliers);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    
    try {
      const action = view === 'add' ? 'add' : 'update';
      const res = await axios.post(`${API_URL}/suppliers.php`, { action, ...formData }, { withCredentials: true });
      
      if (res.data.success) {
        setSuccess(res.data.message);
        setTimeout(() => setView('list'), 1000);
      } else {
        setError(res.data.message);
      }
    } catch (err) {
      setError('Server error occurred');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this supplier?')) return;
    try {
      const res = await axios.post(`${API_URL}/suppliers.php`, { action: 'delete', id }, { withCredentials: true });
      if (res.data.success) {
        fetchSuppliers();
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert('Delete failed');
    }
  };

  const generateLink = async (id) => {
    try {
      const res = await axios.post(`${API_URL}/suppliers.php`, { action: 'generate_link', id }, { withCredentials: true });
      if (res.data.success) {
        fetchSuppliers();
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert('Failed to generate link');
    }
  };

  const copyToClipboard = (token, id) => {
    const link = `${window.location.origin}/supplier-portal/${token}`;
    navigator.clipboard.writeText(link);
    setCopiedLink(id);
    setTimeout(() => setCopiedLink(null), 2000);
  };

  const openAddForm = () => {
    setFormData({ id: '', name: '', phone: '', email: '', address: '', product_types: '' });
    setError('');
    setSuccess('');
    setView('add');
  };

  const openEditForm = (supplier) => {
    setFormData({ ...supplier });
    setError('');
    setSuccess('');
    setView('edit');
  };

  const openAssignForm = async (supplier) => {
    setSelectedSupplier(supplier);
    setAssignSearch('');
    setView('assign');
    setAssignLoading(true);
    try {
      const res = await axios.post(`${API_URL}/suppliers.php`, { action: 'get_assigned_products', supplier_id: supplier.id }, { withCredentials: true });
      if (res.data.success) {
        setAssignProductsList(res.data.products);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setAssignLoading(false);
    }
  };

  const toggleProductAssign = async (productId, currentStatus) => {
    const newStatus = !currentStatus;
    // Optimistic update
    setAssignProductsList(prev => prev.map(p => p.id === productId ? { ...p, is_assigned: newStatus } : p));
    
    try {
      const res = await axios.post(`${API_URL}/suppliers.php`, { 
        action: 'toggle_product_assignment', 
        supplier_id: selectedSupplier.id, 
        product_id: productId, 
        is_assigned: newStatus 
      }, { withCredentials: true });
      
      if (!res.data.success) {
        // Revert on failure
        setAssignProductsList(prev => prev.map(p => p.id === productId ? { ...p, is_assigned: currentStatus } : p));
      }
    } catch (err) {
      setAssignProductsList(prev => prev.map(p => p.id === productId ? { ...p, is_assigned: currentStatus } : p));
    }
  };

  const openRequestForm = async (supplier) => {
    setSelectedSupplier(supplier);
    setRequestData({ product_id: '', quantity: '', note: '' });
    setView('request');
    setRequestLoading(true);
    try {
      const res = await axios.post(`${API_URL}/suppliers.php`, { action: 'get_assigned_products', supplier_id: supplier.id }, { withCredentials: true });
      if (res.data.success) {
        setAssignProductsList(res.data.products.filter(p => p.is_assigned)); // Only assigned products
      }
    } catch (err) {
      console.error(err);
    } finally {
      setRequestLoading(false);
    }
  };

  const handleSendRequest = async (e) => {
    e.preventDefault();
    try {
      const res = await axios.post(`${API_URL}/suppliers.php`, {
        action: 'create_request',
        supplier_id: selectedSupplier.id,
        ...requestData
      }, { withCredentials: true });
      
      if (res.data.success) {
        alert('Request sent to supplier successfully!');
        setView('list');
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert('Failed to send request');
    }
  };

  const filteredSuppliers = suppliers.filter(s => 
    s.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
    (s.phone && s.phone.includes(searchTerm))
  );

  const filteredAssignProducts = assignProductsList.filter(p => 
    p.name.toLowerCase().includes(assignSearch.toLowerCase()) || 
    (p.item_code && p.item_code.toLowerCase().includes(assignSearch.toLowerCase()))
  );

  if (view === 'request') {
    return (
      <div className="max-w-xl mx-auto">
        <button onClick={() => setView('list')} className="flex items-center gap-2 text-text-muted hover:text-brand-500 mb-6 transition">
          <ArrowLeft size={20} /> Back to Suppliers
        </button>
        <div className="glass-card p-8">
          <h2 className="text-2xl font-bold mb-2 text-brand-900 dark:text-white">Send Supply Request</h2>
          <p className="text-text-muted dark:text-text-mutedDark mb-6 text-sm">Notify {selectedSupplier?.name} to supply specific products urgently.</p>
          
          {requestLoading ? (
            <div className="flex justify-center p-10"><div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div></div>
          ) : assignProductsList.length === 0 ? (
            <div className="p-4 bg-orange-50 text-orange-600 rounded-lg text-sm text-center">
              This supplier has no assigned products. Please assign products first before making a request.
            </div>
          ) : (
            <form onSubmit={handleSendRequest} className="space-y-5">
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Select Product *</label>
                <select 
                  required 
                  className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                  value={requestData.product_id} 
                  onChange={e => setRequestData({...requestData, product_id: e.target.value})}
                >
                  <option value="">-- Choose Product --</option>
                  {assignProductsList.map(p => (
                    <option key={p.id} value={p.id}>{p.name} {p.item_code ? `(${p.item_code})` : ''}</option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Quantity Needed *</label>
                <input 
                  type="number" 
                  min="1" 
                  required 
                  className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                  value={requestData.quantity} 
                  onChange={e => setRequestData({...requestData, quantity: e.target.value})} 
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Additional Note (Optional)</label>
                <textarea 
                  rows="3"
                  placeholder="e.g. Need this urgently by tomorrow morning."
                  className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                  value={requestData.note} 
                  onChange={e => setRequestData({...requestData, note: e.target.value})} 
                />
              </div>

              <div className="pt-4 flex justify-end gap-3">
                <button type="button" onClick={() => setView('list')} className="px-5 py-2.5 rounded-lg border border-border-light dark:border-border-dark text-text-muted hover:bg-bg-light dark:hover:bg-bg-dark transition">
                  Cancel
                </button>
                <button type="submit" className="px-5 py-2.5 rounded-lg bg-orange-500 text-white font-medium hover:bg-orange-600 transition shadow-lg shadow-orange-500/30 flex items-center gap-2">
                  Send Request
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    );
  }

  if (view === 'assign') {
    return (
      <div className="max-w-3xl mx-auto">
        <button 
          onClick={() => setView('list')}
          className="flex items-center gap-2 text-text-muted hover:text-brand-500 mb-6 transition"
        >
          <ArrowLeft size={20} /> Back to Suppliers
        </button>
        
        <div className="glass-card p-8">
          <h2 className="text-2xl font-bold mb-2 text-brand-900 dark:text-white">
            Assign Products to {selectedSupplier?.name}
          </h2>
          <p className="text-text-muted dark:text-text-mutedDark mb-6 text-sm">
            Select which inventory items this supplier provides. They will only see these items in their portal.
          </p>

          <div className="relative w-full mb-6">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" size={20} />
            <input 
              type="text" 
              placeholder="Search products..." 
              value={assignSearch}
              onChange={(e) => setAssignSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
            />
          </div>

          {assignLoading ? (
            <div className="flex justify-center p-10"><div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div></div>
          ) : (
            <div className="border border-border-light dark:border-border-dark rounded-lg overflow-hidden max-h-96 overflow-y-auto">
              <table className="w-full text-sm text-left">
                <thead className="bg-bg-light dark:bg-bg-dark text-text-muted dark:text-text-mutedDark sticky top-0">
                  <tr>
                    <th className="px-4 py-3 font-medium">Product Name</th>
                    <th className="px-4 py-3 font-medium">Item Code</th>
                    <th className="px-4 py-3 font-medium text-right">Assigned</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border-light dark:divide-border-dark">
                  {filteredAssignProducts.map(p => (
                    <tr key={p.id} className="hover:bg-bg-light dark:hover:bg-bg-dark/50 transition">
                      <td className="px-4 py-3 text-text-light dark:text-white font-medium">{p.name}</td>
                      <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark">{p.item_code || '-'}</td>
                      <td className="px-4 py-3 text-right">
                        <label className="relative inline-flex items-center cursor-pointer">
                          <input 
                            type="checkbox" 
                            className="sr-only peer" 
                            checked={!!p.is_assigned}
                            onChange={() => toggleProductAssign(p.id, !!p.is_assigned)}
                          />
                          <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-500"></div>
                        </label>
                      </td>
                    </tr>
                  ))}
                  {filteredAssignProducts.length === 0 && (
                    <tr>
                      <td colSpan="3" className="px-4 py-8 text-center text-text-muted">No products found.</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    );
  }

  if (view === 'add' || view === 'edit') {
    return (
      <div className="max-w-2xl mx-auto">
        <button 
          onClick={() => setView('list')}
          className="flex items-center gap-2 text-text-muted hover:text-brand-500 mb-6 transition"
        >
          <ArrowLeft size={20} /> Back to Suppliers
        </button>
        
        <div className="glass-card p-8">
          <h2 className="text-2xl font-bold mb-6 text-brand-900 dark:text-white">
            {view === 'add' ? 'Add New Supplier' : 'Edit Supplier'}
          </h2>
          
          {error && <div className="bg-red-50 text-red-500 p-3 rounded-lg mb-4 text-sm">{error}</div>}
          {success && <div className="bg-emerald-50 text-emerald-500 p-3 rounded-lg mb-4 text-sm">{success}</div>}
          
          <form onSubmit={handleSubmit} className="space-y-5" autoComplete="off">
            <div>
              <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Supplier Name *</label>
              <input 
                type="text" 
                required 
                className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                value={formData.name} 
                onChange={e => setFormData({...formData, name: e.target.value})} 
                autoComplete="off"
              />
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Phone</label>
                <input 
                  type="text" 
                  className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                  value={formData.phone} 
                  onChange={e => setFormData({...formData, phone: e.target.value})} 
                  autoComplete="off"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Email</label>
                <input 
                  type="email" 
                  className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                  value={formData.email} 
                  onChange={e => setFormData({...formData, email: e.target.value})} 
                  autoComplete="off"
                />
              </div>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Address</label>
              <input 
                type="text" 
                className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                value={formData.address} 
                onChange={e => setFormData({...formData, address: e.target.value})} 
                autoComplete="off"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-text-light dark:text-white mb-1">Product Types / Description</label>
              <textarea 
                rows="3"
                className="w-full p-2.5 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
                value={formData.product_types} 
                onChange={e => setFormData({...formData, product_types: e.target.value})} 
                autoComplete="off"
              />
            </div>

            <div className="pt-4 flex justify-end gap-3">
              <button type="button" onClick={() => setView('list')} className="px-5 py-2.5 rounded-lg border border-border-light dark:border-border-dark text-text-muted hover:bg-bg-light dark:hover:bg-bg-dark transition">
                Cancel
              </button>
              <button type="submit" className="px-5 py-2.5 rounded-lg bg-brand-500 text-white font-medium hover:bg-brand-600 transition shadow-lg shadow-brand-500/30">
                {view === 'add' ? 'Save Supplier' : 'Update Supplier'}
              </button>
            </div>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-brand-900 dark:text-white">Supply Management</h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm mt-1">Manage suppliers and generate product submission links.</p>
        </div>
        <button 
          onClick={openAddForm}
          className="flex items-center gap-2 bg-brand-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-brand-600 transition shadow-lg shadow-brand-500/30"
        >
          <Plus size={20} /> Add Supplier
        </button>
      </div>

      <div className="glass-card p-5 mb-6">
        <div className="relative w-full md:w-96">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" size={20} />
          <input 
            type="text" 
            placeholder="Search suppliers by name or phone..." 
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 bg-bg-light dark:bg-bg-dark border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-brand-500 outline-none text-text-light dark:text-white"
          />
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center p-10"><div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div></div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {filteredSuppliers.map(supplier => (
            <div key={supplier.id} className="glass-card p-5 flex flex-col hover:border-brand-500/50 transition">
              <div className="flex justify-between items-start mb-3">
                <h3 className="font-bold text-lg text-brand-900 dark:text-white truncate">{supplier.name}</h3>
                <div className="flex gap-1">
                  <button onClick={() => openRequestForm(supplier)} className="p-1.5 text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-500/10 rounded-md transition" title="Send Request">
                    <Send size={16} />
                  </button>
                  <button onClick={() => openAssignForm(supplier)} className="p-1.5 text-purple-500 hover:bg-purple-50 dark:hover:bg-purple-500/10 rounded-md transition" title="Assign Products">
                    <PackagePlus size={16} />
                  </button>
                  <button onClick={() => openEditForm(supplier)} className="p-1.5 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-md transition" title="Edit">
                    <Edit2 size={16} />
                  </button>
                  <button onClick={() => handleDelete(supplier.id)} className="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-md transition" title="Delete">
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
              
              <div className="text-sm text-text-muted dark:text-text-mutedDark space-y-1 mb-4 flex-1">
                {supplier.phone && <p>📞 {supplier.phone}</p>}
                {supplier.email && <p>📧 {supplier.email}</p>}
                {supplier.product_types && <p className="mt-2 text-xs opacity-80 border-t border-border-light dark:border-border-dark pt-2 line-clamp-2">{supplier.product_types}</p>}
              </div>

              <div className="pt-3 border-t border-border-light dark:border-border-dark mt-auto">
                {supplier.active_link ? (
                  <div className="flex flex-col gap-2">
                    <div className="text-xs text-emerald-500 font-medium flex items-center gap-1"><LinkIcon size={12}/> Active Portal Link</div>
                    <button 
                      onClick={() => copyToClipboard(supplier.active_link, supplier.id)}
                      className={`flex justify-center items-center gap-2 w-full py-1.5 rounded text-sm font-medium transition ${copiedLink === supplier.id ? 'bg-emerald-500 text-white' : 'bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-500/20'}`}
                    >
                      {copiedLink === supplier.id ? <><Check size={16}/> Copied!</> : <><Copy size={16}/> Copy Link</>}
                    </button>
                  </div>
                ) : (
                  <button 
                    onClick={() => generateLink(supplier.id)}
                    className="w-full py-1.5 border border-dashed border-brand-500 text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10 rounded text-sm font-medium transition"
                  >
                    Generate Portal Link
                  </button>
                )}
              </div>
            </div>
          ))}
          {filteredSuppliers.length === 0 && (
            <div className="col-span-full text-center p-10 text-text-muted glass-card">
              No suppliers found. Click "Add Supplier" to create one.
            </div>
          )}
        </div>
      )}
    </div>
  );
}
