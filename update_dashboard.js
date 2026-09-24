
const fs = require("fs");
let c = fs.readFileSync("frontend/src/pages/MainDashboard.jsx", "utf8");

c = c.replace(/<StatCard title="Total Sales"[\s\S]+? shadow-xl" \/>/, `{user?.modules?.module_pos && $&}`);
c = c.replace(/<StatCard title="Total Orders"[\s\S]+? shadow-xl" \/>/, `{user?.modules?.module_pos && $&}`);
c = c.replace(/<StatCard title="Inventory Value"[\s\S]+? shadow-xl" \/>/, `{user?.modules?.module_inventory && $&}`);
c = c.replace(/<StatCard title="Net Profit"[\s\S]+? shadow-xl" \/>/, `{user?.modules?.module_expenses && $&}`);

c = c.replace(/<div className="lg:col-span-2 glass-card p-5 shadow-xl">[\s\S]+?<\/div>\n        <\/div>/, `{user?.modules?.module_pos && ($&)}`);
c = c.replace(/<div className="glass-card p-5 shadow-xl">\s*<h2 className="text-base font-semibold mb-4 text-text-light dark:text-white">Sales by Category<\/h2>[\s\S]+?<\/div>\n        <\/div>/, `{user?.modules?.module_pos && ($&)}`);
c = c.replace(/<div className="glass-card p-5 shadow-xl">\s*<div className="flex justify-between items-center mb-4">\s*<h2 className="text-base font-semibold text-text-light dark:text-white">Top Selling Products<\/h2>[\s\S]+?<\/div>\n        <\/div>/, `{user?.modules?.module_inventory && ($&)}`);
c = c.replace(/<div className="glass-card p-5 shadow-xl">\s*<div className="flex justify-between items-center mb-4">\s*<h2 className="text-base font-semibold text-text-light dark:text-white">Low Stock Alerts<\/h2>[\s\S]+?<\/div>\n        <\/div>/, `{user?.modules?.module_inventory && ($&)}`);

fs.writeFileSync("frontend/src/pages/MainDashboard.jsx", c, "utf8");

