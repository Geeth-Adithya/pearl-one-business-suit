import React, { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Link, useLocation, Navigate } from 'react-router-dom';
import {
  Home, ShoppingCart, Package, Users, Wallet, BarChart2,
  UserCircle, Store, Settings, LogOut, Sun, Moon,
  Globe, Building2, UsersRound, Tag, Truck, ShoppingBag, Menu, X
} from 'lucide-react';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import MainDashboard from './pages/MainDashboard';
import ShopManagement from './pages/superadmin/ShopManagement';
import ShopEdit from './pages/superadmin/ShopEdit';
import UserManagement from './pages/superadmin/UserManagement';
import Inventory from './pages/admin/Inventory';
import ProductAdd from './pages/admin/ProductAdd';
import ProductEdit from './pages/admin/ProductEdit';
import Categories from './pages/admin/Categories';
import POS from './pages/admin/POS';
import PrintBill from './pages/admin/PrintBill';
import Suppliers from './pages/admin/Suppliers';
import Expenses from './pages/admin/Expenses';
import Staff from './pages/admin/Staff';
import SupplierPortal from './pages/SupplierPortal';
import NotificationBell from './components/NotificationBell';
import SettingsPage from './pages/admin/Settings';
import { AuthProvider, useAuth } from './context/AuthContext';

function Sidebar({ isDarkMode, toggleTheme, isOpen, onClose }) {
  const location = useLocation();

  if (location.pathname.startsWith('/print/') || location.pathname.startsWith('/portal/') || location.pathname.startsWith('/supplier-portal/')) {
    return null;
  }
  
  const { logout, user } = useAuth();
  const isActive = (path) => location.pathname === path;

  const NavLink = ({ to, icon: Icon, label }) => {
    const active = isActive(to);
    return (
      <Link
        to={to}
        onClick={onClose}
        className={`flex items-center gap-3 px-4 py-3 rounded-lg transition ${active ? 'bg-brand-500 text-white shadow-md shadow-brand-500/30' : 'text-text-muted dark:text-text-mutedDark hover:bg-bg-light dark:hover:bg-bg-dark hover:text-brand-700 dark:hover:text-white'}`}
      >
        <Icon size={20} />
        <span className="font-medium">{label}</span>
      </Link>
    );
  };

  const superAdminNav = (
    <>
      <NavLink to="/" icon={Globe} label="Platform Overview" />
      <NavLink to="/shops" icon={Building2} label="Shop Management" />
      <NavLink to="/users" icon={UsersRound} label="User Management" />
      <NavLink to="/settings" icon={Settings} label="Settings" />
    </>
  );

  const adminNav = (
    <>
      <NavLink to="/" icon={Home} label="Dashboard" />
      {user?.modules?.module_pos && <NavLink to="/pos" icon={ShoppingCart} label="POS / Sales" />}
      {user?.modules?.module_inventory && <NavLink to="/inventory" icon={Package} label="Inventory" />}
      {user?.modules?.module_suppliers && <NavLink to="/suppliers" icon={Truck} label="Suppliers" />}
      {user?.modules?.module_customers && <NavLink to="/customers" icon={Users} label="Customers" />}
      {user?.modules?.module_expenses && <NavLink to="/expenses" icon={Wallet} label="Expenses" />}
      {user?.modules?.module_reports && <NavLink to="/reports" icon={BarChart2} label="Reports" />}
      {(user?.role === 'admin' || user?.role === 'manager') && user?.modules?.module_staff && <NavLink to="/staff" icon={UserCircle} label="Staff" />}
      {(user?.role === 'admin' || user?.role === 'manager') && user?.modules?.module_branches && <NavLink to="/branches" icon={Store} label="Branches" />}
      {(user?.role === 'admin' || user?.role === 'manager') && <NavLink to="/settings" icon={Settings} label="Settings" />}
    </>
  );

  return (
    <>
      {/* Dark overlay — mobile only */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar panel */}
      <div
        className={`
          fixed left-0 top-0 h-screen w-64 z-50
          bg-card-light dark:bg-card-dark
          border-r border-border-light dark:border-border-dark
          flex flex-col
          transition-transform duration-300 ease-in-out
          ${isOpen ? 'translate-x-0' : '-translate-x-full'}
          lg:translate-x-0
        `}
      >
        {/* Header */}
        <div className="p-5 flex items-center gap-3 border-b border-border-light dark:border-border-dark">
          <div className="w-10 h-10 flex items-center justify-center font-bold overflow-hidden shrink-0">
            <img
              src="http://localhost:8000/assets/images/logo.png"
              alt=""
              className="w-full h-full object-contain"
              onError={(e) => { e.target.style.display = 'none'; e.target.nextSibling.style.display = 'block'; }}
            />
            <span className="hidden text-brand-500 text-2xl font-black">P</span>
          </div>
          <div className="min-w-0 flex-1">
            <h1 className="font-bold text-base tracking-wide text-brand-900 dark:text-white leading-tight truncate">
              {user?.shopName || user?.username || 'PearlOne Business Suite'}
            </h1>
            <p className="text-xs text-text-muted dark:text-text-mutedDark capitalize">
              {user?.role === 'superadmin' ? '* Super Admin' : 'Admin'}
            </p>
          </div>
          {/* Close button — mobile only */}
          <button
            onClick={onClose}
            className="lg:hidden p-1.5 rounded-lg text-text-muted hover:bg-bg-light dark:hover:bg-bg-dark transition shrink-0"
          >
            <X size={18} />
          </button>
        </div>

        <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
          {user?.role === 'superadmin' ? superAdminNav : adminNav}
        </nav>

        <div className="p-4 border-t border-border-light dark:border-border-dark space-y-2">
          <button
            onClick={toggleTheme}
            className="flex items-center gap-3 px-4 py-3 w-full rounded-lg text-text-muted dark:text-text-mutedDark hover:bg-bg-light dark:hover:bg-bg-dark transition"
          >
            {isDarkMode ? <Sun size={20} /> : <Moon size={20} />}
            <span className="font-medium">{isDarkMode ? 'Light Mode' : 'Dark Mode'}</span>
          </button>
          <button onClick={logout} className="flex items-center gap-3 px-4 py-3 w-full rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
            <LogOut size={20} />
            <span className="font-medium">Log Out</span>
          </button>
        </div>
      </div>
    </>
  );
}

