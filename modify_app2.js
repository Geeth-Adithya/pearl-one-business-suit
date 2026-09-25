const fs = require('fs');
let file = fs.readFileSync('frontend/src/App.jsx', 'utf8');

file = file.replace("import Suppliers from './pages/admin/Suppliers';", "import Suppliers from './pages/admin/Suppliers';\nimport Expenses from './pages/admin/Expenses';");

fs.writeFileSync('frontend/src/App.jsx', file);
console.log('Import added');
