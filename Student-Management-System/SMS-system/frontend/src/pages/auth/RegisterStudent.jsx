// src/pages/auth/RegisterStudent.jsx
import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { User, Mail, Lock, Phone, Calendar, Sun, Moon, ArrowLeft } from 'lucide-react';
import Button from '@/components/ui/Button';
import Input from '@/components/ui/Input';
import authService from '@/services/auth';
import { useTheme } from '@/contexts/ThemeContext';

const RegisterStudent = () => {
  const navigate = useNavigate();
  const { theme, toggleTheme } = useTheme();
  const dark = theme === 'dark';

  const [formData, setFormData] = useState({
    name: '',
    username: '',
    email: '',
    phone: '',
    gender: '',
    dob: '',
    department: '',
    year_level: '',
    password: '',
    confirm_password: '',
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: null }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    const newErrors = {};
    if (!formData.name) newErrors.name = 'Name is required';
    if (!formData.username) newErrors.username = 'Username is required';
    if (!formData.email) newErrors.email = 'Email is required';
    if (!formData.phone) newErrors.phone = 'Phone is required';
    if (!formData.gender) newErrors.gender = 'Gender is required';
    if (!formData.dob) newErrors.dob = 'Date of birth is required';
    if (!formData.department) newErrors.department = 'Department is required';
    if (!formData.year_level) newErrors.year_level = 'Year level is required';
    if (!formData.password) newErrors.password = 'Password is required';
    if (formData.password.length < 6) newErrors.password = 'Password must be at least 6 characters';
    if (formData.password !== formData.confirm_password) {
      newErrors.confirm_password = 'Passwords do not match';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    try {
      setLoading(true);
      const { confirm_password, ...registrationData } = formData;
      const result = await authService.register(registrationData);
      if (result.success) {
        navigate('/student/dashboard');
      } else {
        setErrors({ submit: result.message || 'Registration failed' });
      }
    } catch (error) {
      setErrors({ submit: error.message || 'Registration failed' });
    } finally {
      setLoading(false);
    }
  };

  const inputClass = `peer w-full px-4 py-3 rounded-lg border transition-colors duration-200
    ${dark 
      ? 'bg-gray-800 border-gray-600 text-gray-100 placeholder-gray-400 focus:ring-indigo-500 focus:border-indigo-500' 
      : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500 focus:ring-indigo-500 focus:border-indigo-500'
    }`;

  return (
    <div className={`min-h-screen flex items-center justify-center p-6 transition-colors duration-300 ${dark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className={`w-full max-w-4xl rounded-2xl shadow-lg p-8 backdrop-blur-sm transition-colors duration-300
        ${dark ? 'bg-gray-900/90 border border-gray-700 text-gray-100' : 'bg-white text-gray-900'}`}>
        
        <div className="mt-4 text-start">
          <Link to="/landing" className="text-indigo-600 dark:text-indigo-400 font-medium hover:underline inline-flex items-center gap-1">
            <ArrowLeft className="w-4 h-4" /> Back
          </Link>
        </div>

        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className={`text-2xl md:text-3xl font-extrabold ${dark ? 'text-gray-100' : 'text-gray-900'}`}>Student Registration</h1>
            <p className={`text-sm ${dark ? 'text-gray-300' : 'text-gray-600'} mt-1`}>Create your student account</p>
          </div>
          <button
            onClick={toggleTheme}
            className={`inline-flex items-center gap-2 px-3 py-2 rounded-lg ${dark ? 'bg-gray-800 border border-gray-700 text-gray-200' : 'bg-gray-100 border border-gray-200 text-gray-700'} shadow-sm hover:shadow-md transition`}
            aria-label="Toggle theme"
            title={`Switch to ${dark ? 'light' : 'dark'} mode`}
          >
            {dark ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
            <span className="hidden sm:inline text-xs">{dark ? 'Light' : 'Dark'}</span>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          {errors.submit && (
            <div className={`rounded-lg px-4 py-3 text-sm ${dark ? 'bg-red-900/40 border-red-700 text-red-200' : 'bg-red-50 border-red-200 text-red-700'}`}>
              {errors.submit}
            </div>
          )}

          <div className="grid md:grid-cols-2 gap-4">
            <Input label="Full Name" name="name" icon={User} value={formData.name} onChange={handleChange} error={errors.name} required className={inputClass} />
            <Input label="Username" name="username" icon={User} value={formData.username} onChange={handleChange} error={errors.username} required className={inputClass} />
            <Input label="Email" name="email" type="email" icon={Mail} value={formData.email} onChange={handleChange} error={errors.email} required className={inputClass} />
            <Input label="Phone" name="phone" icon={Phone} value={formData.phone} onChange={handleChange} error={errors.phone} required className={inputClass} />

            <div className="space-y-1">
              <label className={`block text-sm font-medium ${dark ? 'text-gray-100' : 'text-gray-900'}`}>Gender <span className="text-red-500">*</span></label>
              <select
                name="gender"
                value={formData.gender}
                onChange={handleChange}
                required
                className={`w-full px-4 py-3 rounded-lg border transition-colors duration-200 ${dark ? 'bg-gray-800 border-gray-600 text-gray-100 focus:ring-indigo-500 focus:border-indigo-500' : 'bg-white border-gray-300 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500'}`}
              >
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
              {errors.gender && <p className="text-sm text-red-500 mt-1">{errors.gender}</p>}
            </div>

            <Input label="Date of Birth" name="dob" type="date" icon={Calendar} value={formData.dob} onChange={handleChange} error={errors.dob} required className={inputClass} />
            <Input label="Department" name="department" value={formData.department} onChange={handleChange} error={errors.department} required className={inputClass} />

            <div className="space-y-1">
              <label className={`block text-sm font-medium ${dark ? 'text-gray-100' : 'text-gray-900'}`}>Year Level <span className="text-red-500">*</span></label>
              <select
                name="year_level"
                value={formData.year_level}
                onChange={handleChange}
                required
                className={`w-full px-4 py-3 rounded-lg border transition-colors duration-200 ${dark ? 'bg-gray-800 border-gray-600 text-gray-100 focus:ring-indigo-500 focus:border-indigo-500' : 'bg-white border-gray-300 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500'}`}
              >
                <option value="">Select Year</option>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
              </select>
              {errors.year_level && <p className="text-sm text-red-500 mt-1">{errors.year_level}</p>}
            </div>

            <div className="relative">
              <Input
                label="Password"
                name="password"
                type={showPassword ? 'text' : 'password'}
                icon={Lock}
                value={formData.password}
                onChange={handleChange}
                error={errors.password}
                required
                className={inputClass}
              />
              <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                {showPassword ? '👁️' : '🙈'}
              </button>
            </div>

            <div className="relative">
              <Input
                label="Confirm Password"
                name="confirm_password"
                type={showConfirmPassword ? 'text' : 'password'}
                icon={Lock}
                value={formData.confirm_password}
                onChange={handleChange}
                error={errors.confirm_password}
                required
                className={inputClass}
              />
              <button type="button" onClick={() => setShowConfirmPassword(!showConfirmPassword)} className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                {showConfirmPassword ? '👁️' : '🙈'}
              </button>
            </div>
          </div>

          <div className="pt-4">
            <Button type="submit" variant="primary" className="w-full" loading={loading}>Register</Button>
          </div>
        </form>

        <div className="mt-6 text-center">
          <p className={`text-sm ${dark ? 'text-gray-300' : 'text-gray-600'}`}>
            Already have an account?{' '}
            <Link to="/auth/login-student" className="text-indigo-600 dark:text-indigo-400 font-medium">Login here</Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default RegisterStudent;
