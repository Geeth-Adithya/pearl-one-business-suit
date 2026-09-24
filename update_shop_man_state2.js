
const fs = require("fs");
let c = fs.readFileSync("frontend/src/pages/superadmin/ShopManagement.jsx", "utf8");
c = c.replace(/subscription_plan: \x27\x27, module_pos: true, module_stock: true, module_supply: false/g, "subscription_plan: \x27\x27, module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1");
fs.writeFileSync("frontend/src/pages/superadmin/ShopManagement.jsx", c, "utf8");

