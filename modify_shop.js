const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/superadmin/ShopEdit.jsx', 'utf8');

// Add subscription fields to state
file = file.replace(/shop_name: '', full_name: '', email: '', shop_contact: '',/, 
  "shop_name: '', full_name: '', email: '', shop_contact: '', subscription_plan: 'Monthly', subscription_end_date: '',");

// Add fields to API response mapping (strip time from datetime)
file = file.replace(/shop_contact: res\.data\.shop\.shop_contact \|\| '',/, 
  "shop_contact: res.data.shop.shop_contact || '', subscription_plan: res.data.shop.subscription_plan || 'Monthly', subscription_end_date: res.data.shop.subscription_end_date ? res.data.shop.subscription_end_date.split(' ')[0] : '',");

// Now we need to inject the HTML for the subscription fields.
// The layout currently has a grid: left column is "Basic Information", right column is "Reset Password".
// Let's add a new card in the right column under Reset Password.

// Find the Reset Password card end:
const resetEnd =           </form>
        </div>
      </div>;

const newCard =           </form>
        </div>

        {/* Subscription Card */}
        <div className="glass-card p-6 shadow-xl space-y-5">
          <div className="flex items-center gap-3 border-b border-border-light dark:border-border-dark pb-3">
            <h2 className="text-base font-semibold text-text-light dark:text-white">Subscription & Billing</h2>
          </div>
          <form onSubmit={handleUpdateDetails} className="space-y-4">
            <div>
              <label className={labelCls}>Subscription Plan</label>
              <select
                className={inputCls}
                value={form.subscription_plan}
                onChange={e => setForm({...form, subscription_plan: e.target.value})}
              >
                <option value="7 Days">7 Days Trial</option>
                <option value="Monthly">Monthly</option>
                <option value="Yearly">Yearly</option>
              </select>
            </div>
            <div>
              <label className={labelCls}>End Date</label>
              <input
                type="date"
                className={inputCls}
                value={form.subscription_end_date}
                onChange={e => setForm({...form, subscription_end_date: e.target.value})}
                required
              />
            </div>
            <div className="pt-2 flex justify-end">
              <button type="submit" disabled={submitting} className="px-5 py-2.5 rounded-lg text-sm font-medium bg-brand-500 text-white hover:bg-brand-600 flex items-center gap-2 transition shadow-lg shadow-brand-500/20">
                {submitting ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />} Save Subscription
              </button>
            </div>
          </form>
        </div>
      </div>;

file = file.replace(resetEnd, newCard);
fs.writeFileSync('frontend/src/pages/superadmin/ShopEdit.jsx', file);
console.log('Done modifying ShopEdit.jsx');
