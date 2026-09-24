import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { Lock, Mail, AlertCircle, Loader2 } from 'lucide-react';

export default function Login() {
  const [loginId, setLoginId] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);
    
    const res = await login(loginId, password);
    if (res.success) {
      navigate('/');
    } else {
      setError(res.message || 'Invalid credentials');
    }
    setIsLoading(false);
  };

  return (
    <div className="min-h-screen bg-bg-light dark:bg-bg-dark flex flex-col justify-center py-12 sm:px-6 lg:px-8 transition-colors duration-300">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center">
          {/* Logo Placeholder (Fallback to initials if image missing) */}
          <div className="w-16 h-16 bg-brand-500 rounded-2xl shadow-xl flex items-center justify-center">
             <img src="http://localhost:8000/assets/images/logo.png" alt="Logo" className="w-12 h-12 object-contain" onError={(e) => { e.target.style.display='none'; e.target.nextSibling.style.display='block'; }} />
             <span className="text-white font-bold text-3xl hidden">P</span>
          </div>
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-text-light dark:text-text-dark">
          Sign in to your account
        </h2>
        <p className="mt-2 text-center text-sm text-text-muted dark:text-text-mutedDark">
          PearlOne Business Suite
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="glass-card py-8 px-4 sm:px-10 shadow-2xl">
          <form className="space-y-6" onSubmit={handleSubmit}>
            {error && (
              <div className="bg-red-500/10 border border-red-500/20 rounded-lg p-4 flex items-center gap-3 text-red-500 dark:text-red-400 text-sm">
                <AlertCircle size={18} />
                <span>{error}</span>
              </div>
            )}
            
            <div>
              <label className="block text-sm font-medium text-text-light dark:text-text-dark mb-1">
                Email or Username
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Mail size={18} className="text-text-muted dark:text-text-mutedDark" />
                </div>
                <input
                  type="text"
                  required
                  value={loginId}
                  onChange={(e) => setLoginId(e.target.value)}
                  className="block w-full pl-10 pr-3 py-2.5 border border-border-light dark:border-border-dark rounded-lg bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white placeholder-text-muted/50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                  placeholder="admin@example.com"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-text-light dark:text-text-dark mb-1">
                Password
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock size={18} className="text-text-muted dark:text-text-mutedDark" />
                </div>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="block w-full pl-10 pr-3 py-2.5 border border-border-light dark:border-border-dark rounded-lg bg-white/50 dark:bg-bg-dark/50 text-text-light dark:text-white placeholder-text-muted/50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                  placeholder=""
                />
              </div>
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <input
                  id="remember-me"
                  type="checkbox"
                  className="h-4 w-4 text-brand-500 focus:ring-brand-500 border-border-light dark:border-border-dark rounded bg-transparent"
                />
                <label htmlFor="remember-me" className="ml-2 block text-sm text-text-muted dark:text-text-mutedDark">
                  Remember me
                </label>
              </div>

              <div className="text-sm">
                <a href="#" className="font-medium text-brand-500 hover:text-brand-300">
                  Forgot your password?
                </a>
              </div>
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-500 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-70 transition"
            >
              {isLoading ? <Loader2 size={20} className="animate-spin" /> : 'Sign in'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
