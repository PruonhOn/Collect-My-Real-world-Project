// Owner: Lin Sreydeth - input UI
import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';

/**
 * Input Component
 * Reusable input field with label, error handling, and icon support
 * 
 * @param {Object} props
 * @param {string} props.label - Input label text
 * @param {string} props.type - Input type (text, email, password, etc.)
 * @param {string} props.name - Input name attribute
 * @param {string} props.placeholder - Placeholder text
 * @param {string|number} props.value - Input value
 * @param {Function} props.onChange - Change handler function
 * @param {string} props.error - Error message to display
 * @param {boolean} props.required - Whether field is required
 * @param {boolean} props.disabled - Whether input is disabled
 * @param {React.Component} props.icon - Icon component to display
 * @param {string} props.className - Additional CSS classes
 */
const Input = ({ 
  label,
  type = 'text',
  name,
  placeholder,
  value,
  onChange,
  onBlur,
  error,
  required = false,
  disabled = false,
  icon: Icon,
  className = '',
  min,
  max,
  rows,
  autoComplete,
  ...props 
}) => {
  const [showPassword, setShowPassword] = useState(false);
  
  const isPassword = type === 'password';
  const isTextarea = type === 'textarea';
  const inputType = isPassword ? (showPassword ? 'text' : 'password') : type;
  
  // Base classes for input/textarea
  const baseClasses = 'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed';
  const normalClasses = 'border-gray-300 focus:border-primary-500 focus:ring-primary-500';
  const errorClasses = 'border-danger-500 focus:border-danger-500 focus:ring-danger-500';
  
  const inputClasses = `${baseClasses} ${error ? errorClasses : normalClasses} ${Icon ? 'pl-10' : ''} ${isPassword ? 'pr-10' : ''} ${className}`;
  
  return (
    <div className="w-full">
      {/* Label */}
      {label && (
        <label 
          htmlFor={name} 
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          {label}
          {required && <span className="text-danger-500 ml-1">*</span>}
        </label>
      )}
      
      {/* Input Container */}
      <div className="relative">
        {/* Left Icon */}
        {Icon && (
          <div className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            <Icon className="w-5 h-5" />
          </div>
        )}
        
        {/* Input or Textarea */}
        {isTextarea ? (
          <textarea
            id={name}
            name={name}
            value={value}
            onChange={onChange}
            onBlur={onBlur}
            placeholder={placeholder}
            disabled={disabled}
            required={required}
            rows={rows || 4}
            className={inputClasses}
            {...props}
          />
        ) : (
          <input
            id={name}
            name={name}
            type={inputType}
            value={value}
            onChange={onChange}
            onBlur={onBlur}
            placeholder={placeholder}
            disabled={disabled}
            required={required}
            min={min}
            max={max}
            autoComplete={autoComplete}
            className={inputClasses}
            {...props}
          />
        )}
        
        {/* Password Toggle Button */}
        {isPassword && (
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
            tabIndex={-1}
          >
            {showPassword ? (
              <EyeOff className="w-5 h-5" />
            ) : (
              <Eye className="w-5 h-5" />
            )}
          </button>
        )}
      </div>
      
      {/* Error Message */}
      {error && (
        <p className="mt-1 text-sm text-danger-500">{error}</p>
      )}
    </div>
  );
};

export default Input;