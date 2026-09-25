import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Package, Plus, Search, Edit, Trash2, Filter, Loader2, ArrowRightLeft, Image as ImageIcon } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

const API_URL = 'http://localhost:8000/api';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";

export default function Inventory() {
  const { user } = useAuth();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalProducts, setTotalProducts] = useState(0);

  // For inline stock updates
  const [stockUpdating, setStockUpdating] = useState(null);

  const fetchProducts = useCallback(async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/products.php`, {
        params: { action: 'list', search, category: categoryFilter, page }
      });
      if (res.data.success) {
        setProducts(res.data.products);
        setCategories(res.data.categories);
        setTotalPages(res.data.total_pages);
        setTotalProducts(res.data.total);
      }
    } catch (e) { console.error('Failed to load products'); }
    setLoading(false);
  }, [search, categoryFilter, page]);

  useEffect(() => {
    // Add a slight debounce to search
    const timer = setTimeout(() => {
      setPage(1);
      fetchProducts();
    }, 400);
    return () => clearTimeout(timer);
  }, [search, categoryFilter, fetchProducts]);

  const handleDelete = async (id, name) => {
    if (!window.confirm(`Delete product "${name}"? This cannot be undone.`)) return;
    try {
      const res = await axios.post(`${API_URL}/products.php`, { action: 'delete', id });
      if (res.data.success) fetchProducts();
      else alert(res.data.message);
    } catch (e) { alert('Failed to delete'); }
  };

  const handleStockAdjust = async (id, change) => {
    if (change === 0) return;
    setStockUpdating(id);
    try {
      const res = await axios.post(`${API_URL}/products.php`, { action: 'stock', id, change });
      if (res.data.success) {
        setProducts(products.map(p => p.id === id ? { ...p, stock_quantity: res.data.new_qty } : p));
      }
    } catch (e) { alert('Failed to update stock'); }
    setStockUpdating(null);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-300">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
            <Package className="text-brand-500" size={26} />
            Inventory Management
          </h1>
          <p className="text-text-muted dark:text-text-mutedDark text-sm mt-1">Manage your products, stock, and categories.</p>
        </div>
        <div className="flex gap-2">
          <Link to="/inventory/categories" className="px-4 py-2 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark text-text-light dark:text-white rounded-lg text-sm font-medium hover:bg-bg-light dark:hover:bg-bg-dark transition">
            Categories
          </Link>
          <Link to="/inventory/add" className="flex items-center gap-2 bg-brand-500 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-lg shadow-brand-500/20">
            <Plus size={16} /> Add Product
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="glass-card p-4 shadow-sm flex flex-col md:flex-row gap-4 items-center">
        <div className="relative flex-1 w-full">
          <Search size={16} className="absolute left-3 top-2.5 text-text-muted" />
          <input 
            type="text" 
            placeholder="Search products by name or code..." 
            className={`${inputCls} pl-10`}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <div className="flex gap-2 w-full md:w-auto">
          <div className="relative w-full md:w-48">
            <Filter size={16} className="absolute left-3 top-2.5 text-text-muted" />
            <select 
              className={`${inputCls} pl-10`}
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value)}
            >
              <option value="">All Categories</option>
              {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </div>
        </div>
      </div>

      {/* Products Table */}
      <div className="glass-card shadow-xl overflow-hidden">
        {loading && products.length === 0 ? (
          <div className="flex justify-center py-12"><Loader2 className="animate-spin text-brand-500" size={32} /></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-text-muted dark:text-text-mutedDark uppercase bg-bg-light/50 dark:bg-bg-dark/50 border-b border-border-light dark:border-border-dark">
                <tr>
                  <th className="px-4 py-3">Product</th>
                  <th className="px-4 py-3">Code / Category</th>
                  <th className="px-4 py-3 text-right">Price</th>
                  <th className="px-4 py-3 text-center">Stock</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {products.map(p => (
                  <tr key={p.id} className="border-b border-border-light dark:border-border-dark hover:bg-bg-light/50 dark:hover:bg-bg-dark/50 transition">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-border-light dark:bg-border-dark flex items-center justify-center overflow-hidden shrink-0">
                          {p.image_url ? (
                            <img src={`http://localhost:8000/${p.image_url}`} alt={p.name} className="w-full h-full object-cover" />
                          ) : (
                            <ImageIcon size={20} className="text-text-muted" />
                          )}
                        </div>
                        <div>
                          <div className="font-medium text-text-light dark:text-white line-clamp-1">{p.name}</div>
                          {p.type === 'Variant' && <div className="text-xs text-text-muted mt-0.5">{p.attribute}</div>}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="font-mono text-xs text-text-light dark:text-gray-300">{p.item_code}</div>
                      <div className="text-xs text-brand-500 mt-0.5 truncate max-w-[150px]">{p.category_names || 'Uncategorized'}</div>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="font-medium text-text-light dark:text-white">Rs. {Number(p.selling_price).toFixed(2)}</div>
                      {p.purchasing_price > 0 && <div className="text-xs text-text-muted mt-0.5">Buy: Rs. {Number(p.purchasing_price).toFixed(2)}</div>}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button 
                          onClick={() => handleStockAdjust(p.id, -1)}
                            disabled={stockUpdating === p.id || p.stock_quantity <= 0}
                          className="w-6 h-6 rounded-full flex items-center justify-center bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-200 disabled:opacity-50"
                        >-</button>
                        <span className={`font-bold w-8 text-center ${p.stock_quantity <= p.low_stock_threshold ? 'text-red-500' : 'text-emerald-500'}`}>
                          {stockUpdating === p.id ? <Loader2 size={12} className="animate-spin mx-auto" /> : p.stock_quantity}
                        </span>
                        <button 
                          onClick={() => handleStockAdjust(p.id, 1)}
                          disabled={stockUpdating === p.id}
                          className="w-6 h-6 rounded-full flex items-center justify-center bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 hover:bg-emerald-200 disabled:opacity-50"
                        >+</button>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center justify-end gap-2">
                        <Link to={`/inventory/edit/${p.id}`} className="p-1.5 rounded-lg bg-brand-500/10 text-brand-500 hover:bg-brand-500/20 transition">
                          <Edit size={16} />
                        </Link>
                      {(user?.role === 'admin' || user?.role === 'manager') && (
                        <button onClick={() => handleDelete(p.id, p.name)} className="p-1.5 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition">
                          <Trash2 size={16} />
                        </button>
                      )}
                      </div>
                    </td>
                  </tr>
                ))}
                {products.length === 0 && !loading && (
                  <tr>
                    <td colSpan="5" className="text-center py-12">
                      <Package size={40} className="mx-auto text-text-muted mb-3 opacity-20" />
                      <p className="text-text-muted dark:text-text-mutedDark">No products found matching your search.</p>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
        
        {/* Pagination */}
        {totalPages > 1 && (
          <div className="p-4 border-t border-border-light dark:border-border-dark flex items-center justify-between">
            <div className="text-xs text-text-muted dark:text-text-mutedDark">
              Showing total {totalProducts} products
            </div>
            <div className="flex gap-1">
              <button disabled={page === 1} onClick={() => setPage(p => p - 1)} className="px-3 py-1 rounded border border-border-light dark:border-border-dark disabled:opacity-50 hover:bg-bg-light dark:hover:bg-bg-dark transition">Prev</button>
              <span className="px-3 py-1 text-sm">{page} / {totalPages}</span>
              <button disabled={page === totalPages} onClick={() => setPage(p => p + 1)} className="px-3 py-1 rounded border border-border-light dark:border-border-dark disabled:opacity-50 hover:bg-bg-light dark:hover:bg-bg-dark transition">Next</button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

