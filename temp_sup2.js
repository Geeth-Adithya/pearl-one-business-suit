const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/SupplierPortal.jsx', 'utf8');

const targetState = \  const [loading, setLoading] = useState(true);
  
  // Theme Toggle\;

const newState = \  const [loading, setLoading] = useState(true);
  
  const [replyText, setReplyText] = useState({});
  const [submittingReq, setSubmittingReq] = useState(null);

  const handleRequestAction = async (reqId, status) => {
    setSubmittingReq(reqId);
    try {
      const res = await axios.post(\\\\/portal.php\\\, {
        action: 'update_request',
        token,
        request_id: reqId,
        status,
        reply: replyText[reqId] || ''
      });
      if (res.data.success) {
        alert('Response sent successfully');
        fetchPortalData();
      } else {
        alert(res.data.message || 'Error updating request');
      }
    } catch (e) {
      alert('Error connecting to server');
    } finally {
      setSubmittingReq(null);
    }
  };

  // Theme Toggle\;

file = file.replace(targetState, newState);

const targetUI = \                  <div className="mt-3 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 flex items-center justify-between">
                    <span>Requested: {new Date(req.created_at).toLocaleDateString()}</span>
                  </div>
                </div>\;

const newUI = \                  <div className="mt-3 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 flex items-center justify-between">
                    <span>Requested: {new Date(req.created_at).toLocaleDateString()}</span>
                  </div>
                  
                  <div className="mt-4 pt-3 border-t border-orange-100 dark:border-orange-500/20">
                    <input 
                      type="text" 
                      placeholder="Add a reply message..." 
                      className="w-full text-xs sm:text-sm p-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded mb-2 text-gray-900 dark:text-white"
                      value={replyText[req.id] || ''}
                      onChange={e => setReplyText({...replyText, [req.id]: e.target.value})}
                    />
                    <div className="flex gap-2">
                      <button 
                        onClick={() => handleRequestAction(req.id, 'accepted')}
                        disabled={submittingReq === req.id}
                        className="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-1.5 rounded text-xs sm:text-sm disabled:opacity-50"
                      >
                        Accept
                      </button>
                      <button 
                        onClick={() => handleRequestAction(req.id, 'rejected')}
                        disabled={submittingReq === req.id}
                        className="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-1.5 rounded text-xs sm:text-sm disabled:opacity-50"
                      >
                        Reject
                      </button>
                    </div>
                  </div>
                </div>\;

file = file.replace(targetUI, newUI);

fs.writeFileSync('frontend/src/pages/SupplierPortal.jsx', file);
console.log('SupplierPortal modified successfully');
