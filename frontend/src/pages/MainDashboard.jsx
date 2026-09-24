import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { ShoppingCart, Package, DollarSign, TrendingUp, Store, Users, Globe } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import axios from 'axios';
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, PointElement, LineElement,
  Title, Tooltip, Filler, ArcElement,
} from 'chart.js';
import { Line, Doughnut } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler, ArcElement);

const API_URL = 'http://localhost:8000/api';

function StatCard({ title, amount, subtitle, icon: Icon, colorClass, gradientClass }) {
  return (
    <div className={`p-5 rounded-2xl relative overflow-hidden group cursor-default ${gradientClass} transition-all duration-300 hover:-translate-y-1`}>
      <div className="flex items-start justify-between mb-3">
        <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${colorClass}`}>
          <Icon size={24} className="text-white" />
        </div>
      </div>
      <h3 className="font-medium text-sm mb-1 text-white/70">{title}</h3>
      <p className="text-2xl font-bold text-white">{amount}</p>
      {subtitle && <p className="text-xs text-white/50 mt-1">{subtitle}</p>}
    </div>
  );
}

// =============================================
// SUPERADMIN VIEW: Shops List
// =============================================
function SuperAdminDashboard({ data }) {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
          <Globe className="text-brand-500" size={28} />
          Platform Overview
        </h1>
        <p className="text-text-muted dark:text-text-mutedDark text-sm mt-1">All shops across the platform</p>
      </div>


      <div className="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard title="Total Shops" amount={data.stats.totalShops} subtitle="Active admins" icon={Store} colorClass="bg-brand-500" gradientClass="bg-gradient-to-br from-[#0c2447] to-[#08152e] border border-brand-500/20 shadow-xl" />
        <StatCard title="Total Users" amount={data.stats.totalUsers} subtitle="All registered users" icon={Users} colorClass="bg-purple-500" gradientClass="bg-gradient-to-br from-[#231245] to-[#140a28] border border-purple-500/20 shadow-xl" />
        <StatCard title="Total Sales" amount={`Rs. ${Number(data.stats.totalSales).toLocaleString()}`} subtitle="Platform-wide" icon={DollarSign} colorClass="bg-emerald-500" gradientClass="bg-gradient-to-br from-[#0a2f26] to-[#061e18] border border-emerald-500/20 shadow-xl" />
        <StatCard title="Total Orders" amount={data.stats.totalOrders} subtitle="All shops" icon={ShoppingCart} colorClass="bg-orange-500" gradientClass="bg-gradient-to-br from-[#3b2011] to-[#201007] border border-orange-500/20 shadow-xl" />
      </div>

      <div className="glass-card p-5 shadow-xl">
        <h2 className="text-base font-semibold mb-4 text-text-light dark:text-white">All Shops</h2>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs uppercase text-text-muted dark:text-text-mutedDark border-b border-border-light dark:border-border-dark">
              <tr>
                <th className="px-4 py-3">Shop / Admin</th>
                <th className="px-4 py-3">Email</th>
                <th className="px-4 py-3">Total Orders</th>
                <th className="px-4 py-3">Total Sales</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Joined</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.shops.map(shop => (
                <tr key={shop.id} className="border-b border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark/50 transition">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white font-bold text-xs">
                        {shop.shop_name?.charAt(0).toUpperCase()}
                      </div>
                      <span className="font-medium text-text-light dark:text-white">{shop.shop_name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark">{shop.email}</td>
                  <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark">{shop.total_orders}</td>
                  <td className="px-4 py-3 font-medium text-text-light dark:text-white">Rs. {Number(shop.total_sales).toLocaleString()}</td>
                  <td className="px-4 py-3">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${shop.is_active == 1 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}`}>
                      {shop.is_active == 1 ? 'Active' : 'Disabled'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-text-muted dark:text-text-mutedDark text-xs">{new Date(shop.created_at).toLocaleDateString()}</td>
                  <td className="px-4 py-3 text-right">
                    <Link to={`/shops/edit/${shop.id}`} className="text-brand-500 hover:text-brand-400 text-xs font-medium underline underline-offset-2">Manage</Link>
                  </td>
                </tr>
              ))}
              {data.shops.length === 0 && (
                <tr><td colSpan="7" className="text-center py-8 text-text-muted">No shops registered yet.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

// =============================================
// ADMIN VIEW: Their Shop's Dashboard
// =============================================
function AdminDashboard({ data, range, setRange }) {
  const { user } = useAuth();
  const hasAnyWidget = user?.modules?.module_pos || user?.modules?.module_inventory || user?.modules?.module_expenses;


  const lineData = {
    labels: data.chart?.labels || [],
    datasets: [{
      fill: true, label: 'Sales (Rs.)', 
      data: data.chart?.data || [],
      borderColor: '#0064E7', backgroundColor: 'rgba(0, 100, 231, 0.15)',
      tension: 0.4, pointBackgroundColor: '#0064E7', pointBorderColor: '#fff',
      pointBorderWidth: 2, pointRadius: 4,
    }],
  };
  const lineOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { color: '#8BA3C0' } },
      x: { grid: { display: false }, ticks: { color: '#8BA3C0' } }
    }
  };

  const doughnutData = {
    labels: data.categoryChart?.labels?.length ? data.categoryChart.labels : ['No Data'],
    datasets: [{ 
      data: data.categoryChart?.data?.length ? data.categoryChart.data : [1], 
      backgroundColor: ['#0064E7', '#A044FF', '#FF9F43', '#28C76F', '#EA5455', '#FFD200'], 
      borderWidth: 0, cutout: '72%' 
    }],
  };
  const doughnutOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-text-light dark:text-white flex items-center gap-3">
          <TrendingUp className="text-brand-500" size={28} />
          Dashboard
        </h1>
        <p className="text-text-muted dark:text-text-mutedDark text-sm mt-1">
          Here's what's happening with <span className="text-brand-500 font-medium">{user?.shopName || user?.username}'s</span> shop today.
        </p>
      </div>


      {!hasAnyWidget && (
        <div className="flex flex-col items-center justify-center py-24 text-center">
          <div className="w-20 h-20 bg-brand-500/10 text-brand-500 rounded-full flex items-center justify-center mb-6 shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"/></svg>
          </div>
          <h2 className="text-2xl font-bold text-text-light dark:text-white mb-3">Welcome to your Shop Dashboard!</h2>
          <p className="text-text-muted dark:text-text-mutedDark max-w-md mx-auto text-sm leading-relaxed">
            Your dashboard overview is currently empty because the related sales or inventory modules are not enabled. 
            Use the sidebar menu to navigate through your active features.
          </p>
        </div>
      )}

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-5">
        {user?.modules?.module_pos ? <StatCard title="Total Sales" amount={`Rs. ${Number(data.stats.totalSales).toLocaleString()}`} subtitle="Completed orders" icon={ShoppingCart} colorClass="bg-brand-500" gradientClass="bg-gradient-to-br from-[#0c2447] to-[#08152e] border border-brand-500/20 shadow-xl" /> : null}
        {user?.modules?.module_pos ? <StatCard title="Total Orders" amount={data.stats.totalOrders} subtitle="All orders" icon={Package} colorClass="bg-emerald-500" gradientClass="bg-gradient-to-br from-[#0a2f26] to-[#061e18] border border-emerald-500/20 shadow-xl" /> : null}
        {user?.modules?.module_inventory ? <StatCard title="Inventory Value" amount={`Rs. ${Number(data.stats.totalInventoryValue).toLocaleString()}`} subtitle="Stock × price" icon={DollarSign} colorClass="bg-purple-500" gradientClass="bg-gradient-to-br from-[#231245] to-[#140a28] border border-purple-500/20 shadow-xl" /> : null}
        {user?.modules?.module_expenses ? <StatCard title="Net Profit" amount={`Rs. ${Number(data.stats.netProfit).toLocaleString()}`} subtitle="Sales - COGS" icon={TrendingUp} colorClass="bg-orange-500" gradientClass="bg-gradient-to-br from-[#3b2011] to-[#201007] border border-orange-500/20 shadow-xl" /> : null}
      </div>

