const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/POS.jsx', 'utf8');

// Replace mapping
const oldCode = {filteredProducts.map(p => (
                  <div 
                    key={p.id} 
                    onClick={() => addToCart(p)};

const newCode = {filteredProducts.map(p => {
                  const isOutOfStock = p.stock_quantity <= 0;
                  return (
                  <div 
                    key={p.id} 
                    onClick={() => {
                        if (isOutOfStock) {
                            alert("This item is out of stock!");
                        } else {
                            addToCart(p);
                        }
                    }}
                    className={\g-white dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl overflow-hidden transition shadow-sm flex flex-col \\}
                  >;

file = file.replace(oldCode, newCode);

// Fix the image zoom to not zoom if out of stock
file = file.replace('className="w-full h-full object-cover group-hover:scale-110 transition duration-300"', 'className={w-full h-full object-cover transition duration-300 }');

file = file.replace('className="text-gray-400 group-hover:scale-125 transition duration-300"', 'className={	ext-gray-400 transition duration-300 }');

// Close the map correctly
file = file.replace(                    <div className="p-3 flex-1 flex flex-col">,                     <div className="p-3 flex-1 flex flex-col">);
file = file.replace(                  </div>
                ))}
              </div>,                   </div>
                  );
                })}
              </div>);

// Remove old className block
file = file.replace(className="bg-white dark:bg-card-dark border border-border-light dark:border-border-dark rounded-xl overflow-hidden cursor-pointer hover:border-brand-500 dark:hover:border-brand-500 transition shadow-sm hover:shadow-md group flex flex-col"\n                  >, `);

fs.writeFileSync('frontend/src/pages/admin/POS.jsx', file);
console.log('Done mapping replacement');
