
const fs = require("fs");
let lines = fs.readFileSync("frontend/src/pages/MainDashboard.jsx", "utf8").split("\n");

// Wrap grid 1
lines[156] = "{user?.modules?.module_pos && (" + lines[156];
let closing1 = lines.findIndex((l, i) => i > 156 && l === "      </div>"); // finds the closing div for the first grid
lines[closing1] = "      </div>)}";

// Wrap grid 2
lines[closing1 + 2] = "{user?.modules?.module_pos && (" + lines[closing1 + 2];
let closing2 = lines.findIndex((l, i) => i > closing1 + 2 && l === "      </div>");
lines[closing2] = "      </div>)}";

fs.writeFileSync("frontend/src/pages/MainDashboard.jsx", lines.join("\n"), "utf8");

