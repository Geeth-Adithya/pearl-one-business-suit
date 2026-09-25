const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/superadmin/ShopEdit.jsx', 'utf8');

file = file.replace(/shop_name: '', full_name: '', email: '', shop_contact: '',/, 
  "shop_name: '', full_name: '', email: '', shop_contact: '', subscription_plan: 'Monthly', subscription_end_date: '',");

file = file.replace(/shop_contact: res\.data\.shop\.shop_contact \|\| '',/, 
  "shop_contact: res.data.shop.shop_contact || '', subscription_plan: res.data.shop.subscription_plan || 'Monthly', subscription_end_date: res.data.shop.subscription_end_date ? res.data.shop.subscription_end_date.split(' ')[0] : '',");

const resetEnd = '          </form>\n        </div>\n      </div>';
const newCard = '          </form>\n        </div>\n\n        {/* Subscription Card */}\n        <div className="glass-card p-6 shadow-xl space-y-5">\n          <div className="flex items-center gap-3 border-b border-border-light dark:border-border-dark pb-3">\n            <h2 className="text-base font-semibold text-text-light dark:text-white">Subscription & Billing</h2>\n          </div>\n          <form onSubmit={handleUpdateDetails} className="space-y-4">\n            <div>\n              <label className={labelCls}>Subscription Plan</label>\n              <select\n                className={inputCls}\n                value={form.subscription_plan}\n                onChange={e => setForm({...form, subscription_plan: e.target.value})}\n              >\n                <option value="7 Days">7 Days Trial</option>\n                <option value="Monthly">Monthly</option>\n                <option value="Yearly">Yearly</option>\n              </select>\n            </div>\n            <div>\n              <label className={labelCls}>End Date</label>\n              <input\n                type="date"\n                className={inputCls}\n                value={form.subscription_end_date}\n                onChange={e => setForm({...form, subscription_end_date: e.target.value})}\n                required\n              />\n            </div>\n            <div className="pt-2 flex justify-end">\n              <button type="submit" disabled={submitting} className="px-5 py-2.5 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 flex items-center gap-2 transition shadow-lg shadow-brand-500/20">\n                {submitting ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Save Subscription\n              </button>\n            </div>\n          </form>\n        </div>\n      </div>';

file = file.replace(resetEnd, newCard);
fs.writeFileSync('frontend/src/pages/superadmin/ShopEdit.jsx', file);
console.log('Done modifying ShopEdit.jsx');
