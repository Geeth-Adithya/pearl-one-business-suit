
const fs = require("fs");
let c = fs.readFileSync("frontend/src/pages/MainDashboard.jsx", "utf8");

// Extract AdminDashboard component
const startIndex = c.indexOf("function AdminDashboard");
if (startIndex !== -1) {
  let adminDash = c.substring(startIndex);
  
  // Clean up any stray `}` or `{` left over from bad undo
  adminDash = adminDash.replace(/<StatCard(.*?)\} /g, "<StatCard$1 ");
  adminDash = adminDash.replace(/\}\}/g, "}"); // just in case
  
  // Now carefully replace specifically inside adminDash
  adminDash = adminDash.replace(/<StatCard title="Total Sales"/, "{user?.modules?.module_pos ? <StatCard title=\\"Total Sales\\"");
  adminDash = adminDash.replace(/<StatCard title="Total Orders"/, "{user?.modules?.module_pos ? <StatCard title=\\"Total Orders\\"");
  adminDash = adminDash.replace(/<StatCard title="Inventory Value"/, "{user?.modules?.module_inventory ? <StatCard title=\\"Inventory Value\\"");
  adminDash = adminDash.replace(/<StatCard title="Net Profit"/, "{user?.modules?.module_expenses ? <StatCard title=\\"Net Profit\\"");
  
  // Since we added `? <StatCard...`, we need to close it with `: null}`.
  // The StatCard is always self closing: `shadow-xl" />`
  adminDash = adminDash.replace(/(<StatCard title="Total Sales"[\s\S]*?shadow-xl" \/>)/, "$1 : null}");
  adminDash = adminDash.replace(/(<StatCard title="Total Orders"[\s\S]*?shadow-xl" \/>)/, "$1 : null}");
  adminDash = adminDash.replace(/(<StatCard title="Inventory Value"[\s\S]*?shadow-xl" \/>)/, "$1 : null}");
  adminDash = adminDash.replace(/(<StatCard title="Net Profit"[\s\S]*?shadow-xl" \/>)/, "$1 : null}");

  // Sales Overview div
  adminDash = adminDash.replace(/<div className="lg:col-span-2 glass-card p-5 shadow-xl">/, "{user?.modules?.module_pos ? <div className=\\"lg:col-span-2 glass-card p-5 shadow-xl\\">");
  // Sales by Category
  adminDash = adminDash.replace(/<div className="glass-card p-5 shadow-xl">\s*<h2 className="text-base font-semibold mb-4 text-text-light dark:text-white">Sales by Category<\/h2>/, "{user?.modules?.module_pos ? <div className=\\"glass-card p-5 shadow-xl\\">\\n          <h2 className=\\"text-base font-semibold mb-4 text-text-light dark:text-white\\">Sales by Category</h2>");
  
  // Top Selling Products
  adminDash = adminDash.replace(/<div className="glass-card p-5 shadow-xl">\s*<div className="flex justify-between items-center mb-4">\s*<h2 className="text-base font-semibold text-text-light dark:text-white">Top Selling Products<\/h2>/, "{user?.modules?.module_inventory ? <div className=\\"glass-card p-5 shadow-xl\\">\\n          <div className=\\"flex justify-between items-center mb-4\\">\\n            <h2 className=\\"text-base font-semibold text-text-light dark:text-white\\">Top Selling Products</h2>");

  // Low Stock Alerts
  adminDash = adminDash.replace(/<div className="glass-card p-5 shadow-xl">\s*<div className="flex justify-between items-center mb-4">\s*<h2 className="text-base font-semibold text-text-light dark:text-white">Low Stock Alerts<\/h2>/, "{user?.modules?.module_inventory ? <div className=\\"glass-card p-5 shadow-xl\\">\\n          <div className=\\"flex justify-between items-center mb-4\\">\\n            <h2 className=\\"text-base font-semibold text-text-light dark:text-white\\">Low Stock Alerts</h2>");

  // Fix the closing tags for these divs.
  // Sales overview ends before "Sales by Category"
  adminDash = adminDash.replace(/(<Line data=\{lineData\} options=\{lineOptions\} \/><\/div>\s*<\/div>)/, "$1 : null}");
  
  // Sales by Category ends before "Top Selling"
  adminDash = adminDash.replace(/(<span className="text-xs text-text-muted dark:text-text-mutedDark">Total<\/span>\s*<\/div>\s*<\/div>\s*<\/div>)/, "$1 : null}");

  // Top Selling ends before "Low Stock Alerts"
  adminDash = adminDash.replace(/(<a href="\/inventory" className="text-brand-500 hover:text-brand-400 text-xs font-medium underline underline-offset-2">View all<\/a>\s*<\/td>\s*<\/tr>\s*\)\)\s*\}\s*<\/tbody>\s*<\/table>\s*<\/div>\s*<\/div>)/, "$1 : null}");

  // Low Stock ends before closing of grid
  adminDash = adminDash.replace(/(<a href="\/inventory" className="text-red-500 hover:text-red-400 text-xs font-medium underline underline-offset-2">Update<\/a>\s*<\/td>\s*<\/tr>\s*\)\)\s*\}\s*<\/tbody>\s*<\/table>\s*<\/div>\s*<\/div>)/, "$1 : null}");

  c = c.substring(0, startIndex) + adminDash;
  fs.writeFileSync("frontend/src/pages/MainDashboard.jsx", c, "utf8");
}

