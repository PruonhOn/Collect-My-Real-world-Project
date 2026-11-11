/**
 * Authentication Service
 * Handles all authentication-related API calls
 */

import apiService from './api';
import { setToken, setUser, removeToken, removeUser, getToken } from '../utils/storage';

const authService = {
  /**
   * User login
   * @param {Object} credentials - { username, password, role }
   * @returns {Promise<Object>} Response with success status and data
   */
  login: async (credentials) => {
    try {
      const response = await apiService.post('/auth/login', credentials);
      
      if (response.success && response.data) {
        const { token, user } = response.data;
        
        // Store token and user data
        setToken(token);
        setUser(user);
        
        return { 
          success: true, 
          user,
          message: response.message || 'Login successful'
        };
      }
      
      return { 
        success: false, 
        message: response.message || 'Login failed'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred during login'
      };
    }
  },

  /**
   * User registration (Student)
   * @param {Object} userData - Registration data
   * @returns {Promise<Object>}
   */
  register: async (userData) => {
    try {
      const response = await apiService.post('/auth/register', userData);
      
      if (response.success && response.data) {
        const { token, user } = response.data;
        
        // Store token and user data
        setToken(token);
        setUser(user);
        
        return { 
          success: true, 
          user,
          message: response.message || 'Registration successful'
        };
      }
      
      return { 
        success: false, 
        message: response.message || 'Registration failed'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred during registration'
      };
    }
  },

  /**
   * User logout
   * @returns {Promise<void>}
   */
  logout: async () => {
    try {
      const token = getToken();
      
      if (token) {
        // Call backend logout endpoint (optional)
        await apiService.post('/auth/logout');
      }
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Always clear local storage
      removeToken();
      removeUser();
    }
  },

  /**
   * Get current user profile
   * @returns {Promise<Object>}
   */
  getProfile: async () => {
    try {
      const response = await apiService.get('/auth/profile');
      
      if (response.success && response.data) {
        // Update stored user data
        setUser(response.data);
        
        return { 
          success: true, 
          user: response.data 
        };
      }
      
      return { 
        success: false,
        message: response.message || 'Failed to fetch profile'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred while fetching profile'
      };
    }
  },

  /**
   * Update user profile
   * @param {Object} userData - Updated user data
   * @returns {Promise<Object>}
   */
  updateProfile: async (userData) => {
    try {
      const response = await apiService.put('/auth/profile', userData);
      
      if (response.success && response.data) {
        // Update stored user data
        setUser(response.data);
        
        return { 
          success: true, 
          user: response.data,
          message: response.message || 'Profile updated successfully'
        };
      }
      
      return { 
        success: false, 
        message: response.message || 'Failed to update profile'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred while updating profile'
      };
    }
  },

  /**
   * Change password
   * @param {Object} passwords - { current_password, new_password }
   * @returns {Promise<Object>}
   */
  changePassword: async (passwords) => {
    try {
      const response = await apiService.post('/auth/change-password', passwords);
      
      return { 
        success: response.success, 
        message: response.message || (response.success ? 'Password changed successfully' : 'Failed to change password')
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred while changing password'
      };
    }
  },

  /**
   * Forgot password
   * @param {string} email - User email
   * @returns {Promise<Object>}
   */
  forgotPassword: async (email) => {
    try {
      const response = await apiService.post('/auth/forgot-password', { email });
      
      return { 
        success: response.success, 
        message: response.message || 'Password reset email sent'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred'
      };
    }
  },

  /**
   * Reset password with token
   * @param {Object} data - { token, password }
   * @returns {Promise<Object>}
   */
  resetPassword: async (data) => {
    try {
      const response = await apiService.post('/auth/reset-password', data);
      
      return { 
        success: response.success, 
        message: response.message || 'Password reset successful'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred'
      };
    }
  },

  /**
   * Verify email with token
   * @param {string} token - Verification token
   * @returns {Promise<Object>}
   */
  verifyEmail: async (token) => {
    try {
      const response = await apiService.post('/auth/verify-email', { token });
      
      return { 
        success: response.success, 
        message: response.message || 'Email verified successfully'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred'
      };
    }
  },

  /**
   * Refresh authentication token
   * @returns {Promise<Object>}
   */
  refreshToken: async () => {
    try {
      const response = await apiService.post('/auth/refresh');
      
      if (response.success && response.data && response.data.token) {
        setToken(response.data.token);
        
        return { 
          success: true, 
          token: response.data.token 
        };
      }
      
      return { 
        success: false,
        message: 'Failed to refresh token'
      };
    } catch (error) {
      return { 
        success: false, 
        message: error.message || 'An error occurred'
      };
    }
  },

  /**
   * Check if user is authenticated
   * @returns {boolean}
   */
  isAuthenticated: () => {
    const token = getToken();
    return !!token;
  },

  /**
   * Get current user from storage
   * @returns {Object|null}
   */
  getCurrentUser: () => {
    try {
      const userStr = localStorage.getItem('sms_user');
      return userStr ? JSON.parse(userStr) : null;
    } catch (error) {
      console.error('Error getting current user:', error);
      return null;
    }
  },

  /**
   * Check user role
   * @param {string|Array<string>} roles - Role(s) to check
   * @returns {boolean}
   */
  hasRole: (roles) => {
    try {
      const user = authService.getCurrentUser();
      if (!user || !user.role) return false;

      if (Array.isArray(roles)) {
        return roles.includes(user.role);
      }

      return user.role === roles;
    } catch (error) {
      console.error('Error checking role:', error);
      return false;
    }
  },
};

export default authService;// Owner: Chea Chan Bormey - login/logout service
