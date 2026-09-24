
const fs = require("fs");
let c = fs.readFileSync("frontend/src/pages/MainDashboard.jsx", "utf8");
c = c.replace(/\{user\?\.modules\?\.module_pos && /g, "");
c = c.replace(/\{user\?\.modules\?\.module_inventory && /g, "");
c = c.replace(/\{user\?\.modules\?\.module_expenses && /g, "");
c = c.replace(/\{user\?\.modules\?\.module_[a-z]+ && \((<div[\s\S]*?<\/div>\s*)<\/div>\)\}/g, "$1</div>");
fs.writeFileSync("frontend/src/pages/MainDashboard.jsx", c, "utf8");

