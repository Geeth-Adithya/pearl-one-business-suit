const fs = require('fs');
let file = fs.readFileSync('frontend/src/pages/SupplierPortal.jsx', 'utf8');

// 1. Add popup state
const stateTarget = '  const [submittingReq, setSubmittingReq] = useState(null);';
const stateNew = '  const [submittingReq, setSubmittingReq] = useState(null);\n  const [popup, setPopup] = useState({ show: false, title: "", message: "", type: "success" });';
file = file.replace(stateTarget, stateNew);

// 2. Replace alerts in handleRequestAction
const actionTarget = \      if (res.data.success) {
        alert('Response sent successfully');
        fetchPortalData();
      } else {
        alert(res.data.message || 'Error updating request');
      }
    } catch (e) {
      alert('Error connecting to server');
    }\;

const actionNew = \      if (res.data.success) {
        setPopup({ show: true, title: 'Success', message: 'Response sent successfully', type: 'success' });
        fetchPortalData();
      } else {
        setPopup({ show: true, title: 'Error', message: res.data.message || 'Error updating request', type: 'error' });
      }
    } catch (e) {
      setPopup({ show: true, title: 'Error', message: 'Error connecting to server', type: 'error' });
    }\;
file = file.replace(actionTarget, actionNew);

// 3. Inject popup modal JSX before closing main div
const jsxTarget = '    </div>\n  );\n}';
const jsxNew = \
      {popup.show && (
        <div className="fixed inset-0 bg-black/60 z-[200] flex items-center justify-center p-4 animate-fade-in">
          <div className="bg-white dark:bg-card-dark rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden animate-slide-up">
            <div className="p-8 flex flex-col items-center text-center">
              <div className={\w-16 h-16 rounded-full flex items-center justify-center mb-4 \\}>
                {popup.type === 'success' ? <CheckCircle2 size={32} /> : <AlertCircle size={32} />}
              </div>
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">{popup.title}</h2>
              <p className="text-gray-600 dark:text-gray-400 mb-6">{popup.message}</p>
              <button 
                onClick={() => setPopup({ ...popup, show: false })}
                className="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-brand-500/30"
              >
                OK
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}\;
file = file.replace(jsxTarget, jsxNew);

fs.writeFileSync('frontend/src/pages/SupplierPortal.jsx', file);
console.log('Modified SupplierPortal successfully!');
