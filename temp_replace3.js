const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Expenses.jsx', 'utf8');

// The 4th inputCls is for the textarea, which needs resize-none
let count = 0;
file = file.replace(/className=\{inputCls \+ " pl-9"\}/g, (match) => {
    count++;
    if (count === 4) {
        return 'className={inputCls + " pl-9 resize-none"}';
    }
    return match;
});

fs.writeFileSync('frontend/src/pages/admin/Expenses.jsx', file);
console.log('Fixed textarea class');
