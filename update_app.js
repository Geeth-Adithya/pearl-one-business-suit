const fs = require('fs');
let c = fs.readFileSync('frontend/src/App.jsx', 'utf8');

const mrCode = \
const ModuleRoute = ({ mod, children }) => {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (user?.role === 'superadmin' || user?.modules?.[mod]) return children;
  return <Navigate to="/" />;
};
\;

c = c.replace(/const pr = \(el\) => <PrivateRoute/, mrCode + '\n  const pr = (el) => <PrivateRoute');
c = c.replace(/const sa = \(el\) => <SuperAdminRoute.*\n/, '$&\n  const mr = (mod, el) => pr(<ModuleRoute mod={mod}>{el}</ModuleRoute>);\n');

c = c.replace(/<Route path="\/pos" element=\{pr\(<POS \/>\)\} \/>/, '<Route path="/pos" element={mr("module_pos", <POS />)} />');
c = c.replace(/<Route path="\/print\/:id" element=\{pr\(<PrintBill \/>\)\} \/>/, '<Route path="/print/:id" element={mr("module_pos", <PrintBill />)} />');

c = c.replace(/<Route path="\/inventory" element=\{pr\(<Inventory \/>\)\} \/>/, '<Route path="/inventory" element={mr("module_inventory", <Inventory />)} />');
c = c.replace(/<Route path="\/inventory\/add" element=\{pr\(<ProductAdd \/>\)\} \/>/, '<Route path="/inventory/add" element={mr("module_inventory", <ProductAdd />)} />');
c = c.replace(/<Route path="\/inventory\/edit\/:id" element=\{pr\(<ProductEdit \/>\)\} \/>/, '<Route path="/inventory/edit/:id" element={mr("module_inventory", <ProductEdit />)} />');
c = c.replace(/<Route path="\/inventory\/categories" element=\{pr\(<Categories \/>\)\} \/>/, '<Route path="/inventory/categories" element={mr("module_inventory", <Categories />)} />');

c = c.replace(/<Route path="\/suppliers" element=\{pr\(<Suppliers \/>\)\} \/>/, '<Route path="/suppliers" element={mr("module_suppliers", <Suppliers />)} />');

c = c.replace(/<Route path="\/customers" element=\{pr\(<PlaceholderPage title="Customer Management" \/>\)\} \/>/, '<Route path="/customers" element={mr("module_customers", <PlaceholderPage title="Customer Management" />)} />');
c = c.replace(/<Route path="\/expenses" element=\{pr\(<Dashboard \/>\)\} \/>/, '<Route path="/expenses" element={mr("module_expenses", <Dashboard />)} />');
c = c.replace(/<Route path="\/reports" element=\{pr\(<PlaceholderPage title="Reports" \/>\)\} \/>/, '<Route path="/reports" element={mr("module_reports", <PlaceholderPage title="Reports" />)} />');
c = c.replace(/<Route path="\/staff" element=\{pr\(<PlaceholderPage title="Staff Management" \/>\)\} \/>/, '<Route path="/staff" element={mr("module_staff", <PlaceholderPage title="Staff Management" />)} />');
c = c.replace(/<Route path="\/branches" element=\{pr\(<PlaceholderPage title="Branch Management" \/>\)\} \/>/, '<Route path="/branches" element={mr("module_branches", <PlaceholderPage title="Branch Management" />)} />');

fs.writeFileSync('frontend/src/App.jsx', c, 'utf8');
