const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Suppliers.jsx', 'utf8');

const targetHeader = \        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Suppliers</h1>\;

const lines = file.split('\n');
let modified = [];
for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('Add Supplier') && lines[i].includes('Plus size=')) {
        modified.push('          <button onClick={() => setView(\\'history\\')} className="flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-2 rounded-lg font-medium hover:bg-orange-200 transition">History</button>');
    }
    modified.push(lines[i]);
}
fs.writeFileSync('frontend/src/pages/admin/Suppliers.jsx', modified.join('\n'));
