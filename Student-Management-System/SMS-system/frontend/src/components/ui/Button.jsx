import { Loader2 } from 'lucide-react';

/**
 * Button Component
 * Reusable button with multiple variants and sizes
 * 
 * @param {Object} props
 * @param {string} props.variant - Button style variant
 * @param {string} props.size - Button size
 * @param {boolean} props.loading - Show loading spinner
 * @param {boolean} props.disabled - Disable button
 * @param {string} props.type - Button type (button, submit, reset)
 * @param {Function} props.onClick - Click handler
 * @param {React.ReactNode} props.children - Button content
 * @param {string} props.className - Additional CSS classes
 */
const Button = ({ 
  variant = 'primary', 
  size = 'md',
  loading = false,
  disabled = false,
  type = 'button',
  onClick,
  children,
  className = '',
  ...props 
}) => {
  // Base button classes
  const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
  
  // Variant classes
  const variantClasses = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
    secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
    danger: 'bg-danger-500 text-white hover:bg-danger-600 focus:ring-danger-500',
    success: 'bg-success-500 text-white hover:bg-success-600 focus:ring-success-500',
    warning: 'bg-warning-500 text-white hover:bg-warning-600 focus:ring-warning-500',
    ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
    outline: 'border-2 border-primary-600 text-primary-600 bg-white hover:bg-primary-50 focus:ring-primary-500',
    link: 'bg-transparent text-primary-600 hover:text-primary-700 hover:underline focus:ring-primary-500',
  };
  
  // Size classes
  const sizeClasses = {
    xs: 'px-2 py-1 text-xs',
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-base',
    lg: 'px-6 py-3 text-lg',
    xl: 'px-8 py-4 text-xl',
  };
  
  // Combine all classes
  const classes = `${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${className}`;
  
  return (
    <button
      type={type}
      className={classes}
      onClick={onClick}
      disabled={disabled || loading}
      {...props}
    >
      {loading && (
        <Loader2 className="w-4 h-4 mr-2 animate-spin" />
      )}
      {children}
    </button>
  );
};

export default Button;