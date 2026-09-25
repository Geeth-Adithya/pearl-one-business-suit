const fs = require('fs');
let file = fs.readFileSync('frontend/src/App.jsx', 'utf8');

// Add import
if (!file.includes("import Expenses")) {
    file = file.replace("import Customers from './pages/admin/Customers';", "import Customers from './pages/admin/Customers';\nimport Expenses from './pages/admin/Expenses';");
}

// Update route
file = file.replace('<Route path="/expenses" element={mr("module_expenses", <Dashboard />)} />', '<Route path="/expenses" element={mr("module_expenses", <Expenses />)} />');

fs.writeFileSync('frontend/src/App.jsx', file);
console.log('Done modifying App.jsx');