{user?.modules?.module_pos && (      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div className="lg:col-span-2 glass-card p-5 shadow-xl">
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-base font-semibold text-text-light dark:text-white">Sales Overview</h2>
            <div className="flex gap-2 text-xs">
              {['7days', '30days', '3months'].map(r => (
                <button 
                  key={r}
                  onClick={() => setRange(r)}
                  className={`px-3 py-1 rounded-full border transition ${range === r ? 'bg-brand-500 text-white border-brand-500' : 'border-border-light dark:border-border-dark text-text-muted dark:text-text-mutedDark hover:bg-bg-light dark:hover:bg-bg-dark'}`}
                >
                  {r === '7days' ? '7 Days' : r === '30days' ? '30 Days' : '3 Months'}
                </button>
              ))}
            </div>
          </div>
          <div className="h-56"><Line data={lineData} options={lineOptions} /></div>
        </div>
        <div className="glass-card p-5 shadow-xl">
          <h2 className="text-base font-semibold mb-4 text-text-light dark:text-white">Sales by Category</h2>
          <div className="relative h-44 flex justify-center">
            <Doughnut data={doughnutData} options={doughnutOptions} />
            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <span className="text-sm font-bold text-text-light dark:text-white">
                Rs. {Number(data.categoryChart?.data?.reduce((a, b) => a + b, 0) || 0).toLocaleString()}
              </span>
              <span className="text-xs text-text-muted dark:text-text-mutedDark">Total</span>
            </div>
          </div>
        </div>
      </div>)}