function Layout({ children, isDarkMode, toggleTheme }) {
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { user } = useAuth();

  // Close sidebar on route change
  useEffect(() => {
    setSidebarOpen(false);
  }, [location.pathname]);

  if (location.pathname.startsWith('/print/') || location.pathname.startsWith('/portal/') || location.pathname.startsWith('/supplier-portal/')) {
    return <div className="bg-white min-h-screen text-black">{children}</div>;
  }

  return (
    <div className="flex min-h-screen">
      <Sidebar
        isDarkMode={isDarkMode}
        toggleTheme={toggleTheme}
        isOpen={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
      />

      {/* Main content */}
      <div className="flex-1 lg:ml-64 min-h-screen bg-bg-light dark:bg-bg-dark transition-colors duration-300 flex flex-col">

        {/* Mobile top bar */}
        <div className="lg:hidden sticky top-0 z-30 flex items-center gap-3 px-4 py-3 bg-card-light dark:bg-card-dark border-b border-border-light dark:border-border-dark shadow-sm">
          <button
            onClick={() => setSidebarOpen(true)}
            className="p-2 rounded-lg text-text-muted hover:bg-bg-light dark:hover:bg-bg-dark transition"
          >
            <Menu size={22} />
          </button>
          <div className="flex-1 min-w-0">
            <span className="font-bold text-sm text-text-light dark:text-white truncate block">
              {user?.shopName || user?.username || 'PearlOne Business Suite'}
            </span>
          </div>
          <NotificationBell />
        </div>

        {/* Page content */}
        <div className="flex-1 p-4 md:p-6 lg:p-8 pt-4 md:pt-6 lg:pt-20 overflow-y-auto relative">
          {/* Desktop notification bell */}
          <div className="hidden lg:block">
            <NotificationBell />
          </div>
          {children}
        </div>
      </div>
    </div>
  );
}

function PlaceholderPage({ title }) {
  return (
    <div className="flex flex-col items-center justify-center h-full text-center opacity-70">
      <h2 className="text-3xl font-bold mb-2 text-brand-900 dark:text-white">{title}</h2>
      <p className="text-text-muted dark:text-text-mutedDark">This module is under construction.</p>
    </div>
  );
}

function PrivateRoute({ children, isDarkMode, toggleTheme }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="min-h-screen bg-bg-light dark:bg-bg-dark flex items-center justify-center"><div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div></div>;
  if (!user) return <Navigate to="/login" />;
  return <Layout isDarkMode={isDarkMode} toggleTheme={toggleTheme}>{children}</Layout>;
}

function SuperAdminRoute({ children, isDarkMode, toggleTheme }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="min-h-screen flex items-center justify-center"><div className="animate-spin w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full"></div></div>;
  if (!user) return <Navigate to="/login" />;
  if (user.role !== 'superadmin') return <Navigate to="/" />;
  return <Layout isDarkMode={isDarkMode} toggleTheme={toggleTheme}>{children}</Layout>;
}

const ModuleRoute = ({ mod, children }) => {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (user?.role === "superadmin" || user?.modules?.[mod]) return children;
  return <Navigate to="/" />;
};

function AppContent() {
  const [isDarkMode, setIsDarkMode] = useState(() => {
    const saved = localStorage.getItem("theme");
    if (saved) return saved === "dark";
    return window.matchMedia("(prefers-color-scheme: dark)").matches;
  });

  useEffect(() => {
    document.documentElement.classList.toggle("dark", isDarkMode);
    localStorage.setItem("theme", isDarkMode ? "dark" : "light");
  }, [isDarkMode]);

  const toggleTheme = () => setIsDarkMode(!isDarkMode);
  const pr = (el) => <PrivateRoute isDarkMode={isDarkMode} toggleTheme={toggleTheme}>{el}</PrivateRoute>;
  const sa = (el) => <SuperAdminRoute isDarkMode={isDarkMode} toggleTheme={toggleTheme}>{el}</SuperAdminRoute>;
  const mr = (mod, el) => pr(<ModuleRoute mod={mod}>{el}</ModuleRoute>);

  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/portal/:token" element={<SupplierPortal />} />
      <Route path="/supplier-portal/:token" element={<SupplierPortal />} />

      {/* Shared Dashboard (auto-renders by role) */}
      <Route path="/" element={pr(<MainDashboard />)} />

      {/* === SUPERADMIN ONLY === */}
      <Route path="/shops" element={sa(<ShopManagement />)} />
      <Route path="/shops/edit/:id" element={sa(<ShopEdit />)} />
      <Route path="/users" element={sa(<UserManagement />)} />

      {/* === ADMIN ONLY === */}
      <Route path="/pos" element={mr("module_pos", <POS />)} />
      <Route path="/print/:id" element={mr("module_pos", <PrintBill />)} />
      <Route path="/inventory" element={mr("module_inventory", <Inventory />)} />
      <Route path="/inventory/add" element={mr("module_inventory", <ProductAdd />)} />
      <Route path="/inventory/edit/:id" element={mr("module_inventory", <ProductEdit />)} />
      <Route path="/inventory/categories" element={mr("module_inventory", <Categories />)} />
      <Route path="/suppliers" element={mr("module_suppliers", <Suppliers />)} />
      
      <Route path="/customers" element={mr("module_customers", <PlaceholderPage title="Customer Management" />)} />
      <Route path="/expenses" element={mr("module_expenses", <Expenses />)} />
      <Route path="/reports" element={mr("module_reports", <PlaceholderPage title="Reports" />)} />
      <Route path="/staff" element={mr("module_staff", <Staff />)} />
      <Route path="/branches" element={mr("module_branches", <PlaceholderPage title="Branch Management" />)} />

      {/* Shared */}
      <Route path="/settings" element={pr(<SettingsPage />)} />

      <Route path="*" element={<Navigate to="/" />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <AppContent />
      </AuthProvider>
    </BrowserRouter>
  );
}
