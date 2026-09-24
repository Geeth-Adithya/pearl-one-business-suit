const fs = require('fs');
let c = fs.readFileSync('frontend/src/pages/superadmin/ShopEdit.jsx', 'utf8');

const modulesHTML = \
      {/* Module Permissions */}
      <div className="glass-card p-6 shadow-xl space-y-5">
        <h2 className="text-base font-semibold text-text-light dark:text-white border-b border-border-light dark:border-border-dark pb-3">
          Enabled Features (Modules)
        </h2>
        <form onSubmit={handleUpdateDetails} className="space-y-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {[
              { id: 'module_pos', label: 'POS / Sales' },
              { id: 'module_inventory', label: 'Inventory' },
              { id: 'module_suppliers', label: 'Suppliers' },
              { id: 'module_customers', label: 'Customers' },
              { id: 'module_expenses', label: 'Expenses' },
              { id: 'module_reports', label: 'Reports' },
              { id: 'module_staff', label: 'Staff' },
              { id: 'module_branches', label: 'Branches' }
            ].map(mod => (
              <label key={mod.id} className="flex items-center gap-3 p-3 border border-border-light dark:border-border-dark rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition">
                <input 
                  type="checkbox" 
                  checked={!!form[mod.id]}
                  onChange={e => setForm(f => ({ ...f, [mod.id]: e.target.checked ? 1 : 0 }))}
                  className="w-4 h-4 text-brand-500 rounded focus:ring-brand-500"
                />
                <span className="text-sm font-medium text-text-light dark:text-gray-200">{mod.label}</span>
              </label>
            ))}
          </div>
          <div className="pt-2 flex justify-end">
            <button type="submit" disabled={submitting} className="px-5 py-2.5 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 flex items-center gap-2 transition shadow-lg shadow-brand-500/20">
              {submitting ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Save Modules
            </button>
          </div>
        </form>
      </div>
\;

c = c.replace(/<\/form>\s*<\/div>\s*<\/div>\s*<\/div>\s*\);/g, '</form></div></div>\n' + modulesHTML + '\n    </div>\n  );');

fs.writeFileSync('frontend/src/pages/superadmin/ShopEdit.jsx', c, 'utf8');
