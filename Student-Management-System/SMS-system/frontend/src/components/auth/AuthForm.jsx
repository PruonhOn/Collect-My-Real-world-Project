// Owner: Chheng Phanna - shared login form
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, Lock, User, Phone } from 'lucide-react';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { validators, validateForm, hasErrors } from '@/utils/validators';

/**
 * Authentication Form Component
 * @param {Object} props
 * @param {string} props.type - 'login' | 'register'
 * @param {string} props.role - 'student' | 'admin' | 'lecturer'
 * @param {Function} props.onSubmit - Form submit handler
 * @param {boolean} props.loading - Loading state
 */
const AuthForm = ({ type = 'login', role = 'student', onSubmit, loading = false }) => {
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    password: '',
    confirmPassword: '',
    name: '',
    phone: '',
  });

  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    // Validation rules
    const rules = type === 'login'
      ? {
          username: [(val) => validators.required(val, 'Username')],
          password: [(val) => validators.required(val, 'Password')],
        }
      : {
          name: [(val) => validators.required(val, 'Full name')],
          username: [
            (val) => validators.required(val, 'Username'),
            (val) => validators.username(val),
          ],
          email: [
            (val) => validators.required(val, 'Email'),
            (val) => validators.email(val),
          ],
          phone: [
            (val) => validators.required(val, 'Phone'),
            (val) => validators.phone(val),
          ],
          password: [
            (val) => validators.required(val, 'Password'),
            (val) => validators.password(val),
          ],
          confirmPassword: [
            (val) => validators.required(val, 'Confirm password'),
            (val) => validators.match(formData.password, val, 'Passwords'),
          ],
        };

    // Validate form
    const validationErrors = validateForm(formData, rules);

    if (hasErrors(validationErrors)) {
      setErrors(validationErrors);
      return;
    }

    // Submit form
    const submitData = { ...formData, role };
    onSubmit(submitData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {type === 'register' && (
        <>
          <Input
            label="Full Name"
            name="name"
            type="text"
            placeholder="Enter your full name"
            value={formData.name}
            onChange={handleChange}
            error={errors.name}
            icon={User}
            required
          />

          <Input
            label="Email"
            name="email"
            type="email"
            placeholder="Enter your email"
            value={formData.email}
            onChange={handleChange}
            error={errors.email}
            icon={Mail}
            required
          />

          <Input
            label="Phone Number"
            name="phone"
            type="tel"
            placeholder="Enter your phone number"
            value={formData.phone}
            onChange={handleChange}
            error={errors.phone}
            icon={Phone}
            required
          />
        </>
      )}

      <Input
        label="Username"
        name="username"
        type="text"
        placeholder="Enter your username"
        value={formData.username}
        onChange={handleChange}
        error={errors.username}
        icon={User}
        required
      />

      <Input
        label="Password"
        name="password"
        type="password"
        placeholder="Enter your password"
        value={formData.password}
        onChange={handleChange}
        error={errors.password}
        icon={Lock}
        required
      />

      {type === 'register' && (
        <Input
          label="Confirm Password"
          name="confirmPassword"
          type="password"
          placeholder="Confirm your password"
          value={formData.confirmPassword}
          onChange={handleChange}
          error={errors.confirmPassword}
          icon={Lock}
          required
        />
      )}

      {type === 'login' && (
        <div className="flex items-center justify-between">
          <label className="flex items-center">
            <input
              type="checkbox"
              className="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
            />
            <span className="ml-2 text-sm text-gray-600">Remember me</span>
          </label>
          <Link
            to="/forgot-password"
            className="text-sm text-primary-600 hover:text-primary-700"
          >
            Forgot password?
          </Link>
        </div>
      )}

      <Button
        type="submit"
        variant="primary"
        className="w-full"
        loading={loading}
      >
        {type === 'login' ? 'Sign In' : 'Create Account'}
      </Button>

      <div className="text-center text-sm text-gray-600">
        {type === 'login' ? (
          <>
            Don't have an account?{' '}
            <Link to="/register-student" className="text-primary-600 hover:text-primary-700 font-medium">
              Register here
            </Link>
          </>
        ) : (
          <>
            Already have an account?{' '}
            <Link to="/login-student" className="text-primary-600 hover:text-primary-700 font-medium">
              Sign in
            </Link>
          </>
        )}
      </div>
    </form>
  );
};

export default AuthForm;