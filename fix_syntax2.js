const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Expenses.jsx', 'utf8');

// The file has literal string: className={\\ pl-9\}
// We want to replace it with: className={inputCls + " pl-9"}
file = file.replace(/className=\{\\\\\\$\\{inputCls\\} pl-9\\\\}/g, 'className={inputCls + " pl-9"}');
file = file.replace(/className=\{\\\\\\$\\{inputCls\\} pl-9 resize-none\\\\}/g, 'className={inputCls + " pl-9 resize-none"}');
file = file.replace(/className=\{\\$\{inputCls\} pl-9\\}/g, 'className={inputCls + " pl-9"}');
file = file.replace(/className=\{\\$\{inputCls\} pl-9 resize-none\\}/g, 'className={inputCls + " pl-9 resize-none"}');

fs.writeFileSync('frontend/src/pages/admin/Expenses.jsx', file);
console.log('Fixed syntax error via text replace');