{user?.modules?.module_pos && (      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div className="glass-card p-5 shadow-xl">
          <h2 className="text-base font-semibold mb-4 text-text-light dark:text-white">Recent Sales</h2>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-text-muted dark:text-text-mutedDark border-b border-border-light dark:border-border-dark">
                <tr><th className="px-3 py-2">Order #</th><th className="px-3 py-2">Customer</th><th className="px-3 py-2">Amount</th><th className="px-3 py-2">Status</th></tr>
              </thead>
              <tbody>
                {data.recentSales.map(sale => (
                  <tr key={sale.id} className="border-b border-border-light dark:border-border-dark hover:bg-bg-light dark:hover:bg-bg-dark/50 transition">
                    <td className="px-3 py-3 text-brand-500 font-medium">#{sale.id}</td>
                    <td className="px-3 py-3 text-text-light dark:text-white">{sale.customer_name || 'Guest'}</td>
                    <td className="px-3 py-3 font-medium text-text-light dark:text-white">Rs. {Number(sale.total_amount).toLocaleString()}</td>
                    <td className="px-3 py-3"><span className="bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full text-xs">{sale.status}</span></td>
                  </tr>
                ))}
                {data.recentSales.length === 0 && <tr><td colSpan="4" className="text-center py-6 text-text-muted">No sales yet</td></tr>}
              </tbody>
            </table>
          </div>
        </div>

        <div className="glass-card p-5 shadow-xl">
          <h2 className="text-base font-semibold mb-4 text-text-light dark:text-white">Top 5 Best Selling Products</h2>
          <div className="space-y-3">
            {data.topProducts.map((prod, i) => (
              <div key={i} className="flex items-center gap-3 border-b border-border-light dark:border-border-dark pb-3 last:border-0">
                <span className="text-text-muted font-bold text-sm w-5">#{i + 1}</span>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-text-light dark:text-white text-sm truncate">{prod.name}</p>
                  <p className="text-xs text-text-muted dark:text-text-mutedDark">{prod.sold} sold</p>
                </div>
                <span className="text-brand-500 text-sm font-medium">Rs. {Number(prod.revenue).toLocaleString()}</span>
              </div>
            ))}
            {data.topProducts.length === 0 && <div className="text-center py-6 text-text-muted text-sm">No product data yet</div>}
          </div>
        </div>
      </div>)}
    </div>
  );
}

// =============================================
// MAIN COMPONENT (auto-detects role)
// =============================================
export default function MainDashboard() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [range, setRange] = useState('7days');
  const { user } = useAuth();

  useEffect(() => {
    setLoading(true);
    axios.get(`${API_URL}/dashboard.php?range=${range}`)
      .then(res => { if (res.data.success) setData(res.data); })
      .catch(err => console.error('Dashboard error:', err))
      .finally(() => setLoading(false));
  }, [range]);

  if (loading && !data) return (
    <div className="flex h-full items-center justify-center">
      <div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div>
    </div>
  );

  if (!data) return <div className="text-center text-text-muted py-20">Failed to load dashboard. Make sure you are logged in.</div>;

  return data.role === 'superadmin'
    ? <SuperAdminDashboard data={data} />
    : <div className={loading ? 'opacity-60 pointer-events-none transition' : 'transition'}>
        <AdminDashboard data={data} range={range} setRange={setRange} />
      </div>;
}
