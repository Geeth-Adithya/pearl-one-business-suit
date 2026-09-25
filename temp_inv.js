const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Inventory.jsx', 'utf8');

file = file.replace(
    /onClick=\{\(\) => handleStockAdjust\(p\.id, -1\)\}\s*disabled=\{stockUpdating === p\.id\}/g,
    "onClick={() => handleStockAdjust(p.id, -1)}\n                            disabled={stockUpdating === p.id || p.stock_quantity <= 0}"
);

fs.writeFileSync('frontend/src/pages/admin/Inventory.jsx', file);
console.log('Disabled minus button');
