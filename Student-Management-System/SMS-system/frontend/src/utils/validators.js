/**
 * Validation utilities for form inputs
 */

export const validators = {
  // Email validation
  email: (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) return 'Email is required';
    if (!emailRegex.test(email)) return 'Invalid email format';
    return null;
  },

  // Password validation
  password: (password) => {
    if (!password) return 'Password is required';
    if (password.length < 8) return 'Password must be at least 8 characters';
    if (!/[A-Z]/.test(password)) return 'Password must contain uppercase letter';
    if (!/[a-z]/.test(password)) return 'Password must contain lowercase letter';
    if (!/[0-9]/.test(password)) return 'Password must contain number';
    return null;
  },

  // Username validation
  username: (username) => {
    if (!username) return 'Username is required';
    if (username.length < 3) return 'Username must be at least 3 characters';
    if (username.length > 20) return 'Username must be less than 20 characters';
    if (!/^[a-zA-Z0-9_]+$/.test(username)) return 'Username can only contain letters, numbers, and underscores';
    return null;
  },

  // Phone validation
  phone: (phone) => {
    if (!phone) return 'Phone number is required';
    const phoneRegex = /^[0-9]{9,15}$/;
    if (!phoneRegex.test(phone.replace(/[\s-]/g, ''))) return 'Invalid phone number';
    return null;
  },

  // Required field
  required: (value, fieldName = 'This field') => {
    if (!value || value.toString().trim() === '') {
      return `${fieldName} is required`;
    }
    return null;
  },

  // Min length
  minLength: (value, min, fieldName = 'This field') => {
    if (value && value.length < min) {
      return `${fieldName} must be at least ${min} characters`;
    }
    return null;
  },

  // Max length
  maxLength: (value, max, fieldName = 'This field') => {
    if (value && value.length > max) {
      return `${fieldName} must be less than ${max} characters`;
    }
    return null;
  },

  // Number validation
  number: (value, fieldName = 'This field') => {
    if (value && isNaN(value)) {
      return `${fieldName} must be a number`;
    }
    return null;
  },

  // Date validation
  date: (date, fieldName = 'Date') => {
    if (!date) return `${fieldName} is required`;
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) return 'Invalid date format';
    return null;
  },

  // Match validation (for password confirmation)
  match: (value1, value2, fieldName = 'Fields') => {
    if (value1 !== value2) {
      return `${fieldName} do not match`;
    }
    return null;
  },
};

/**
 * Validate multiple fields at once
 * @param {Object} fields - Object with field names as keys and values to validate
 * @param {Object} rules - Object with field names as keys and validation rules
 * @returns {Object} - Object with field names as keys and error messages
 */
export const validateForm = (fields, rules) => {
  const errors = {};

  Object.keys(rules).forEach((fieldName) => {
    const fieldRules = rules[fieldName];
    const fieldValue = fields[fieldName];

    for (const rule of fieldRules) {
      const error = rule(fieldValue);
      if (error) {
        errors[fieldName] = error;
        break; // Stop at first error for this field
      }
    }
  });

  return errors;
};

/**
 * Check if form has any errors
 * @param {Object} errors - Errors object from validateForm
 * @returns {boolean}
 */
export const hasErrors = (errors) => {
  return Object.keys(errors).length > 0;
};