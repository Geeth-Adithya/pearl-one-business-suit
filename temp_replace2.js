const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Expenses.jsx', 'utf8');

// Replace any classname using inputCls pl-9 with the simple addition
file = file.replace(/className=\{.*?inputCls.*?pl-9.*?\}/g, 'className={inputCls + " pl-9"}');

fs.writeFileSync('frontend/src/pages/admin/Expenses.jsx', file);
console.log('Replaced using regex wildcards');
