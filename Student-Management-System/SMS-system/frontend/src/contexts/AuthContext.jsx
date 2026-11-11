import React, { createContext, useContext, useState } from 'react';

const API_URL = import.meta?.env?.VITE_API_URL || 'http://localhost:8000/api';

// Create context with undefined default to detect missing provider
export const AuthContext = createContext(undefined);

/**
 * AuthProvider Component
 * Manages authentication state using React state (in-memory storage)
 * Wrap your app with <AuthProvider> in main.jsx
 */
export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(false);
  const [isAuth, setIsAuth] = useState(false);
  const [token, setToken] = useState(null);

  /**
   * Helper function for API calls
   */
  const apiCall = async (endpoint, options = {}) => {
    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        headers: {
          'Content-Type': 'application/json',
          ...(options.headers || {}),
        },
        ...options,
      });

      const data = await response.json();
      return { ok: response.ok, data };
    } catch (error) {
      return { ok: false, error: error.message };
    }
  };

  /**
   * Login user with credentials
   */
  const login = async (credentials) => {
    try {
      setLoading(true);
      const { ok, data, error } = await apiCall('/auth/login', {
        method: 'POST',
        body: JSON.stringify(credentials),
      });

      if (ok && data?.success && data?.data) {
        const { token: authToken, user: userData } = data.data;

        setToken(authToken);
        setUser(userData);
        setIsAuth(true);

        return { success: true, user: userData };
      }

      return {
        success: false,
        message: data?.message || error || 'Login failed',
      };
    } catch (error) {
      console.error('Login error:', error);
      return {
        success: false,
        message: error.message || 'Login failed',
      };
    } finally {
      setLoading(false);
    }
  };

  /**
   * Register new user
   */
  const register = async (userDataPayload) => {
    try {
      setLoading(true);
      const { ok, data, error } = await apiCall('/auth/register', {
        method: 'POST',
        body: JSON.stringify(userDataPayload),
      });

      if (ok && data?.success && data?.data) {
        const { token: authToken, user: newUser } = data.data;

        setToken(authToken);
        setUser(newUser);
        setIsAuth(true);

        return { success: true, user: newUser };
      }

      return {
        success: false,
        message: data?.message || error || 'Registration failed',
      };
    } catch (error) {
      console.error('Registration error:', error);
      return {
        success: false,
        message: error.message || 'Registration failed',
      };
    } finally {
      setLoading(false);
    }
  };

  /**
   * Logout user
   */
  const logout = async () => {
    try {
      if (token) {
        await apiCall('/auth/logout', {
          method: 'POST',
          headers: { Authorization: `Bearer ${token}` },
        }).catch((err) => console.error('Logout API error:', err));
      }
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setToken(null);
      setUser(null);
      setIsAuth(false);
    }
  };

  /**
   * Update user profile
   */
  const updateUser = async (userDataPayload) => {
    try {
      if (!token) {
        return { success: false, message: 'No authentication token found' };
      }

      setLoading(true);
      const { ok, data, error } = await apiCall('/auth/profile', {
        method: 'PUT',
        headers: { Authorization: `Bearer ${token}` },
        body: JSON.stringify(userDataPayload),
      });

      if (ok && data?.success && data?.data) {
        const updatedUser = data.data;
        setUser(updatedUser);
        return { success: true, user: updatedUser };
      }

      return {
        success: false,
        message: data?.message || error || 'Update failed',
      };
    } catch (error) {
      console.error('Update profile error:', error);
      return {
        success: false,
        message: error.message || 'Update failed',
      };
    } finally {
      setLoading(false);
    }
  };

  /**
   * Refresh user data from server
   */
  const refreshUser = async () => {
    try {
      if (!token) {
        return { success: false, message: 'No token found' };
      }

      const { ok, data } = await apiCall('/auth/profile', {
        method: 'GET',
        headers: { Authorization: `Bearer ${token}` },
      });

      if (ok && data?.success && data?.data) {
        const userData = data.data;
        setUser(userData);
        return { success: true, user: userData };
      }

      return { success: false };
    } catch (error) {
      console.error('Refresh user error:', error);
      return { success: false };
    }
  };

  /**
   * Check if user has specific role(s)
   */
  const hasRole = (roles) => {
    if (!user) return false;
    if (Array.isArray(roles)) return roles.includes(user.role);
    return user.role === roles;
  };

  /**
   * Get current user object
   */
  const getCurrentUser = () => user;

  /**
   * Get current auth token
   */
  const getToken = () => token;

  // Context value
  const value = {
    user,
    loading,
    isAuth,
    token,
    login,
    register,
    logout,
    updateUser,
    refreshUser,
    hasRole,
    getCurrentUser,
    getToken,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

/**
 * Custom hook to use auth context
 * Throws error if used outside AuthProvider
 */
export const useAuth = () => {
  const context = useContext(AuthContext);
  
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  
  return context;
};

// No default export — use named AuthProvider import:
// import { AuthProvider } from './contexts/AuthContext';