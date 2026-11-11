// src/components/routing/ProtectedRoute.jsx
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth'; // assume this hook exists and provided by AuthProvider

/**
 * props:
 *  - children: React node
 *  - roles: string or array of allowed roles (e.g. "admin" or ["admin","manager"])
 */
const ProtectedRoute = ({ children, roles = [] }) => {
  const location = useLocation();

  // Normalize roles to array
  const allowedRoles = Array.isArray(roles) ? roles : (roles ? [roles] : []);

  // Try to get auth from hook (preferred)
  let auth = null;
  try {
    auth = useAuth();
  } catch (e) {
    // if hook throws (used outside provider), we'll fallback to localStorage below
    auth = null;
  }

  const isAuthenticated = () => {
    if (auth) return !!auth.isAuthenticated;
    const user = localStorage.getItem('user');
    return !!user;
  };

  const getUser = () => {
    if (auth) return auth.user || null;
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  };

  const hasPermission = (userRole) => {
    // If no roles specified, allow any authenticated user
    if (allowedRoles.length === 0) return true;
    return allowedRoles.includes(userRole);
  };

  if (!isAuthenticated()) {
    // choose login route based on allowed role preference
    const loginPath = allowedRoles.includes('admin') ? '/login-admin' : '/login-student';
    return <Navigate to={loginPath} state={{ from: location }} replace />;
  }

  const user = getUser();

  if (!hasPermission(user?.role)) {
    // Redirect to an unauthorized page or landing
    return <Navigate to="/unauthorized" replace />;
  }

  return children;
};

export default ProtectedRoute;
