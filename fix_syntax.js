const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Expenses.jsx', 'utf8');

file = file.replace(/className=\{\\\\\\$\{inputCls\} (.*?)\\\\}/g, "className={${inputCls} }");

fs.writeFileSync('frontend/src/pages/admin/Expenses.jsx', file);
console.log('Fixed syntax error');
