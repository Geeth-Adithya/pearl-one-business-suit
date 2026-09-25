const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/superadmin/ShopManagement.jsx', 'utf8');

file = file.replace(\subscription_plan: '', module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1\, \subscription_plan: '', max_users: 5, module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1\);
file = file.replace(\subscription_plan: '', module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1\, \subscription_plan: '', max_users: 5, module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1\);

fs.writeFileSync('frontend/src/pages/superadmin/ShopManagement.jsx', file);
console.log('done');
