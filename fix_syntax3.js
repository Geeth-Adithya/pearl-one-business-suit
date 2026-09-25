const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Expenses.jsx', 'utf8');

file = file.split('className={\\\\ pl-9\\}').join('className={inputCls + " pl-9"}');
file = file.split('className={\\\\ pl-9 resize-none\\}').join('className={inputCls + " pl-9 resize-none"}');

fs.writeFileSync('frontend/src/pages/admin/Expenses.jsx', file);
console.log('Fixed syntax using string split join');
