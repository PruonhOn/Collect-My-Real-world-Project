// Owner: Chheng Phanna - localStorage helper
/**
 * Storage Utility
 * Handles localStorage operations for auth tokens and user data
 */

const TOKEN_KEY = 'sms_auth_token';
const USER_KEY = 'sms_user';

/**
 * Set authentication token
 * @param {string} token - JWT token
 */
export const setToken = (token) => {
  try {
    localStorage.setItem(TOKEN_KEY, token);
  } catch (error) {
    console.error('Error setting token:', error);
  }
};

/**
 * Get authentication token
 * @returns {string|null} JWT token or null
 */
export const getToken = () => {
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch (error) {
    console.error('Error getting token:', error);
    return null;
  }
};

/**
 * Remove authentication token
 */
export const removeToken = () => {
  try {
    localStorage.removeItem(TOKEN_KEY);
  } catch (error) {
    console.error('Error removing token:', error);
  }
};

/**
 * Set user data
 * @param {Object} user - User object
 */
export const setUser = (user) => {
  try {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  } catch (error) {
    console.error('Error setting user:', error);
  }
};

/**
 * Get user data
 * @returns {Object|null} User object or null
 */
export const getUser = () => {
  try {
    const userStr = localStorage.getItem(USER_KEY);
    return userStr ? JSON.parse(userStr) : null;
  } catch (error) {
    console.error('Error getting user:', error);
    return null;
  }
};

/**
 * Remove user data
 */
export const removeUser = () => {
  try {
    localStorage.removeItem(USER_KEY);
  } catch (error) {
    console.error('Error removing user:', error);
  }
};

/**
 * Clear all storage data
 */
export const clearStorage = () => {
  try {
    removeToken();
    removeUser();
  } catch (error) {
    console.error('Error clearing storage:', error);
  }
};

/**
 * Check if user is authenticated
 * @returns {boolean}
 */
export const isAuthenticated = () => {
  return !!getToken();
};