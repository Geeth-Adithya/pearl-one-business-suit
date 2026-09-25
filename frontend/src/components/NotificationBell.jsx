import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import { Bell, AlertTriangle, Clock, CheckCircle2 } from 'lucide-react';

import { useAuth } from '../context/AuthContext';

const API_URL = 'http://localhost:8000/api';

export default function NotificationBell() {
  const { user } = useAuth();
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  const fetchNotifications = async () => {
    try {
      const res = await axios.get(`${API_URL}/notifications.php?action=list`, { withCredentials: true });
      if (res.data.success) {
        setNotifications(res.data.notifications);
        setUnreadCount(res.data.unread_count);
      }
    } catch (err) {
      console.error('Failed to fetch notifications');
    }
  };

  const markAsRead = async (id = null) => {
    try {
      await axios.post(`${API_URL}/notifications.php`, { action: 'mark_read', id }, { withCredentials: true });
      fetchNotifications();
    } catch (err) {
      console.error(err);
    }
  };

  const clearAll = async () => {
    try {
      await axios.post(`${API_URL}/notifications.php`, { action: 'clear_all' }, { withCredentials: true });
      fetchNotifications();
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    if (user) {
      fetchNotifications();
      const interval = setInterval(fetchNotifications, 15000); // Check every 15s
      return () => clearInterval(interval);
    }
  }, [user]);

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
    <div className="fixed top-6 right-8 z-[100]" ref={dropdownRef}>
      <button 
        onClick={() => {
          setIsOpen(!isOpen);
          if (!isOpen && unreadCount > 0) markAsRead(); // Mark all as read when opening
        }}
        className="relative p-2 rounded-full bg-white dark:bg-card-dark border border-gray-200 dark:border-border-dark shadow-sm hover:shadow-md transition text-gray-600 dark:text-gray-300 hover:text-brand-500"
      >
        <Bell size={24} />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 min-w-5 h-5 bg-red-500 rounded-full flex items-center justify-center text-white text-[10px] font-bold border-2 border-white dark:border-card-dark px-1">
            {unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute top-full right-0 mt-3 w-80 max-h-96 overflow-y-auto bg-white dark:bg-card-dark border border-gray-200 dark:border-border-dark rounded-xl shadow-xl animate-fade-in origin-top-right">
          <div className="bg-gray-50 dark:bg-gray-800/50 px-4 py-3 border-b border-gray-200 dark:border-border-dark flex items-center justify-between sticky top-0 z-10">
            <h3 className="font-bold text-gray-900 dark:text-white">Notifications</h3>
            {notifications.length > 0 && (
              <button 
                onClick={clearAll} 
                className="text-xs font-bold text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400 transition"
              >
                Clear All
              </button>
            )}
          </div>
          
          <div className="divide-y divide-gray-100 dark:divide-gray-800">
            {notifications.length > 0 ? (
              notifications.map((notif) => (
                <div key={notif.id} className={`p-4 ${notif.is_read ? 'opacity-70' : 'bg-blue-50/50 dark:bg-blue-900/10'}`}>
                  <div className="flex items-start gap-3">
                    <div className="mt-0.5">
                      {notif.type === 'supply_request' ? <CheckCircle2 size={16} className="text-emerald-500" /> : <AlertTriangle size={16} className="text-orange-500" />}
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-gray-900 dark:text-white mb-0.5">{notif.title}</h4>
                      <p className="text-xs text-gray-600 dark:text-gray-400 mb-2">{notif.message}</p>
                      <div className="flex items-center gap-1.5 text-[10px] text-gray-400">
                        <Clock size={10} />
                        <span>{new Date(notif.created_at).toLocaleString()}</span>
                      </div>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div className="p-6 text-center text-gray-500 dark:text-gray-400">
                <Bell size={32} className="mx-auto mb-2 opacity-50" />
                <p className="text-sm font-medium">No new notifications</p>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
