const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Suppliers.jsx', 'utf8');

// Replace view tabs header to include History
const targetTabs = \
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between gap-4 mb-8">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Suppliers</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Manage your suppliers and link them to products.</p>
        </div>
        <button onClick={() => { setView('add'); setFormData({ name: '', phone: '', email: '', address: '', product_types: '' }); }} className="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg font-medium transition shadow-lg shadow-brand-500/20 flex items-center gap-2">
          <Plus size={20} /> Add Supplier
        </button>
      </div>\;

const newTabs = \
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Suppliers</h1>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Manage your suppliers and link them to products.</p>
        </div>
        <div className="flex gap-2">
          <button onClick={() => setView('history')} className="bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-4 py-2 rounded-lg font-medium transition hover:bg-orange-200 flex items-center gap-2">
            History
          </button>
          <button onClick={() => { setView('add'); setFormData({ name: '', phone: '', email: '', address: '', product_types: '' }); }} className="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg font-medium transition shadow-lg shadow-brand-500/20 flex items-center gap-2">
            <Plus size={20} /> Add
          </button>
        </div>
      </div>\;
file = file.replace(targetTabs, newTabs);

const targetHistoryView = \  if (view === 'assign') {\;

const newHistoryView = \
  if (view === 'history') {
    return (
      <div className="max-w-4xl mx-auto">
        <div className="flex items-center gap-4 mb-6">
          <button onClick={() => setView('list')} className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <ArrowLeft size={24} className="text-gray-600 dark:text-gray-300" />
          </button>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Request History</h2>
        </div>
        <div className="bg-white dark:bg-card-dark rounded-xl shadow-sm border border-gray-200 dark:border-border-dark p-6">
          <HistoryView />
        </div>
      </div>
    );
  }

  if (view === 'assign') {\;
file = file.replace(targetHistoryView, newHistoryView);

const compCode = \
function HistoryView() {
  const [history, setHistory] = React.useState([]);
  React.useEffect(() => {
    axios.get('http://localhost:8000/api/suppliers.php?action=requests_history', { withCredentials: true })
      .then(res => setHistory(res.data.requests || []));
  }, []);
  return (
    <div className="space-y-4">
      {history.length === 0 ? <p className="text-center text-gray-500 py-10">No requests sent yet.</p> : history.map(req => (
        <div key={req.id} className="border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col sm:flex-row justify-between gap-4">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <span className={\	ext-xs font-bold px-2 py-1 rounded \\}>
                {req.status.toUpperCase()}
              </span>
              <span className="text-xs text-gray-500">{new Date(req.created_at).toLocaleString()}</span>
            </div>
            <h3 className="font-bold text-gray-900 dark:text-white">{req.product_name}</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">Supplier: <span className="font-medium">{req.supplier_name}</span> | Qty Requested: <span className="font-medium">{req.quantity}</span></p>
            {req.note && <p className="text-sm text-gray-500 italic mt-1">"{req.note}"</p>}
          </div>
          {req.supplier_reply && (
            <div className="sm:w-1/3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
              <p className="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Supplier Reply:</p>
              <p className="text-sm text-gray-600 dark:text-gray-400">{req.supplier_reply}</p>
            </div>
          )}
        </div>
      ))}
    </div>
  )
}
\;

file = file.replace('export default function Suppliers() {', compCode + '\\nexport default function Suppliers() {');

fs.writeFileSync('frontend/src/pages/admin/Suppliers.jsx', file);
