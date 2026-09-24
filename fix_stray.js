
const fs = require("fs");
let lines = fs.readFileSync("frontend/src/pages/MainDashboard.jsx", "utf8").split("\n");
lines = lines.map(line => line.replace(/shadow-xl" \/>\}/g, "shadow-xl\" />"));
fs.writeFileSync("frontend/src/pages/MainDashboard.jsx", lines.join("\n"), "utf8");

