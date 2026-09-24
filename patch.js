const fs = require('fs');
let content = fs.readFileSync('frontend/src/App.jsx', 'utf8');
content = content.replace('{children}', '<GlobalAlert />\n          {children}');
fs.writeFileSync('frontend/src/App.jsx', content, 'utf8');
