const fs = require('fs');
let file = fs.readFileSync('frontend/src/App.jsx', 'utf8');

// Add import
if (!file.includes("import Staff")) {
    file = file.replace("import Expenses from './pages/admin/Expenses';", "import Expenses from './pages/admin/Expenses';\nimport Staff from './pages/admin/Staff';");
}

// Update route
file = file.replace('<Route path="/staff" element={mr("module_staff", <PlaceholderPage title="Staff Management" />)} />', '<Route path="/staff" element={mr("module_staff", <Staff />)} />');

fs.writeFileSync('frontend/src/App.jsx', file);
console.log('App.jsx updated with Staff route');
