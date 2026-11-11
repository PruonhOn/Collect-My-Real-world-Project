// Owner: Chheng Phanna - useAuth hook
// ==========================================
// FILE: src/hooks/useAuth.jsx
// ==========================================
// NOTE:
// This file defines a single AuthContext + AuthProvider and the useAuth hook.
// It preserves your original flow (AuthProvider + login/logout + role checks)
// but is corrected for common React/context pitfalls.
//
// If you also have `src/contexts/AuthContext.jsx`, pick one file to provide the AuthProvider
// (do NOT keep both AuthProvider implementations at the same time).
//

import React, { createContext, useContext, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

// Create single AuthContext (use undefined so missing provider is detectable)
const AuthContext = createContext(undefined);

/**
 * AuthProvider
 * Wrap your app tree with <AuthProvider> so useAuth() works.
 */
export const AuthProvider = ({ children }) => {
  const navigate = useNavigate();

  // Auth state
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Local storage keys (keep consistent)
  const STORAGE_USER_KEY = 'user';
  const STORAGE_TOKEN_KEY = 'token';

  // Load user from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(STORAGE_USER_KEY);
      if (stored) {
        setUser(JSON.parse(stored));
      }
    } catch (err) {
      console.error('Error reading stored user:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  // ---- Authentication operations ----

  /**
   * login(credentials)
   * Accepts either (username, password) or an object { username, password }.
   * Returns { success: boolean, user?, error? }
   */
  const login = async (a, b) => {
    // Support both login(username, password) and login({ username, password })
    const credentials =
      typeof a === 'object' && a !== null
        ? a
        : { username: a, password: b };

    try {
      const res = await fetch('/api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(credentials),
      });

      const data = await res.json();

      if (res.ok && data.success) {
        // adapt to your API shape
        const token = data.token || data.data?.token || null;
        const u = data.user || data.data?.user || null;

        const userData = u
          ? {
              id: u.id,
              username: u.username,
              full_name: u.full_name ?? u.name ?? '',
              role: u.role ?? 'viewer',
              // include other fields if needed
            }
          : null;

        if (token) localStorage.setItem(STORAGE_TOKEN_KEY, token);
        if (userData) localStorage.setItem(STORAGE_USER_KEY, JSON.stringify(userData));

        setUser(userData);

        return { success: true, user: userData };
      }

      // Non-2xx or success false
      return { success: false, error: data.message || 'Login failed' };
    } catch (error) {
      console.error('login error:', error);
      return { success: false, error: error.message || 'Connection failed' };
    }
  };

  /**
   * logout()
   * Clears storage and redirects to login (safe to call from anywhere inside Router)
   */
  const logout = (redirect = '/login') => {
    try {
      localStorage.removeItem(STORAGE_USER_KEY);
      localStorage.removeItem(STORAGE_TOKEN_KEY);
    } catch (err) {
      console.error('Error clearing storage on logout:', err);
    } finally {
      setUser(null);
      if (redirect) {
        // Use navigate only if Router is available
        try {
          navigate(redirect);
        } catch (e) {
          // If navigate isn't available (e.g., called outside Router), ignore
          console.warn('navigate not available on logout:', e);
        }
      }
    }
  };

  /**
   * register(payload)
   * Simple wrapper for registration endpoint; adapt to backend shape.
   */
  const register = async (payload) => {
    try {
      const res = await fetch('/api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const data = await res.json();

      if (res.ok && data.success) {
        const token = data.token || data.data?.token || null;
        const u = data.user || data.data?.user || null;

        const userData = u
          ? {
              id: u.id,
              username: u.username,
              full_name: u.full_name ?? u.name ?? '',
              role: u.role ?? 'viewer',
            }
          : null;

        if (token) localStorage.setItem(STORAGE_TOKEN_KEY, token);
        if (userData) localStorage.setItem(STORAGE_USER_KEY, JSON.stringify(userData));

        setUser(userData);
        return { success: true, user: userData };
      }

      return { success: false, error: data.message || 'Registration failed' };
    } catch (error) {
      console.error('register error:', error);
      return { success: false, error: error.message || 'Connection failed' };
    }
  };

  /**
   * hasRole(requiredRole)
   * Returns true if user's role level >= required role level.
   * You can tweak the role hierarchy mapping to your needs.
   */
  const hasRole = (requiredRole) => {
    if (!user || !user.role) return false;

    const roles = { viewer: 1, operator: 2, manager: 3, admin: 4 };
    const userLevel = roles[user.role] || 0;
    const requiredLevel = roles[requiredRole] || 0;
    return userLevel >= requiredLevel;
  };

  /**
   * refreshUser()
   * Optionally fetch fresh profile from API using stored token.
   */
  const refreshUser = async () => {
    try {
      const token = localStorage.getItem(STORAGE_TOKEN_KEY);
      if (!token) return { success: false, error: 'No token' };

      const res = await fetch('/api/me.php', {
        headers: { Authorization: `Bearer ${token}` },
      });

      const data = await res.json();
      if (res.ok && data.success) {
        const u = data.user || data.data?.user;
        if (u) {
          const updated = {
            id: u.id,
            username: u.username,
            full_name: u.full_name ?? u.name ?? '',
            role: u.role ?? user.role,
          };
          localStorage.setItem(STORAGE_USER_KEY, JSON.stringify(updated));
          setUser(updated);
          return { success: true, user: updated };
        }
      }

      return { success: false };
    } catch (error) {
      console.error('refreshUser error:', error);
      return { success: false };
    }
  };

  const isAuthenticated = !!user;

  // Value exposed to consumers
  const value = {
    user,
    loading,
    login,
    logout,
    register,
    hasRole,
    refreshUser,
    isAuthenticated,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

/**
 * useAuth hook
 * Consume authentication context.
 */
export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (ctx === undefined || ctx === null) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return ctx;
};

export default useAuth;
