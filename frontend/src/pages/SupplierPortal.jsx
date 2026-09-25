import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import axios from 'axios';
import { Package, AlertCircle, CheckCircle2, Clock, Sun, Moon } from 'lucide-react';

const API_URL = 'http://localhost:8000/api';

export default function SupplierPortal() {
  const { token } = useParams();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  
  const [replyText, setReplyText] = useState({});
  const [submittingReq, setSubmittingReq] = useState(null);
  const [popup, setPopup] = useState({ show: false, title: "", message: "", type: "success" });

  const handleRequestAction = async (reqId, status) => {
    setSubmittingReq(reqId);
    try {
      const res = await axios.post(`${API_URL}/portal.php`, {
        action: 'update_request',
        token,
        request_id: reqId,
        status,
        reply: replyText[reqId] || ''
      });
      if (res.data.success) {
        setPopup({ show: true, title: 'Success', message: 'Response sent successfully', type: 'success' });
        fetchPortalData();
      } else {
        setPopup({ show: true, title: 'Error', message: res.data.message || 'Error updating request', type: 'error' });
      }
    } catch (e) {
      setPopup({ show: true, title: 'Error', message: 'Error connecting to server', type: 'error' });
    } finally {
      setSubmittingReq(null);
    }
  };

  // Theme Toggle
  const [theme, setTheme] = useState(localStorage.getItem('theme') || 'light');
  
  useEffect(() => {
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('theme', theme);
  }, [theme]);

  const toggleTheme = () => {
    setTheme(theme === 'light' ? 'dark' : 'light');
  };

  useEffect(() => {
    fetchPortalData();
  }, [token]);

  const fetchPortalData = async () => {
    try {
      const res = await axios.get(`${API_URL}/portal.php?token=${token}`);
      if (res.data.success) {
        setData(res.data);
      } else {
        setError(res.data.message);
      }
    } catch (err) {
      setError('Unable to load the portal. Please check your connection.');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="min-h-screen bg-bg-light dark:bg-bg-dark flex items-center justify-center"><div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div></div>;
  }

  if (error) {
    return (
      <div className="min-h-screen bg-bg-light dark:bg-bg-dark flex items-center justify-center p-4">
        <div className="bg-white dark:bg-card-dark border border-border-light dark:border-border-dark max-w-md w-full p-8 text-center rounded-2xl shadow-xl">
          <div className="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertCircle size={32} />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Access Denied</h1>
          <p className="text-gray-500 dark:text-gray-400">{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-bg-dark text-text-light dark:text-white transition-colors duration-200">
      {/* Header */}
      <header className="bg-white dark:bg-card-dark border-b border-gray-200 dark:border-border-dark sticky top-0 z-10 shadow-sm transition-colors duration-200">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center shadow-lg overflow-hidden shrink-0">
               <img src="http://localhost:8000/assets/images/logo.png" alt="" className="w-full h-full object-contain" onError={(e) => { e.target.style.display='none'; e.target.nextSibling.style.display='block'; }} />
               <span className="text-white font-bold text-xl hidden">P</span>
            </div>
            <div>
              <h1 className="text-lg sm:text-xl font-bold text-gray-900 dark:text-white leading-tight line-clamp-1">{data.shopName}</h1>
              <p className="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium tracking-wide">SUPPLIER PORTAL</p>
            </div>
          </div>
          <div className="flex items-center gap-3 sm:gap-4">
            <div className="text-right hidden sm:block">
              <p className="text-sm font-medium text-gray-900 dark:text-white">{data.supplier.name}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400">Authorized Supplier</p>
            </div>
            <button 
              onClick={toggleTheme} 
              className="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
              title="Toggle Theme"
            >
              {theme === 'light' ? <Moon size={20} /> : <Sun size={20} />}
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        
        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 rounded-xl p-4 sm:p-5 mb-8 flex gap-3 sm:gap-4 items-start">
          <div className="text-blue-500 mt-1 shrink-0"><AlertCircle size={24}/></div>
          <div>
            <h3 className="font-bold text-blue-900 dark:text-blue-400 mb-1 text-sm sm:text-base">Welcome, {data.supplier.name}</h3>
            <p className="text-blue-700 dark:text-blue-300/80 text-xs sm:text-sm">
              This is your dedicated supplier portal. Below you will find the items that are currently in high demand or pending fulfillment for our store. 
              Please review the quantities needed and prepare your supply accordingly.
            </p>
          </div>
        </div>

        {/* URGENT REQUESTS SECTION */}
        {data.requests && data.requests.length > 0 && (
          <div className="mb-8 sm:mb-10">
            <div className="flex items-center gap-2 mb-4">
              <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
              <h2 className="text-lg sm:text-xl font-bold text-orange-600 dark:text-orange-500">Urgent Store Requests</h2>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {data.requests.map((req, i) => (
                <div key={i} className={`bg-white dark:bg-card-dark border-2 rounded-xl p-4 shadow-sm relative overflow-hidden flex flex-col ${req.status === 'accepted' ? 'border-blue-200 dark:border-blue-500/30' : 'border-orange-200 dark:border-orange-500/30'}`}>
                  {req.status === 'accepted' ? (
                    <div className="absolute top-0 right-0 bg-blue-500 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-1 rounded-bl-lg shadow-sm">PENDING TO DELIVER</div>
                  ) : (
                    <div className="absolute top-0 right-0 bg-orange-500 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-1 rounded-bl-lg shadow-sm">NEW REQUEST</div>
                  )}
                  
                  <div className="flex items-start gap-3 sm:gap-4 mb-3 mt-2">
                    <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-lg flex items-center justify-center shrink-0 border overflow-hidden ${req.status === 'accepted' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800/50' : 'bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-800/50'}`}>
                      {req.image_url ? (
                        <img src={`http://localhost:8000/${req.image_url}`} alt={req.name} className="w-full h-full object-cover" />
                      ) : (
                        <Package className={req.status === 'accepted' ? 'text-blue-400' : 'text-orange-400'} size={24} />
                      )}
                    </div>
                    <div>
                      <h3 className="font-bold text-gray-900 dark:text-white leading-tight text-sm sm:text-base">{req.name}</h3>
                      {req.item_code && <p className="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 font-mono mt-1">Code: {req.item_code}</p>}
                      <div className="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                        <span className="font-semibold text-gray-900 dark:text-white">Requested Qty:</span> 
                        <span className={`font-bold px-2 py-0.5 rounded ml-1 ${req.status === 'accepted' ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-400' : 'bg-orange-100 dark:bg-orange-500/20 text-orange-800 dark:text-orange-400'}`}>{req.quantity}</span>
                      </div>
                    </div>
                  </div>
                  
                  {req.note && (
                    <div className={`mt-auto pt-3 border-t ${req.status === 'accepted' ? 'border-blue-100 dark:border-blue-500/20' : 'border-orange-100 dark:border-orange-500/20'}`}>
                      <p className="text-xs sm:text-sm text-gray-700 dark:text-gray-300 italic">"{req.note}"</p>
                    </div>
                  )}
                  
                  <div className="mt-3 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 flex items-center justify-between">
                    <span>Requested: {new Date(req.created_at).toLocaleDateString()}</span>
                  </div>
                  
                  <div className={`mt-4 pt-3 border-t ${req.status === 'accepted' ? 'border-blue-100 dark:border-blue-500/20' : 'border-orange-100 dark:border-orange-500/20'}`}>
                    {req.status === 'pending' ? (
                      <>
                        <input 
                          type="text" 
                          placeholder="Add a reply message..." 
                          className="w-full text-xs sm:text-sm p-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded mb-2 text-gray-900 dark:text-white"
                          value={replyText[req.id] || ''}
                          onChange={e => setReplyText({...replyText, [req.id]: e.target.value})}
                        />
                        <div className="flex gap-2">
                          <button 
                            onClick={() => handleRequestAction(req.id, 'accepted')}
                            disabled={submittingReq === req.id}
                            className="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-1.5 rounded text-xs sm:text-sm disabled:opacity-50 transition"
                          >
                            Accept
                          </button>
                          <button 
                            onClick={() => handleRequestAction(req.id, 'rejected')}
                            disabled={submittingReq === req.id}
                            className="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-1.5 rounded text-xs sm:text-sm disabled:opacity-50 transition"
                          >
                            Reject
                          </button>
                        </div>
                      </>
                    ) : (
                      <button 
                        onClick={() => handleRequestAction(req.id, 'fulfilled')}
                        disabled={submittingReq === req.id}
                        className="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 rounded text-xs sm:text-sm disabled:opacity-50 transition flex items-center justify-center gap-2"
                      >
                        <CheckCircle2 size={16} /> Mark as Delivered
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* LOW STOCK SECTION */}
        {data.low_stock && data.low_stock.length > 0 && (
          <div className="mb-8 sm:mb-10">
            <div className="flex items-center justify-between mb-4 sm:mb-6">
              <h2 className="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <AlertCircle className="text-red-500" size={20} /> Low Stock Warning
              </h2>
            </div>
            
            {/* Mobile View: Cards */}
            <div className="block sm:hidden space-y-3">
              {data.low_stock.map((item, i) => (
                <div key={i} className="bg-white dark:bg-card-dark rounded-xl shadow-sm border border-red-200 dark:border-red-500/30 p-4">
                  <div className="flex items-center gap-3 mb-3">
                    <div className="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden shrink-0">
                      {item.image_url ? (
                        <img src={`http://localhost:8000/${item.image_url}`} alt={item.name} className="w-full h-full object-cover" />
                      ) : (
                        <Package className="text-gray-400" size={20} />
                      )}
                    </div>
                    <div>
                      <p className="font-bold text-gray-900 dark:text-white text-sm">{item.name}</p>
                      {item.item_code && <p className="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">{item.item_code}</p>}
                    </div>
                  </div>
                  <div className="flex items-center justify-between pt-3 border-t border-red-100 dark:border-red-900/30">
                    <div>
                      <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Current Stock</p>
                      <span className="inline-flex px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 font-bold text-sm">
                        {item.stock_quantity}
                      </span>
                    </div>
                    <div className="text-right">
                      <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Threshold</p>
                      <span className="font-medium text-sm text-gray-600 dark:text-gray-300">{item.low_stock_threshold}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Desktop View: Table */}
            <div className="hidden sm:block bg-white dark:bg-card-dark rounded-xl shadow-sm border border-red-200 dark:border-red-500/30 overflow-hidden">
              <table className="w-full text-sm text-left">
                <thead className="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 uppercase text-xs font-bold border-b border-red-200 dark:border-red-500/30">
                  <tr>
                    <th className="px-6 py-4">Product Details</th>
                    <th className="px-6 py-4 text-center">Current Stock</th>
                    <th className="px-6 py-4 text-center">Threshold</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-red-100 dark:divide-red-900/20">
                  {data.low_stock.map((item, i) => (
                    <tr key={i} className="hover:bg-red-50/50 dark:hover:bg-red-900/10 transition">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-4">
                          <div className="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-700 shrink-0">
                            {item.image_url ? (
                              <img src={`http://localhost:8000/${item.image_url}`} alt={item.name} className="w-full h-full object-cover" />
                            ) : (
                              <Package className="text-gray-400" size={20} />
                            )}
                          </div>
                          <div>
                            <p className="font-bold text-gray-900 dark:text-white text-base">{item.name}</p>
                            {item.item_code && <p className="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">Code: {item.item_code}</p>}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-center">
                        <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 font-bold text-lg">
                          {item.stock_quantity}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-center text-gray-500 dark:text-gray-400 font-medium">
                        {item.low_stock_threshold}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* REGULAR NEEDS SECTION */}
        <div className="flex items-center justify-between mb-4 sm:mb-6">
          <h2 className="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Pending Orders (Needed Now)</h2>
          <div className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
            <Clock size={16} /> Live updates
          </div>
        </div>

        {/* Mobile View: Cards */}
        <div className="block sm:hidden space-y-3">
          {data.needs.length === 0 ? (
            <div className="bg-white dark:bg-card-dark rounded-xl shadow-sm border border-gray-200 dark:border-border-dark p-8 text-center">
              <CheckCircle2 size={40} className="text-emerald-400 mx-auto mb-3" />
              <p className="font-medium text-gray-900 dark:text-white">All caught up!</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">No pending orders right now.</p>
            </div>
          ) : (
            data.needs.map((item, i) => (
              <div key={i} className="bg-white dark:bg-card-dark rounded-xl shadow-sm border border-gray-200 dark:border-border-dark p-4">
                <div className="flex items-center gap-3 mb-3">
                  <div className="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden shrink-0">
                    {item.image_url ? (
                      <img src={`http://localhost:8000/${item.image_url}`} alt={item.name} className="w-full h-full object-cover" />
                    ) : (
                      <Package className="text-gray-400" size={20} />
                    )}
                  </div>
                  <div>
                    <p className="font-bold text-gray-900 dark:text-white text-sm">{item.name}</p>
                    {item.item_code && <p className="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">{item.item_code}</p>}
                  </div>
                </div>
                <div className="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-border-dark">
                  <div>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Qty Needed</p>
                    <span className="inline-flex px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 font-bold text-sm">
                      {item.total_needed}
                    </span>
                  </div>
                  <div className="text-right">
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</p>
                    <span className="inline-flex px-2 py-0.5 rounded-md bg-orange-50 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 text-[10px] font-bold uppercase">
                      Pending
                    </span>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Desktop View: Table */}
        <div className="hidden sm:block bg-white dark:bg-card-dark rounded-xl shadow-sm border border-gray-200 dark:border-border-dark overflow-hidden">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-xs font-bold border-b border-gray-200 dark:border-border-dark">
              <tr>
                <th className="px-6 py-4">Product Details</th>
                <th className="px-6 py-4 text-right">Qty Needed</th>
                <th className="px-6 py-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 dark:divide-border-dark">
              {data.needs.map((item, i) => (
                <tr key={i} className="hover:bg-slate-50 dark:hover:bg-gray-800/50 transition">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-4">
                      <div className="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-700 shrink-0">
                        {item.image_url ? (
                          <img src={`http://localhost:8000/${item.image_url}`} alt={item.name} className="w-full h-full object-cover" />
                        ) : (
                          <Package className="text-gray-400" size={20} />
                        )}
                      </div>
                      <div>
                        <p className="font-bold text-gray-900 dark:text-white text-base">{item.name}</p>
                        {item.item_code && <p className="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">Code: {item.item_code}</p>}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 font-bold text-lg">
                      {item.total_needed}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center">
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-orange-50 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider">
                      Pending
                    </span>
                  </td>
                </tr>
              ))}
              
              {data.needs.length === 0 && (
                <tr>
                  <td colSpan="3" className="px-6 py-16 text-center">
                    <div className="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                      <CheckCircle2 size={48} className="text-emerald-400 dark:text-emerald-500 mb-3" />
                      <p className="text-lg font-medium text-gray-900 dark:text-white">All caught up!</p>
                      <p className="text-sm">There are no pending orders for your assigned products right now.</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </main>

      {/* Custom Popup Modal */}
      {popup.show && (
        <div className="fixed inset-0 bg-black/60 z-[200] flex items-center justify-center p-4 animate-fade-in">
          <div className="bg-white dark:bg-card-dark rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden animate-slide-up">
            <div className="p-8 flex flex-col items-center text-center">
              <div className={`w-16 h-16 rounded-full flex items-center justify-center mb-4 ${popup.type === 'success' ? 'bg-emerald-100 text-emerald-500' : 'bg-red-100 text-red-500'}`}>
                {popup.type === 'success' ? <CheckCircle2 size={32} /> : <AlertCircle size={32} />}
              </div>
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">{popup.title}</h2>
              <p className="text-gray-600 dark:text-gray-400 mb-6">{popup.message}</p>
              <button 
                onClick={() => setPopup({ ...popup, show: false })}
                className="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-brand-500/30"
              >
                OK
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
