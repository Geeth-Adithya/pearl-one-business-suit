const fs = require('fs');
let content = fs.readFileSync('frontend/src/App.jsx', 'utf8');
content = content.replace("import SupplierPortal from './pages/SupplierPortal';", "import SupplierPortal from './pages/SupplierPortal';\nimport Settings from './pages/admin/Settings';");
content = content.replace("<Route path="/settings" element={pr(<PlaceholderPage title="Settings" />)} />", "<Route path="/settings" element={pr(<Settings />)} />");
fs.writeFileSync('frontend/src/App.jsx', content, 'utf8');
