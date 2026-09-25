const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/admin/Staff.jsx', 'utf8');

const target =                     <td className="p-4">
                      {s.is_active ? (
                        <span className="flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                          <CheckCircle size={12} /> Active
                        </span>
                      ) : (
                        <span className="flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                          <XCircle size={12} /> Inactive
                        </span>
                      )}
                    </td>;

const replacement =                     <td className="p-4">
                      <div className="flex flex-col gap-2">
                        {s.is_active ? (
                          <span className="flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <CheckCircle size={12} /> Active
                          </span>
                        ) : (
                          <span className="flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                            <XCircle size={12} /> Inactive
                          </span>
                        )}
                        
                        {s.is_online == 1 ? (
                          <span className="flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div> Online
                          </span>
                        ) : (
                          <span className="flex items-center gap-1.5 text-xs font-medium text-text-muted dark:text-text-mutedDark">
                            <div className="w-2 h-2 rounded-full bg-gray-400 dark:bg-gray-600"></div> Offline
                          </span>
                        )}
                      </div>
                    </td>;

file = file.replace(target, replacement);
fs.writeFileSync('frontend/src/pages/admin/Staff.jsx', file);
console.log('Updated Status Column');
