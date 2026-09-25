const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/SupplierPortal.jsx', 'utf8');

const targetStr = \              {data.requests.map((req, i) => (
                <div key={i} className="bg-white dark:bg-card-dark border-2 border-orange-200 dark:border-orange-500/30 rounded-xl p-4 shadow-sm relative overflow-hidden flex flex-col">
                  <div className="absolute top-0 right-0 bg-orange-500 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-1 rounded-bl-lg">NEW REQUEST</div>
                  
                  <div className="flex items-start gap-3 sm:gap-4 mb-3 mt-2">
                    <div className="w-12 h-12 sm:w-14 sm:h-14 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center shrink-0 border border-orange-100 dark:border-orange-800/50 overflow-hidden">
                      {req.image_url ? (
                        <img src={\\\http://localhost:8000/\\\\\\} alt={req.name} className="w-full h-full object-cover" />
                      ) : (
                        <Package className="text-orange-400" size={24} />
                      )}
                    </div>
                    <div>
                      <h3 className="font-bold text-gray-900 dark:text-white leading-tight text-sm sm:text-base">{req.name}</h3>
                      {req.item_code && <p className="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 font-mono mt-1">Code: {req.item_code}</p>}
                      <div className="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                        <span className="font-semibold text-gray-900 dark:text-white">Requested Qty:</span> <span className="bg-orange-100 dark:bg-orange-500/20 text-orange-800 dark:text-orange-400 font-bold px-2 py-0.5 rounded ml-1">{req.quantity}</span>
                      </div>
                    </div>
                  </div>
                  
                  {req.note && (
                    <div className="mt-auto pt-3 border-t border-orange-100 dark:border-orange-500/20">
                      <p className="text-xs sm:text-sm text-gray-700 dark:text-gray-300 italic">"{req.note}"</p>
                    </div>
                  )}
                  
                  <div className="mt-3 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 flex items-center justify-between">
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
                </div>
              ))}\;

const newStr = \              {data.requests.map((req, i) => (
                <div key={i} className={\\\g-white dark:bg-card-dark border-2 rounded-xl p-4 shadow-sm relative overflow-hidden flex flex-col \\\\}>
                  {req.status === 'accepted' ? (
                    <div className="absolute top-0 right-0 bg-brand-500 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-1 rounded-bl-lg">PENDING TO DELIVER</div>
                  ) : (
                    <div className="absolute top-0 right-0 bg-orange-500 text-white text-[10px] sm:text-xs font-bold px-2 sm:px-3 py-1 rounded-bl-lg">NEW REQUEST</div>
                  )}
                  
                  <div className="flex items-start gap-3 sm:gap-4 mb-3 mt-2">
                    <div className={\\\w-12 h-12 sm:w-14 sm:h-14 rounded-lg flex items-center justify-center shrink-0 border overflow-hidden \\\\}>
                      {req.image_url ? (
                        <img src={\\\http://localhost:8000/\\\\\\} alt={req.name} className="w-full h-full object-cover" />
                      ) : (
                        <Package className={req.status === 'accepted' ? "text-brand-400" : "text-orange-400"} size={24} />
                      )}
                    </div>
                    <div>
                      <h3 className="font-bold text-gray-900 dark:text-white leading-tight text-sm sm:text-base">{req.name}</h3>
                      {req.item_code && <p className="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 font-mono mt-1">Code: {req.item_code}</p>}
                      <div className="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                        <span className="font-semibold text-gray-900 dark:text-white">Requested Qty:</span> 
                        <span className={\\\ont-bold px-2 py-0.5 rounded ml-1 \\\\}>{req.quantity}</span>
                      </div>
                    </div>
                  </div>
                  
                  {req.note && (
                    <div className={\\\mt-auto pt-3 border-t \\\\}>
                      <p className="text-xs sm:text-sm text-gray-700 dark:text-gray-300 italic">"{req.note}"</p>
                    </div>
                  )}
                  
                  <div className="mt-3 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 flex items-center justify-between">
                    <span>Requested: {new Date(req.created_at).toLocaleDateString()}</span>
                  </div>
                  
                  <div className={\\\mt-4 pt-3 border-t \\\\}>
                    {req.status === 'pending' ? (
                      <>
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
                            className="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-1.5 rounded text-xs sm:text-sm disabled:opacity-50 transition"
                          >
                            Accept
                          </button>
                          <button 
                            onClick={() => handleRequestAction(req.id, 'rejected')}
                            disabled={submittingReq === req.id}
                            className="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-1.5 rounded text-xs sm:text-sm disabled:opacity-50 transition"
                          >
                            Reject
                          </button>
                        </div>
                      </>
                    ) : (
                      <button 
                        onClick={() => handleRequestAction(req.id, 'fulfilled')}
                        disabled={submittingReq === req.id}
                        className="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-2 rounded text-xs sm:text-sm disabled:opacity-50 transition flex items-center justify-center gap-2"
                      >
                        <CheckCircle2 size={16} /> Mark as Delivered
                      </button>
                    )}
                  </div>
                </div>
              ))}\;

file = file.replace(targetStr, newStr);
fs.writeFileSync('frontend/src/pages/SupplierPortal.jsx', file);
console.log('Done replacement');
