import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { Bell, AlertTriangle, Clock } from 'lucide-react';

import { useAuth } from '../context/AuthContext';

const API_URL = 'http://localhost:8000/api';

export default function NotificationBell() {
  const { user } = useAuth();
  const [alertData, setAlertData] = useState({ message: '', updated_at: '', target_date: '' });
  const [isOpen, setIsOpen] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const dropdownRef = useRef(null);

  const fetchAlert = async () => {
    try {
      const res = await axios.get(`${API_URL}/settings.php?action=get_maintenance&t=${Date.now()}`, { withCredentials: true });
      if (res.data.success) {
        const newData = {
          message: res.data.message || '',
          updated_at: res.data.updated_at || '',
          target_date: res.data.target_date || ''
        };
        setAlertData(newData);
        
        // Check if we need to show popup
        if (newData.message && newData.updated_at && user) {
          const lastSeenKey = `last_seen_alert_${user.id}`;
          const lastSeen = localStorage.getItem(lastSeenKey);
          if (lastSeen !== newData.updated_at) {
            setShowModal(true);
          }
        }
      }
    } catch (err) {
      console.error('Failed to fetch maintenance alert');
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    if (user) {
      localStorage.setItem(`last_seen_alert_${user.id}`, alertData.updated_at);
    }
  };

  useEffect(() => {
    fetchAlert();
    const interval = setInterval(fetchAlert, 60000);
    return () => clearInterval(interval);
  }, []);

  // Close dropdown on outside click
  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <>
      <div className="fixed top-6 right-8 z-[100]" ref={dropdownRef}>
        <button 
          onClick={() => setIsOpen(!isOpen)}
          className="relative p-2 rounded-full bg-white dark:bg-card-dark border border-gray-200 dark:border-border-dark shadow-sm hover:shadow-md transition text-gray-600 dark:text-gray-300 hover:text-brand-500"
        >
          <Bell size={24} />
          {alertData.message && (
            <span className="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full animate-pulse border-2 border-white dark:border-card-dark"></span>
          )}
        </button>

        {isOpen && (
          <div className="absolute top-full right-0 mt-3 w-80 bg-white dark:bg-card-dark border border-gray-200 dark:border-border-dark rounded-xl shadow-xl overflow-hidden animate-fade-in origin-top-right">
            {alertData.message ? (
              <>
                <div className="bg-red-50 dark:bg-red-900/20 px-4 py-3 border-b border-red-100 dark:border-red-900/50 flex items-center gap-2">
                  <AlertTriangle size={18} className="text-red-500" />
                  <h3 className="font-bold text-red-700 dark:text-red-400">System Notification</h3>
                </div>
                <div className="p-4">
                  <p className="text-gray-800 dark:text-gray-200 text-sm leading-relaxed mb-4 whitespace-pre-wrap">
                    {alertData.message}
                  </p>
                  
                  {alertData.target_date && (
                    <div className="mb-3 p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-900/50 rounded-lg text-sm text-orange-800 dark:text-orange-400 font-medium">
                      📅 Scheduled for: <br/>{new Date(alertData.target_date).toLocaleString()}
                    </div>
                  )}

                  {alertData.updated_at && (
                    <div className="flex items-center gap-1.5 text-xs text-red-500 font-medium">
                      <Clock size={12} />
                      <span>Updated: {new Date(alertData.updated_at).toLocaleString()}</span>
                    </div>
                  )}
                </div>
              </>
            ) : (
              <div className="p-6 text-center text-gray-500 dark:text-gray-400">
                <Bell size={32} className="mx-auto mb-2 opacity-50" />
                <p className="text-sm font-medium">No new notifications</p>
              </div>
            )}
          </div>
        )}
      </div>

      {showModal && alertData.message && (
        <div className="fixed inset-0 bg-black/60 z-[200] flex items-center justify-center p-4 animate-fade-in">
          <div className="bg-white dark:bg-card-dark rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-slide-up">
            <div className="bg-red-500 p-6 flex flex-col items-center text-white text-center relative">
              <div className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-3">
                <AlertTriangle size={32} className="animate-pulse" />
              </div>
              <h2 className="text-2xl font-bold">Important Update</h2>
            </div>
            <div className="p-6">
              <p className="text-gray-700 dark:text-gray-300 text-base leading-relaxed mb-6 whitespace-pre-wrap">
                {alertData.message}
              </p>
              
              {alertData.target_date && (
                <div className="mb-6 p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-900/50 rounded-xl flex items-center gap-3">
                  <div className="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-lg text-orange-600 dark:text-orange-400">
                    <Clock size={20} />
                  </div>
                  <div>
                    <p className="text-xs text-orange-600 dark:text-orange-400 font-bold uppercase tracking-wider">Scheduled For</p>
                    <p className="text-sm font-bold text-gray-900 dark:text-white">
                      {new Date(alertData.target_date).toLocaleString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                    </p>
                  </div>
                </div>
              )}

              <button 
                onClick={handleCloseModal}
                className="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-xl transition shadow-lg shadow-red-500/30"
              >
                I Understand
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

