import React from 'react';
import { Wallet, TrendingUp, Calendar, CreditCard, ChevronDown } from 'lucide-react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Filler,
  ArcElement,
} from 'chart.js';
import { Line, Doughnut } from 'react-chartjs-2';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Filler,
  ArcElement
);

function StatCard({ title, amount, percentage, icon: Icon, colorClass, gradientClass, subtitle }) {
  return (
    <div className={`p-6 rounded-2xl border relative overflow-hidden group ${gradientClass} transition-colors duration-300`}>
      <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-3xl -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
      
      <div className={`w-12 h-12 rounded-xl mb-4 flex items-center justify-center ${colorClass}`}>
        <Icon size={24} className="text-white" />
      </div>
      
      <h3 className="font-medium text-sm mb-1 opacity-80">{title}</h3>
      <p className="text-3xl font-bold mb-2">{amount}</p>
      
      <div className="flex items-center gap-2 text-sm mt-3">
        {percentage !== null && (
          <span className="text-emerald-500 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded flex items-center gap-1 font-medium text-xs">
            <TrendingUp size={12} />
            {percentage}%
          </span>
        )}
        <span className="text-xs opacity-70">{subtitle}</span>
      </div>
    </div>
  );
}

export default function Dashboard() {
  // Line Chart Data
  const lineData = {
    labels: ['Sep 1', 'Sep 5', 'Sep 10', 'Sep 15', 'Sep 20', 'Sep 25', 'Sep 30'],
    datasets: [
      {
        fill: true,
        label: 'Expenses',
        data: [12000, 15000, 10000, 14000, 11000, 18000, 13000],
        borderColor: '#0064E7',
        backgroundColor: 'rgba(0, 100, 231, 0.2)', // Light blue fill
        tension: 0.4,
        pointBackgroundColor: '#0064E7',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
      },
    ],
  };

  const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
    },
    scales: {
      y: {
        grid: { color: 'rgba(255, 255, 255, 0.05)' },
        ticks: { color: '#8BA3C0', callback: (val) => val / 1000 + 'K' }
      },
      x: {
        grid: { display: false },
        ticks: { color: '#8BA3C0' }
      }
    }
  };

  // Doughnut Chart Data
  const doughnutData = {
    labels: ['Rent', 'Salary', 'Electricity', 'Transport'],
    datasets: [
      {
        data: [25000, 35000, 8500, 2000],
        backgroundColor: ['#0064E7', '#A044FF', '#FF9F43', '#28C76F'],
        borderWidth: 0,
        cutout: '75%',
      },
    ],
  };

  const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
    }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <header className="flex justify-between items-center mb-2">
        <div className="flex items-center gap-3">
          <div className="p-3 bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl">
             <Wallet className="text-brand-500 dark:text-white" size={24} />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-brand-900 dark:text-white transition-colors duration-300">
              Expenses
            </h1>
            <p className="text-text-muted dark:text-text-mutedDark text-sm transition-colors duration-300">Track and manage your business expenses</p>
          </div>
        </div>
        
        <div className="flex gap-4 items-center">
          <button className="bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark px-4 py-2.5 rounded-lg flex items-center gap-3 text-sm font-medium transition-colors duration-300">
            <Calendar size={16} className="text-text-muted dark:text-text-mutedDark" />
            01 Sep 2026 - 30 Sep 2026
            <ChevronDown size={14} className="ml-2" />
          </button>
          <button className="bg-brand-500 hover:bg-brand-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-brand-500/20 flex items-center gap-2">
            + Add Expense
          </button>
        </div>
      </header>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard 
          title="Total Expenses" 
          amount="Rs. 70,500" 
          percentage="12"
          subtitle="vs. last month"
          icon={Wallet} 
          colorClass="bg-brand-500/20 text-brand-500"
          gradientClass="bg-card-light dark:bg-[#071630] text-text-light dark:text-white border-border-light dark:border-brand-500/30 shadow-sm dark:shadow-xl"
        />
        <StatCard 
          title="This Month" 
          amount="Rs. 70,500" 
          percentage="12"
          subtitle="vs. last month"
          icon={CreditCard} 
          colorClass="bg-emerald-500/20 text-emerald-400"
          gradientClass="bg-card-light dark:bg-[#061828] text-text-light dark:text-white border-border-light dark:border-emerald-500/20 shadow-sm dark:shadow-xl"
        />
        <StatCard 
          title="Today's Expenses" 
          amount="Rs. 5,500" 
          percentage="8"
          subtitle="vs. yesterday"
          icon={Calendar} 
          colorClass="bg-purple-500/20 text-purple-400"
          gradientClass="bg-card-light dark:bg-[#120c24] text-text-light dark:text-white border-border-light dark:border-purple-500/20 shadow-sm dark:shadow-xl"
        />
        <StatCard 
          title="Total Categories" 
          amount="8" 
          percentage={null}
          subtitle="Active categories"
          icon={Wallet} 
          colorClass="bg-brand-300/20 text-brand-300"
          gradientClass="bg-card-light dark:bg-card-dark text-text-light dark:text-white border-border-light dark:border-border-dark shadow-sm dark:shadow-xl"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div className="lg:col-span-2 glass-card p-6 shadow-sm dark:shadow-xl">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-base font-semibold">Expenses Overview</h2>
            <div className="flex gap-2 text-xs font-medium">
              <button className="px-3 py-1 rounded-full border border-border-light dark:border-border-dark">7 Days</button>
              <button className="px-3 py-1 rounded-full bg-brand-500 text-white">30 Days</button>
              <button className="px-3 py-1 rounded-full border border-border-light dark:border-border-dark">3 Months</button>
            </div>
          </div>
          <div className="h-64">
            <Line data={lineData} options={lineOptions} />
          </div>
        </div>

        <div className="glass-card p-6 shadow-sm dark:shadow-xl">
          <h2 className="text-base font-semibold mb-6">Expenses by Category</h2>
          <div className="flex h-48 justify-center relative">
            <Doughnut data={doughnutData} options={doughnutOptions} />
            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-2">
              <span className="text-sm font-bold">Rs. 70,500</span>
              <span className="text-xs text-text-muted dark:text-text-mutedDark">Total</span>
            </div>
          </div>
          
          <div className="mt-6 space-y-3">
            <div className="flex justify-between items-center text-sm">
              <div className="flex items-center gap-2"><span className="w-2.5 h-2.5 rounded-full bg-[#0064E7]"></span> Rent</div>
              <div className="text-text-mutedDark">Rs. 25,000 <span className="text-xs ml-2">35.5%</span></div>
            </div>
            <div className="flex justify-between items-center text-sm">
              <div className="flex items-center gap-2"><span className="w-2.5 h-2.5 rounded-full bg-[#A044FF]"></span> Salary</div>
              <div className="text-text-mutedDark">Rs. 35,000 <span className="text-xs ml-2">49.6%</span></div>
            </div>
            <div className="flex justify-between items-center text-sm">
              <div className="flex items-center gap-2"><span className="w-2.5 h-2.5 rounded-full bg-[#FF9F43]"></span> Electricity</div>
              <div className="text-text-mutedDark">Rs. 8,500 <span className="text-xs ml-2">12.1%</span></div>
            </div>
            <div className="flex justify-between items-center text-sm">
              <div className="flex items-center gap-2"><span className="w-2.5 h-2.5 rounded-full bg-[#28C76F]"></span> Transport</div>
              <div className="text-text-mutedDark">Rs. 2,000 <span className="text-xs ml-2">2.8%</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
