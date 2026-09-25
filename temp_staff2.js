const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Staff.jsx', 'utf8');

const targetStr = \                  <div>
                  <label className={labelCls}>
                    {modalMode === 'add' ? 'Password *' : 'New Password (leave blank to keep current)'}
                  </label>\;

const newStr = \                  <div>
                    <label className={labelCls}>System Role *</label>
                    <select
                      className={inputCls + " focus:ring-brand-500"}
                      value={form.role || 'user'}
                      onChange={e => setForm({...form, role: e.target.value})}
                    >
                      <option value="user">Regular Staff (Limited Access)</option>
                      <option value="manager">Shop Admin (Full Access)</option>
                    </select>
                  </div>
                  
                  <div>
                  <label className={labelCls}>
                    {modalMode === 'add' ? 'Password *' : 'New Password (leave blank to keep current)'}
                  </label>\;

file = file.replace(targetStr, newStr);
fs.writeFileSync('frontend/src/pages/admin/Staff.jsx', file);
console.log("Updated Staff.jsx");
