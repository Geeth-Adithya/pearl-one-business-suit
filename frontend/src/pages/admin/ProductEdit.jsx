import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Package, Upload, ArrowLeft, Save, Loader2, Image as ImageIcon } from 'lucide-react';
import { Link, useNavigate, useParams } from 'react-router-dom';

const API_URL = 'http://localhost:8000/api';
const BASE_URL = 'http://localhost:8000';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";
const labelCls = "block text-xs font-medium text-text-muted dark:text-text-mutedDark mb-1";

export default function ProductEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [uploading, setUploading] = useState(false);

  const [categories, setCategories] = useState([]);
  const [suppliers, setSuppliers] = useState([]);
  
  const [form, setForm] = useState(null);

  useEffect(() => {
    // Fetch form data (cats/suppliers) and product data simultaneously
    Promise.all([
      axios.get(`${API_URL}/products.php?action=form_data`),
      axios.get(`${API_URL}/products.php?action=get&id=${id}`)
    ]).then(([formRes, prodRes]) => {
      if (formRes.data.success) {
        setCategories(formRes.data.categories);
        setSuppliers(formRes.data.suppliers);
      }
      if (prodRes.data.success) {
        setForm(prodRes.data.product);
      } else {
        alert('Product not found');
        navigate('/inventory');
      }
    }).catch(() => {
      alert('Error loading data');
      navigate('/inventory');
    }).finally(() => setLoading(false));
  }, [id, navigate]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => {
      const updated = { ...prev, [name]: value };
      if (name === 'purchasing_price' || name === 'selling_price') {
        const buy = parseFloat(updated.purchasing_price) || 0;
        const sell = parseFloat(updated.selling_price) || 0;
        if (buy > 0 && sell >= buy) {
          updated.margin_percent = (((sell - buy) / buy) * 100).toFixed(2);
        } else {
          updated.margin_percent = '';
        }
      }
      if (name === 'margin_percent') {
        const buy = parseFloat(updated.purchasing_price) || 0;
        const marg = parseFloat(updated.margin_percent) || 0;
        if (buy > 0) {
          updated.selling_price = (buy + (buy * marg / 100)).toFixed(2);
        }
      }
      return updated;
    });
  };

  const handleCategoryToggle = (catId) => {
    setForm(prev => {
      const cidStr = catId.toString();
      const ids = prev.category_ids.includes(cidStr) 
        ? prev.category_ids.filter(c => c !== cidStr) 
        : [...prev.category_ids, cidStr];
      return { ...prev, category_ids: ids };
    });
  };

  const handleImageUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    setUploading(true);
    const formData = new FormData();
    formData.append('image', file);

    try {
      const res = await axios.post(`${API_URL}/upload.php`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      if (res.data.success) {
        setForm(f => ({ ...f, image_url: res.data.url }));
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert('Failed to upload image.');
    }
    setUploading(false);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const res = await axios.post(`${API_URL}/products.php`, { ...form, action: 'update' });
      if (res.data.success) {
        navigate('/inventory');
      } else {
        alert(res.data.message);
      }
    } catch (err) {
      alert('Server error while saving product.');
    }
    setSubmitting(false);
  };

  if (loading) return <div className="flex justify-center py-20"><Loader2 className="animate-spin text-brand-500" size={32} /></div>;
  if (!form) return null;

  return (
    <div className="space-y-6 animate-in fade-in duration-300 max-w-5xl mx-auto">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link to="/inventory" className="p-2 rounded-lg bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark transition">
            <ArrowLeft size={20} className="text-text-muted" />
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
              <Package className="text-brand-500" size={26} />
              Edit Product: {form.item_code}
            </h1>
            <p className="text-text-muted dark:text-text-mutedDark text-sm mt-1">Update product details and stock.</p>
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Image & Categories */}
        <div className="space-y-6">
          <div className="glass-card p-6 shadow-xl">
            <h2 className="text-base font-semibold text-text-light dark:text-white mb-4">Product Image</h2>
            <div className="flex flex-col items-center justify-center border-2 border-dashed border-border-light dark:border-border-dark rounded-xl p-6 hover:bg-bg-light/50 dark:hover:bg-bg-dark/50 transition relative overflow-hidden group">
              {form.image_url ? (
                <img src={`${BASE_URL}/${form.image_url}`} alt="Preview" className="w-full h-48 object-cover rounded-lg" />
              ) : (
                <div className="text-center">
                  <ImageIcon size={40} className="mx-auto text-text-muted mb-2" />
                  <p className="text-sm text-text-muted dark:text-text-mutedDark">Click to upload image</p>
                  <p className="text-xs text-text-muted/60 mt-1">JPG, PNG, WEBP (Max 5MB)</p>
                </div>
              )}
              <input 
                type="file" 
                accept="image/*" 
                onChange={handleImageUpload} 
                className="absolute inset-0 opacity-0 cursor-pointer"
                disabled={uploading}
              />
              {uploading && (
                <div className="absolute inset-0 bg-black/50 flex items-center justify-center rounded-xl">
                  <Loader2 size={30} className="animate-spin text-white" />
                </div>
              )}
            </div>
          </div>

          <div className="glass-card p-6 shadow-xl">
            <h2 className="text-base font-semibold text-text-light dark:text-white mb-4">Categories</h2>
            <div className="max-h-60 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
              {categories.map(cat => (
                <label key={cat.id} className="flex items-center gap-3 p-2 rounded hover:bg-bg-light dark:hover:bg-bg-dark cursor-pointer transition">
                  <input 
                    type="checkbox" 
                    checked={form.category_ids.includes(cat.id.toString())}
                    onChange={() => handleCategoryToggle(cat.id)}
                    className="w-4 h-4 text-brand-500 rounded border-gray-300 focus:ring-brand-500"
                  />
                  <span className="text-sm text-text-light dark:text-white">{cat.name}</span>
                </label>
              ))}
              {categories.length === 0 && <p className="text-sm text-text-muted">No categories available.</p>}
            </div>
          </div>
        </div>

        {/* Right Column: Details */}
        <div className="lg:col-span-2 space-y-6">
          <div className="glass-card p-6 shadow-xl space-y-4">
            <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3">Basic Info</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="md:col-span-2">
                <label className={labelCls}>Product Name <span className="text-red-500">*</span></label>
                <input name="name" value={form.name} onChange={handleChange} required className={inputCls} />
              </div>
              
              <div>
                <label className={labelCls}>Item Code</label>
                <input name="item_code" value={form.item_code} disabled className={`${inputCls} opacity-60 cursor-not-allowed`} />
              </div>

              <div>
                <label className={labelCls}>Product Type</label>
                <select name="type" value={form.type} onChange={handleChange} className={inputCls}>
                  <option value="Single">Single Item</option>
                  <option value="Variant">Variant (Size/Color)</option>
                </select>
              </div>

              {form.type === 'Variant' && (
                <div className="md:col-span-2">
                  <label className={labelCls}>Variant Attribute</label>
                  <input name="attribute" value={form.attribute} onChange={handleChange} className={inputCls} />
                </div>
              )}
            </div>
          </div>

          <div className="glass-card p-6 shadow-xl space-y-4">
            <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3">Pricing & Inventory</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className={labelCls}>Purchasing Price (Rs.)</label>
                <input type="number" step="0.01" min="0" name="purchasing_price" value={form.purchasing_price} onChange={handleChange} className={inputCls} />
              </div>
              <div>
                <label className={labelCls}>Margin (%)</label>
                <input type="number" step="0.01" name="margin_percent" value={form.margin_percent} onChange={handleChange} className={inputCls} />
              </div>
              <div>
                <label className={labelCls}>Selling Price (Rs.) <span className="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="selling_price" value={form.selling_price} onChange={handleChange} required className={inputCls} />
              </div>

              <div>
                <label className={labelCls}>Stock Quantity</label>
                <input type="number" name="stock_quantity" value={form.stock_quantity} onChange={handleChange} className={inputCls} />
              </div>
              <div>
                <label className={labelCls}>Low Stock Alert At</label>
                <input type="number" min="0" name="low_stock_threshold" value={form.low_stock_threshold} onChange={handleChange} className={inputCls} />
              </div>
              <div>
                <label className={labelCls}>Unit</label>
                <input name="unit" value={form.unit} onChange={handleChange} className={inputCls} />
              </div>
            </div>
          </div>

          <div className="glass-card p-6 shadow-xl space-y-4">
            <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3">Additional Details</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="md:col-span-2">
                <label className={labelCls}>Supplier</label>
                <select name="supplier_name" value={form.supplier_name || ''} onChange={handleChange} className={inputCls}>
                  <option value="">-- No Supplier --</option>
                  {suppliers.map(s => <option key={s.id} value={s.name}>{s.name}</option>)}
                </select>
              </div>

              <div className="md:col-span-2">
                <label className={labelCls}>Search Keywords (Optional)</label>
                <input name="search_keywords" value={form.search_keywords || ''} onChange={handleChange} className={inputCls} />
              </div>

              <div className="md:col-span-2">
                <label className={labelCls}>Description</label>
                <textarea name="description" value={form.description || ''} onChange={handleChange} rows="3" className={inputCls}></textarea>
              </div>
            </div>
          </div>

          <div className="flex justify-end gap-3">
            <Link to="/inventory" className="px-6 py-2.5 rounded-lg border border-border-light dark:border-border-dark text-text-light dark:text-white text-sm font-medium hover:bg-bg-light dark:hover:bg-bg-dark transition">
              Cancel
            </Link>
            <button type="submit" disabled={submitting} className="flex items-center gap-2 bg-brand-500 hover:bg-brand-700 disabled:opacity-60 text-white px-8 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-brand-500/20">
              {submitting ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
              Save Changes
            </button>
          </div>
        </div>
      </form>
    </div>
  );
}

