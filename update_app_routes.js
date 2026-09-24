
const fs = require("fs");
let c = fs.readFileSync("frontend/src/App.jsx", "utf8");
const replacement = `const ModuleRoute = ({ mod, children }) => {
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
      <Route path="/expenses" element={mr("module_expenses", <Dashboard />)} />
      <Route path="/reports" element={mr("module_reports", <PlaceholderPage title="Reports" />)} />
      <Route path="/staff" element={mr("module_staff", <PlaceholderPage title="Staff Management" />)} />
      <Route path="/branches" element={mr("module_branches", <PlaceholderPage title="Branch Management" />)} />

      {/* Shared */}
      <Route path="/settings" element={pr(<SettingsPage />)} />

      <Route path="*" element={<Navigate to="/" />} />
    </Routes>
  );
}`;
c = c.replace(/function AppContent\(\) \{[\s\S]+?<\/Routes>\s*\);\s*\}/, replacement);
fs.writeFileSync("frontend/src/App.jsx", c, "utf8");

