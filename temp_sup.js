const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/SupplierPortal.jsx', 'utf8');

const targetStr = \
        {/* REGULAR NEEDS SECTION */}
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">Current Supply Needs</h2>\;

const newStr = \
        {/* LOW STOCK SECTION */}
        {data.low_stock && data.low_stock.length > 0 && (
          <div className="mb-10">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                <AlertCircle className="text-red-500" /> Low Stock Warning
              </h2>
            </div>
            <div className="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
              <table className="w-full text-sm text-left">
                <thead className="bg-red-50 text-red-600 uppercase text-xs font-bold border-b border-red-200">
                  <tr>
                    <th className="px-6 py-4">Product Details</th>
                    <th className="px-6 py-4 text-center">Current Stock</th>
                    <th className="px-6 py-4 text-center">Threshold</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-red-100">
                  {data.low_stock.map((item, i) => (
                    <tr key={i} className="hover:bg-red-50/50 transition">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-4">
                          <div className="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200 shrink-0">
                            {item.image_url ? (
                              <img src={\http://localhost:8000/\\} alt={item.name} className="w-full h-full object-cover" />
                            ) : (
                              <Package className="text-gray-400" size={20} />
                            )}
                          </div>
                          <div>
                            <p className="font-bold text-gray-900 text-base">{item.name}</p>
                            {item.item_code && <p className="text-xs text-gray-500 font-mono mt-0.5">Code: {item.item_code}</p>}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-center">
                        <span className="inline-flex items-center justify-center px-3 py-1 rounded-full bg-red-100 text-red-700 font-bold text-lg">
                          {item.stock_quantity}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-center text-gray-500 font-medium">
                        {item.low_stock_threshold}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* REGULAR NEEDS SECTION */}
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">Pending Orders (Needed Now)</h2>\;

file = file.replace(targetStr, newStr);

fs.writeFileSync('frontend/src/pages/SupplierPortal.jsx', file);
console.log('Supplier Portal updated');
