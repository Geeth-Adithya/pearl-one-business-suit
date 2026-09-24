import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import axios from 'axios';
import { Package, AlertCircle, CheckCircle2, Clock } from 'lucide-react';

const API_URL = 'http://localhost:8000/api';

export default function SupplierPortal() {
  const { token } = useParams();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

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
        <div className="glass-card max-w-md w-full p-8 text-center">
          <div className="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertCircle size={32} />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Access Denied</h1>
          <p className="text-gray-500">{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center shadow-lg overflow-hidden">
               <img src="http://localhost:8000/assets/images/logo.png" alt="" className="w-full h-full object-contain" onError={(e) => { e.target.style.display='none'; e.target.nextSibling.style.display='block'; }} />
               <span className="text-white font-bold text-xl hidden">P</span>
            </div>
            <div>
              <h1 className="text-xl font-bold text-gray-900 leading-tight">{data.shopName}</h1>
              <p className="text-xs text-gray-500 font-medium tracking-wide">SUPPLIER PORTAL</p>
            </div>
          </div>
          <div className="text-right hidden sm:block">
            <p className="text-sm font-medium text-gray-900">{data.supplier.name}</p>
            <p className="text-xs text-gray-500">Authorized Supplier</p>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div className="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-8 flex gap-4 items-start">
          <div className="text-blue-500 mt-1"><AlertCircle size={24}/></div>
          <div>
            <h3 className="font-bold text-blue-900 mb-1">Welcome, {data.supplier.name}</h3>
            <p className="text-blue-700 text-sm">
              This is your dedicated supplier portal. Below you will find the items that are currently in high demand or pending fulfillment for our store. 
              Please review the quantities needed and prepare your supply accordingly.
            </p>
          </div>
        </div>

        {/* URGENT REQUESTS SECTION */}
        {data.requests && data.requests.length > 0 && (
          <div className="mb-10">
            <div className="flex items-center gap-2 mb-4">
              <span className="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
              <h2 className="text-xl font-bold text-orange-600">Urgent Store Requests</h2>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {data.requests.map((req, i) => (
                <div key={i} className="bg-white border-2 border-orange-200 rounded-xl p-4 shadow-sm relative overflow-hidden flex flex-col">
                  <div className="absolute top-0 right-0 bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">NEW REQUEST</div>
                  
                  <div className="flex items-start gap-4 mb-3 mt-2">
                    <div className="w-14 h-14 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 border border-orange-100 overflow-hidden">
                      {req.image_url ? (
                        <img src={`http://localhost:8000/${req.image_url}`} alt={req.name} className="w-full h-full object-cover" />
                      ) : (
                        <Package className="text-orange-400" size={24} />
                      )}
                    </div>
                    <div>
                      <h3 className="font-bold text-gray-900 leading-tight">{req.name}</h3>
                      {req.item_code && <p className="text-xs text-gray-500 font-mono mt-1">Code: {req.item_code}</p>}
                      <div className="mt-2 text-sm text-gray-600">
                        <span className="font-semibold text-gray-900">Requested Qty:</span> <span className="bg-orange-100 text-orange-800 font-bold px-2 py-0.5 rounded ml-1">{req.quantity}</span>
                      </div>
                    </div>
                  </div>
                  
                  {req.note && (
                    <div className="mt-auto pt-3 border-t border-orange-100">
                      <p className="text-sm text-gray-700 italic">"{req.note}"</p>
                    </div>
                  )}
                  
                  <div className="mt-3 text-xs text-gray-400 flex items-center justify-between">
                    <span>Requested: {new Date(req.created_at).toLocaleDateString()}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* REGULAR NEEDS SECTION */}
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">Current Supply Needs</h2>
          <div className="text-sm text-gray-500 flex items-center gap-1">
            <Clock size={16} /> Live updates
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-50 text-gray-500 uppercase text-xs font-bold border-b border-gray-200">
              <tr>
                <th className="px-6 py-4">Product Details</th>
                <th className="px-6 py-4 text-right">Qty Needed</th>
                <th className="px-6 py-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data.needs.map((item, i) => (
                <tr key={i} className="hover:bg-slate-50 transition">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-4">
                      <div className="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200 shrink-0">
                        {item.image_url ? (
                          <img src={`http://localhost:8000/${item.image_url}`} alt={item.name} className="w-full h-full object-cover" />
                        ) : (
                          <Package className="text-gray-400" size={20} />
                        )}
                      </div>
                      <div>
                        <p className="font-bold text-gray-900 text-base">{item.name}</p>
                        {item.item_code && <p className="text-xs text-gray-500 font-mono mt-0.5">Code: {item.item_code}</p>}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-brand-50 text-brand-700 font-bold text-lg">
                      {item.total_needed}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-center">
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-orange-50 text-orange-600 text-xs font-bold uppercase tracking-wider">
                      Pending
                    </span>
                  </td>
                </tr>
              ))}
              
              {data.needs.length === 0 && (
                <tr>
                  <td colSpan="3" className="px-6 py-16 text-center">
                    <div className="flex flex-col items-center justify-center text-gray-400">
                      <CheckCircle2 size={48} className="text-emerald-400 mb-3" />
                      <p className="text-lg font-medium text-gray-900">All caught up!</p>
                      <p className="text-sm">There are no pending orders for your assigned products right now.</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </main>
    </div>
  );
}

