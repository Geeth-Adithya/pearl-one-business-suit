import React, { useState, useEffect, useMemo } from 'react';
import axios from 'axios';
import { Search, ShoppingCart, Plus, Minus, Trash2, User, Phone, CheckCircle, Image as ImageIcon, Loader2 } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';

const API_URL = 'http://localhost:8000/api';
const BASE_URL = 'http://localhost:8000';

const inputCls = "w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition";

export default function POS() {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  
  const [search, setSearch] = useState('');
  const [activeCategory, setActiveCategory] = useState('');

  const [cart, setCart] = useState([]);
  const [customerName, setCustomerName] = useState('');
  const [contactNo, setContactNo] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('Cash');
  const [cashTendered, setCashTendered] = useState('');
  const [cardType, setCardType] = useState('Visa');
  const [cardLastFour, setCardLastFour] = useState('');
  
  const [processing, setProcessing] = useState(false);
  const [successOrder, setSuccessOrder] = useState(null);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_URL}/pos.php`);
      if (res.data.success) {
        setProducts(res.data.products);
        setCategories(res.data.categories);
      }
    } catch (e) {
      console.error(e);
    }
    setLoading(false);
  };

  const filteredProducts = useMemo(() => {
    return products.filter(p => {
      const matchSearch = p.name.toLowerCase().includes(search.toLowerCase()) || (p.item_code && p.item_code.toLowerCase().includes(search.toLowerCase()));
      const matchCat = activeCategory ? p.category_name?.includes(activeCategory) : true;
      return matchSearch && matchCat;
    });
  }, [products, search, activeCategory]);

  const addToCart = (product) => {
    if (product.stock_quantity <= 0) {
      alert("This item is out of stock!");
      return;
    }
    setCart(prev => {
      const existing = prev.find(item => item.id === product.id);
      if (existing) {
        if (existing.qty + 1 > product.stock_quantity) {
          alert(`Cannot add more. Only ${product.stock_quantity} in stock.`);
          return prev;
        }
        return prev.map(item => item.id === product.id ? { ...item, qty: item.qty + 1 } : item);
      }
      return [...prev, { ...product, qty: 1 }];
    });
  };

  const updateQty = (id, change) => {
    setCart(prev => prev.map(item => {
      if (item.id === id) {
        let newQty = item.qty + change;
        if (newQty > item.stock_quantity) {
          alert(`Cannot exceed available stock (${item.stock_quantity}).`);
          newQty = item.stock_quantity;
        }
        newQty = Math.max(1, newQty);
        return { ...item, qty: newQty };
      }
      return item;
    }));
  };

  const removeFromCart = (id) => {
    setCart(prev => prev.filter(item => item.id !== id));
  };

  const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.selling_price) * item.qty), 0);
  const total = subtotal; // Can add tax/discount logic here later

  const handleCheckout = async () => {
    if (cart.length === 0) return;
    
    if (paymentMethod === 'Card') {
      if (cardLastFour.length !== 4) {
        alert("Please enter the last 4 digits of the card.");
        return;
      }
    }

    setProcessing(true);
    try {
      const payload = {
        action: 'checkout',
        customer_name: customerName,
        contact_no: contactNo,
        payment_method: paymentMethod,
        card_type: paymentMethod === 'Card' ? cardType : null,
        card_last_four: paymentMethod === 'Card' ? cardLastFour : null,
        items: cart.map(c => ({ id: c.id, qty: c.qty }))
      };
      const res = await axios.post(`${API_URL}/pos.php`, payload);
      if (res.data.success) {
        setSuccessOrder(res.data.order_id);
        setCart([]);
        setCustomerName('');
        setContactNo('');
        fetchProducts(); // Refresh stock
      } else {
        alert(res.data.message);
      }
    } catch (e) {
      alert('Checkout failed.');
    }
    setProcessing(false);
  };

  if (successOrder) {
    return (
      <div className="flex flex-col items-center justify-center h-full max-w-md mx-auto py-20 text-center animate-in fade-in zoom-in duration-300">
        <div className="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center mb-6 shadow-xl shadow-emerald-500/20">
          <CheckCircle size={40} className="text-white" />
        </div>
        <h2 className="text-3xl font-bold text-text-light dark:text-white mb-2">Order Completed!</h2>
        <p className="text-text-muted dark:text-text-mutedDark mb-8">Order #{successOrder} has been successfully processed.</p>
        
        <div className="flex gap-4 w-full">
          <button onClick={() => setSuccessOrder(null)} className="flex-1 py-3 bg-brand-500 hover:bg-brand-600 text-white rounded-xl font-medium transition shadow-lg shadow-brand-500/20">
            New Sale
          </button>
          <a href={`/print/${successOrder}`} target="_blank" rel="noopener noreferrer" className="flex-1 py-3 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark text-text-light dark:text-white hover:bg-bg-light dark:hover:bg-bg-dark rounded-xl font-medium transition text-center flex items-center justify-center">
            Print Bill
          </a>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col lg:flex-row gap-6 h-[calc(100vh-8rem)] animate-in fade-in duration-300">
      
      {/* Left: Products Grid */}
      <div className="flex-1 flex flex-col min-w-0 bg-white/40 dark:bg-bg-dark/40 rounded-2xl border border-border-light dark:border-border-dark overflow-hidden">
        
        {/* Search & Categories */}
        <div className="p-4 border-b border-border-light dark:border-border-dark bg-white dark:bg-card-dark/80 backdrop-blur z-10">
          <div className="relative mb-4">
            <Search size={18} className="absolute left-3 top-2.5 text-text-muted" />
            <input 
              type="text" 
              placeholder="Search by product name or code..." 
              value={search} onChange={e => setSearch(e.target.value)}
              className={`${inputCls} pl-10 text-base py-2.5 bg-gray-100 dark:bg-gray-800`}
            />
          </div>
          <div className="flex gap-2 overflow-x-auto custom-scrollbar pb-2">
            <button 
              onClick={() => setActiveCategory('')} 
              className={`px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition ${activeCategory === '' ? 'bg-brand-500 text-white' : 'bg-gray-200 dark:bg-gray-800 text-text-light dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700'}`}
            >
              All Items
            </button>
            {categories.map(c => (
              <button 
                key={c.id} onClick={() => setActiveCategory(c.name)}
                className={`px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition ${activeCategory === c.name ? 'bg-brand-500 text-white' : 'bg-gray-200 dark:bg-gray-800 text-text-light dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700'}`}
              >
                {c.name}
              </button>
            ))}
          </div>
        </div>

        {/* Product Grid */}
        <div className="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-50/50 dark:bg-transparent">
          {loading ? (
            <div className="flex justify-center py-20"><Loader2 className="animate-spin text-brand-500" size={32} /></div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
              {filteredProducts.map(p => {
                const isOutOfStock = p.stock_quantity <= 0;
                return (
                  <div 
                    key={p.id} 
                    onClick={() => { if (!isOutOfStock) addToCart(p); else alert("This item is out of stock!"); }}
                    className={`bg-white dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl overflow-hidden transition shadow-sm flex flex-col ${isOutOfStock ? 'opacity-60 cursor-not-allowed grayscale' : 'cursor-pointer hover:border-brand-500 dark:hover:border-brand-500 hover:shadow-md group'}`}
                  >
                    <div className="aspect-square bg-gray-100 dark:bg-gray-800 flex items-center justify-center relative overflow-hidden">
                      {p.image_url ? (
                        <img src={`${BASE_URL}/${p.image_url}`} className={`w-full h-full object-cover transition duration-300 ${!isOutOfStock ? 'group-hover:scale-110' : ''}`} alt={p.name} />
                      ) : (
                        <ImageIcon size={30} className={`text-gray-400 transition duration-300 ${!isOutOfStock ? 'group-hover:scale-125' : ''}`} />
                      )}
                      {isOutOfStock && (
                        <div className="absolute inset-0 bg-white/40 dark:bg-black/40 flex items-center justify-center backdrop-blur-[1px]">
                          <span className="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded shadow-md">Out of Stock</span>
                        </div>
                      )}
                    </div>
                    <div className="p-3 flex-1 flex flex-col">
                      <h3 className="font-medium text-sm text-text-light dark:text-white leading-tight line-clamp-2 flex-1">{p.name}</h3>
                      {p.attribute && <p className="text-xs text-text-muted mt-0.5">{p.attribute}</p>}
                      <div className="flex items-end justify-between mt-2">
                        <span className={`font-bold ${isOutOfStock ? 'text-gray-500' : 'text-brand-600 dark:text-brand-400'}`}>Rs. {Number(p.selling_price).toLocaleString()}</span>
                        <span className={`text-[10px] ${isOutOfStock ? 'text-red-500 font-bold' : 'text-text-muted'}`}>Stock: {p.stock_quantity}</span>
                      </div>
                    </div>
                  </div>
                );
              })}
              {filteredProducts.length === 0 && (
                <div className="col-span-full py-12 text-center text-text-muted">No products found matching your search.</div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Right: Cart Panel */}
      <div className="w-full lg:w-96 flex flex-col bg-white dark:bg-card-dark rounded-2xl border border-border-light dark:border-border-dark shadow-xl overflow-hidden shrink-0">
        
        <div className="p-4 border-b border-border-light dark:border-border-dark bg-gray-50 dark:bg-gray-800/50 flex items-center gap-2">
          <ShoppingCart className="text-brand-500" size={20} />
          <h2 className="font-bold text-text-light dark:text-white text-lg">Current Order</h2>
          <span className="ml-auto bg-brand-500 text-white text-xs font-bold px-2 py-1 rounded-full">{cart.reduce((s, i) => s + i.qty, 0)}</span>
        </div>

        {/* Customer Details */}
        <div className="p-4 border-b border-border-light dark:border-border-dark space-y-3 bg-white dark:bg-transparent">
          <div className="relative">
            <User size={16} className="absolute left-3 top-2.5 text-text-muted" />
            <input type="text" placeholder="Customer Name (Optional)" value={customerName} onChange={e => setCustomerName(e.target.value)} className={`${inputCls} pl-9`} />
          </div>
          <div className="relative">
            <Phone size={16} className="absolute left-3 top-2.5 text-text-muted" />
            <input type="text" placeholder="Contact Number (Optional)" value={contactNo} onChange={e => setContactNo(e.target.value)} className={`${inputCls} pl-9`} />
          </div>
        </div>

        {/* Cart Items */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
          {cart.length === 0 ? (
            <div className="h-full flex flex-col items-center justify-center text-text-muted opacity-50">
              <ShoppingCart size={48} className="mb-4" />
              <p>Cart is empty</p>
              <p className="text-sm">Add products to start billing</p>
            </div>
          ) : (
            cart.map(item => (
              <div key={item.id} className="flex gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/30">
                <div className="flex-1">
                  <h4 className="text-sm font-medium text-text-light dark:text-white line-clamp-1">{item.name} {item.attribute && `- ${item.attribute}`}</h4>
                  <div className="text-xs text-text-muted mt-1">Rs. {Number(item.selling_price).toLocaleString()}</div>
                </div>
                <div className="flex flex-col items-end justify-between">
                  <button onClick={() => removeFromCart(item.id)} className="text-red-400 hover:text-red-600 transition mb-2">
                    <Trash2 size={14} />
                  </button>
                  <div className="flex items-center gap-2 bg-white dark:bg-gray-700 rounded-lg border border-border-light dark:border-border-dark p-0.5">
                    <button onClick={() => updateQty(item.id, -1)} className="w-6 h-6 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 rounded"><Minus size={14} /></button>
                    <span className="text-sm font-medium w-4 text-center">{item.qty}</span>
                    <button onClick={() => updateQty(item.id, 1)} className="w-6 h-6 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-600 rounded"><Plus size={14} /></button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Totals & Checkout */}
        <div className="p-5 border-t border-border-light dark:border-border-dark bg-gray-50 dark:bg-gray-800/80">
          
          <div className="flex bg-white dark:bg-gray-900 rounded-lg p-1 border border-border-light dark:border-border-dark mb-4">
            <button 
              onClick={() => setPaymentMethod('Cash')}
              className={`flex-1 py-1.5 text-sm font-medium rounded-md transition ${paymentMethod === 'Cash' ? 'bg-brand-500 text-white shadow' : 'text-text-muted hover:bg-gray-100 dark:hover:bg-gray-800'}`}
            >
              Cash
            </button>
            <button 
              onClick={() => setPaymentMethod('Card')}
              className={`flex-1 py-1.5 text-sm font-medium rounded-md transition ${paymentMethod === 'Card' ? 'bg-brand-500 text-white shadow' : 'text-text-muted hover:bg-gray-100 dark:hover:bg-gray-800'}`}
            >
              Card
            </button>
          </div>

          {paymentMethod === 'Card' && (
            <div className="space-y-3 mb-4">
              <div className="flex gap-2">
                <div className="flex-1">
                  <select 
                    className={inputCls} 
                    value={cardType} 
                    onChange={e => setCardType(e.target.value)}
                  >
                    <option value="Visa">Visa</option>
                    <option value="Mastercard">Mastercard</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div className="flex-1">
                  <input 
                    type="text" 
                    maxLength={4}
                    placeholder="Last 4 Digits" 
                    className={inputCls}
                    value={cardLastFour}
                    onChange={e => setCardLastFour(e.target.value.replace(/\D/g, ''))}
                  />
                </div>
              </div>
            </div>
          )}

          {paymentMethod === 'Cash' && (
            <div className="space-y-2 mb-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-text-muted">Cash Tendered</span>
                <input 
                  type="number" 
                  min="0"
                  className="w-24 px-2 py-1 text-right rounded-lg border border-border-light dark:border-border-dark bg-white dark:bg-bg-dark text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                  placeholder="Rs."
                  value={cashTendered}
                  onChange={e => setCashTendered(e.target.value)}
                />
              </div>
            </div>
          )}

          <div className="space-y-2 mb-4 text-sm">
            <div className="flex justify-between text-text-muted">
              <span>Subtotal</span>
              <span>Rs. {subtotal.toLocaleString()}</span>
            </div>
            {/* Discount / Tax can be added here */}
            <div className="flex justify-between font-bold text-lg text-text-light dark:text-white pt-2 border-t border-border-light dark:border-border-dark">
              <span>Total</span>
              <span className="text-brand-500">Rs. {total.toLocaleString()}</span>
            </div>
            {paymentMethod === 'Cash' && cashTendered !== '' && (
              <div className="flex justify-between font-bold text-md text-emerald-500 pt-1">
                <span>Change / Balance</span>
                <span>Rs. {Math.max(0, parseFloat(cashTendered) - total).toLocaleString()}</span>
              </div>
            )}
          </div>
          
          <button 
            onClick={handleCheckout} 
            disabled={cart.length === 0 || processing || (paymentMethod === 'Card' && cardLastFour.length !== 4)}
            className="w-full py-3.5 bg-brand-500 hover:bg-brand-600 disabled:bg-gray-400 disabled:opacity-50 text-white font-bold rounded-xl transition shadow-lg shadow-brand-500/20 flex items-center justify-center gap-2"
          >
            {processing ? <Loader2 size={20} className="animate-spin" /> : null}
            {processing ? 'Processing...' : 'Charge Rs. ' + total.toLocaleString()}
          </button>
        </div>
      </div>

    </div>
  );
}

