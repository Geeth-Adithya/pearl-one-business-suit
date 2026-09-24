
const fs = require("fs");
let lines = fs.readFileSync("frontend/src/pages/MainDashboard.jsx", "utf8").split("\n");
lines[150] = "        {user?.modules?.module_pos ? <StatCard title=\"Total Sales\" amount={`Rs. ${Number(data.stats.totalSales).toLocaleString()}`} subtitle=\"Completed orders\" icon={ShoppingCart} colorClass=\"bg-brand-500\" gradientClass=\"bg-gradient-to-br from-[#0c2447] to-[#08152e] border border-brand-500/20 shadow-xl\" /> : null}";
lines[151] = "        {user?.modules?.module_pos ? <StatCard title=\"Total Orders\" amount={data.stats.totalOrders} subtitle=\"All orders\" icon={Package} colorClass=\"bg-emerald-500\" gradientClass=\"bg-gradient-to-br from-[#0a2f26] to-[#061e18] border border-emerald-500/20 shadow-xl\" /> : null}";
lines[152] = "        {user?.modules?.module_inventory ? <StatCard title=\"Inventory Value\" amount={`Rs. ${Number(data.stats.totalInventoryValue).toLocaleString()}`} subtitle=\"Stock × price\" icon={DollarSign} colorClass=\"bg-purple-500\" gradientClass=\"bg-gradient-to-br from-[#231245] to-[#140a28] border border-purple-500/20 shadow-xl\" /> : null}";
lines[153] = "        {user?.modules?.module_expenses ? <StatCard title=\"Net Profit\" amount={`Rs. ${Number(data.stats.netProfit).toLocaleString()}`} subtitle=\"Sales - COGS\" icon={TrendingUp} colorClass=\"bg-orange-500\" gradientClass=\"bg-gradient-to-br from-[#3b2011] to-[#201007] border border-orange-500/20 shadow-xl\" /> : null}";
fs.writeFileSync("frontend/src/pages/MainDashboard.jsx", lines.join("\n"), "utf8");

