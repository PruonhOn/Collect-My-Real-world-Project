/**
 * API Service - Handles all HTTP requests
 */

import axios from 'axios';

// Get token from localStorage
const getToken = () => {
  return localStorage.getItem('sms_auth_token');
};

// Remove token and user
const removeAuth = () => {
  localStorage.removeItem('sms_auth_token');
  localStorage.removeItem('sms_user');
};

// Create axios instance
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor - Add auth token to requests
api.interceptors.request.use(
  (config) => {
    const token = getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors globally
api.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    // Handle unauthorized access
    if (error.response?.status === 401) {
      removeAuth();
      window.location.href = '/';
    }

    // Extract error message
    const message = error.response?.data?.message || 
                   error.response?.data?.error ||
                   error.message ||
                   'An error occurred';

    return Promise.reject({
      message,
      status: error.response?.status,
      data: error.response?.data,
    });
  }
);

/**
 * API methods
 */
const apiService = {
  // Generic methods
  get: (url, config) => api.get(url, config),
  post: (url, data, config) => api.post(url, data, config),
  put: (url, data, config) => api.put(url, data, config),
  patch: (url, data, config) => api.patch(url, data, config),
  delete: (url, config) => api.delete(url, config),

  // File upload
  uploadFile: (url, file, onProgress) => {
    const formData = new FormData();
    formData.append('file', file);

    return api.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress) {
          const percentCompleted = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          );
          onProgress(percentCompleted);
        }
      },
    });
  },
};

export default apiService;