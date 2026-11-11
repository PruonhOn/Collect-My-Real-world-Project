// Owner: Chheng Phanna - student login page
// ==========================================
import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { GraduationCap, Eye, EyeOff, ArrowLeft } from 'lucide-react';
import Button from '@/components/ui/Button';
import { useAuth } from '@/hooks/useAuth';
import { useTheme } from '@/contexts/ThemeContext'; // global theme

const LoginStudent = () => {
  const navigate = useNavigate();
  const { theme } = useTheme();
  const dark = theme === 'dark';
  const { login, user: contextUser, isAuth } = useAuth();

  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Redirect if already logged in
  useEffect(() => {
    if (isAuth && contextUser) {
      navigate('/student/dashboard', { replace: true });
    }
  }, [isAuth, contextUser, navigate]);

  const handleLogin = async (e) => {
    e?.preventDefault();
    setError('');

    if (!username.trim() || !password) {
      setError('Please enter both username and password.');
      return;
    }

    setLoading(true);
    try {
      const result = await login({ username, password });

      if (result.success) {
        navigate('/student/dashboard', { replace: true });
      } else {
        setError(result.message || 'Login failed. Please try again.');
      }
    } catch (err) {
      setError('An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center p-6 transition-colors duration-300"
      style={{ background: dark ? 'linear-gradient(180deg, rgba(17,24,39,0.4), rgba(31,41,55,0.4))' : undefined }}
    >
      <div
        className={`w-full max-w-md rounded-2xl shadow-2xl p-8 backdrop-blur-sm transition-colors duration-300
        ${dark ? 'bg-gray-900/80 border border-gray-700 text-gray-100' : 'bg-white text-gray-900'}`}
      >
        {/* Back to Home */}
        <div className="mb-4">
          <Link
            to="/landing"
            className={`flex items-center gap-1 text-sm font-medium hover:underline ${dark ? 'text-indigo-400' : 'text-indigo-600'}`}
          >
            <ArrowLeft className="w-4 h-4" />
            Back
          </Link>
        </div>

        {/* Header */}
        <div className="text-center mb-6">
          <div className="inline-flex items-center justify-center w-12 h-12 bg-primary-600 rounded-lg mx-auto mb-4">
            <GraduationCap className="w-7 h-7 text-white" />
          </div>
          <h1 className={`text-3xl font-bold ${dark ? 'text-gray-100' : 'text-gray-900'}`}>Student Login</h1>
          <p className={`text-sm ${dark ? 'text-gray-300' : 'text-gray-600'} mt-1`}>Sign in to access your student portal</p>
        </div>

        {/* Error */}
        {error && (
          <div className={`rounded-lg px-4 py-3 text-sm ${dark ? 'bg-red-900/40 border-red-700 text-red-200' : 'bg-red-50 border-red-200 text-red-700'}`}>
            {error}
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleLogin} className="space-y-4 mt-4">
          <div>
            <label htmlFor="student-username" className={`block text-sm font-medium mb-1 ${dark ? 'text-gray-300' : 'text-gray-700'}`}>Username</label>
            <input
              id="student-username"
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="Enter your username"
              autoComplete="username"
              className={`w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors duration-200
                ${dark ? 'bg-gray-900 border-gray-700 text-gray-100 placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'}`}
            />
          </div>

          <div className="relative">
            <label htmlFor="student-password" className={`block text-sm font-medium mb-1 ${dark ? 'text-gray-300' : 'text-gray-700'}`}>Password</label>
            <input
              id="student-password"
              type={showPassword ? 'text' : 'password'}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter your password"
              autoComplete="current-password"
              className={`w-full px-4 py-3 rounded-lg border focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors duration-200 pr-12
                ${dark ? 'bg-gray-900 border-gray-700 text-gray-100 placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'}`}
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-500 transition-colors"
            >
              {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
            </button>
          </div>

          <Button type="submit" variant="primary" className="w-full" loading={loading}>
            {loading ? 'Logging in...' : 'Login'}
          </Button>

          <Button
            type="button"
            variant="secondary"
            className="w-full mt-2 flex items-center justify-center gap-2"
            onClick={() => navigate('/landing')}
          >
            <ArrowLeft className="w-4 h-4" />
            Back to Home
          </Button>
        </form>

        {/* Admin login link */}
        <div className="mt-4 text-center text-sm">
          Are you an admin?{' '}
          <Link to="/login-admin" className="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
            Login here
          </Link>
        </div>
      </div>
    </div>
  );
};

export default LoginStudent;
