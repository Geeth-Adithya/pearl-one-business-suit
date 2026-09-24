import React, { createContext, useState, useEffect, useContext } from 'react';
import axios from 'axios';

axios.defaults.withCredentials = true;
const API_URL = 'http://localhost:8000/api'; // Changed to local PHP server

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const res = await axios.get(`${API_URL}/me.php?t=${Date.now()}`);
      if (res.data.authenticated) {
        setUser(res.data.user);
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (loginId, password) => {
    try {
      const res = await axios.post(`${API_URL}/login.php`, { login: loginId, password });
      if (res.data.success) {
        setUser(res.data.user);
        return { success: true };
      }
      return { success: false, message: res.data.message || 'Login failed' };
    } catch (error) {
      console.error('Login error:', error);
      return { success: false, message: 'Server error, please check connection.' };
    }
  };

  const logout = async () => {
    try {
      await axios.post(`${API_URL}/logout.php`);
    } catch (e) {}
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
