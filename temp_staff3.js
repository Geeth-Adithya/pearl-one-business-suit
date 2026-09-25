const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Staff.jsx', 'utf8');

file = file.replace(\    password: '',\n    is_active: 1\n  });\, \    password: '',\n    role: 'user',\n    is_active: 1\n  });\);
file = file.replace(\      password: '',\n      is_active: 1\n    });\, \      password: '',\n      role: 'user',\n      is_active: 1\n    });\);

fs.writeFileSync('frontend/src/pages/admin/Staff.jsx', file);
console.log('done');
