// Owner: Dang Selive - student profile form
// Owner: Student Profile Page
import { useState, useEffect } from 'react';
import { User, Mail, Phone, Calendar, MapPin, Save, Lock } from 'lucide-react';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import Button from '@/components/ui/Button';
import Input from '@/components/ui/Input';
import apiService from '@/services/api';

const StudentProfile = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('profile');
  const [loading, setLoading] = useState(false);
  const [profileData, setProfileData] = useState({
    name: '',
    username: '',
    email: '',
    phone: '',
    gender: '',
    dob: '',
    department: '',
    year_level: '',
    address: '',
  });
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    confirm_password: '',
  });
  const [errors, setErrors] = useState({});
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await apiService.get('/student/profile');
      if (response.success) {
        setProfileData(response.data);
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
    }
  };

  const handleProfileChange = (e) => {
    const { name, value } = e.target;
    setProfileData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const handlePasswordChange = (e) => {
    const { name, value } = e.target;
    setPasswordData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccess('');

    try {
      setLoading(true);
      const response = await apiService.put('/student/profile', profileData);
      if (response.success) {
        setSuccess('Profile updated successfully');
      }
    } catch (error) {
      setErrors({ submit: error.message });
    } finally {
      setLoading(false);
    }
  };

  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccess('');

    if (passwordData.new_password !== passwordData.confirm_password) {
      setErrors({ confirm_password: 'Passwords do not match' });
      return;
    }

    try {
      setLoading(true);
      const response = await apiService.post('/student/change-password', {
        current_password: passwordData.current_password,
        new_password: passwordData.new_password,
      });
      if (response.success) {
        setSuccess('Password changed successfully');
        setPasswordData({
          current_password: '',
          new_password: '',
          confirm_password: '',
        });
      }
    } catch (error) {
      setErrors({ submit: error.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="lg:pl-64 pt-16">
        <main className="p-6">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">My Profile</h1>
            <p className="text-gray-600 mt-2">Manage your personal information</p>
          </div>

          <div className="bg-white rounded-lg shadow">
            <div className="border-b">
              <nav className="flex gap-4 px-6">
                <button
                  onClick={() => setActiveTab('profile')}
                  className={`flex items-center gap-2 px-4 py-4 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'profile'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  <User className="w-4 h-4" />
                  Profile Information
                </button>
                <button
                  onClick={() => setActiveTab('password')}
                  className={`flex items-center gap-2 px-4 py-4 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'password'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  <Lock className="w-4 h-4" />
                  Change Password
                </button>
              </nav>
            </div>

            <div className="p-6">
              {success && (
                <div className="mb-6 bg-success-50 border border-success-200 text-success-700 px-4 py-3 rounded-lg">
                  {success}
                </div>
              )}

              {errors.submit && (
                <div className="mb-6 bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-lg">
                  {errors.submit}
                </div>
              )}

              {activeTab === 'profile' && (
                <form onSubmit={handleProfileSubmit} className="max-w-3xl space-y-4">
                  <div className="grid md:grid-cols-2 gap-4">
                    <Input
                      label="Full Name"
                      name="name"
                      icon={User}
                      value={profileData.name}
                      onChange={handleProfileChange}
                      error={errors.name}
                      required
                    />
                    <Input
                      label="Username"
                      name="username"
                      icon={User}
                      value={profileData.username}
                      onChange={handleProfileChange}
                      error={errors.username}
                      disabled
                    />
                    <Input
                      label="Email"
                      name="email"
                      type="email"
                      icon={Mail}
                      value={profileData.email}
                      onChange={handleProfileChange}
                      error={errors.email}
                      required
                    />
                    <Input
                      label="Phone"
                      name="phone"
                      icon={Phone}
                      value={profileData.phone}
                      onChange={handleProfileChange}
                      error={errors.phone}
                      required
                    />
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Gender
                      </label>
                      <select
                        name="gender"
                        value={profileData.gender}
                        onChange={handleProfileChange}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                      >
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <Input
                      label="Date of Birth"
                      name="dob"
                      type="date"
                      icon={Calendar}
                      value={profileData.dob}
                      onChange={handleProfileChange}
                      error={errors.dob}
                    />
                    <Input
                      label="Department"
                      name="department"
                      value={profileData.department}
                      onChange={handleProfileChange}
                      error={errors.department}
                      disabled
                    />
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">
                        Year Level
                      </label>
                      <select
                        name="year_level"
                        value={profileData.year_level}
                        onChange={handleProfileChange}
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                        disabled
                      >
                        <option value="">Select Year</option>
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                      </select>
                    </div>
                  </div>

                  <Input
                    label="Address"
                    name="address"
                    type="textarea"
                    rows={3}
                    icon={MapPin}
                    value={profileData.address}
                    onChange={handleProfileChange}
                    error={errors.address}
                  />

                  <div className="pt-4">
                    <Button type="submit" variant="primary" loading={loading}>
                      <Save className="w-4 h-4 mr-2" />
                      Save Changes
                    </Button>
                  </div>
                </form>
              )}

              {activeTab === 'password' && (
                <form onSubmit={handlePasswordSubmit} className="max-w-2xl space-y-4">
                  <Input
                    label="Current Password"
                    name="current_password"
                    type="password"
                    icon={Lock}
                    value={passwordData.current_password}
                    onChange={handlePasswordChange}
                    error={errors.current_password}
                    required
                  />
                  <Input
                    label="New Password"
                    name="new_password"
                    type="password"
                    icon={Lock}
                    value={passwordData.new_password}
                    onChange={handlePasswordChange}
                    error={errors.new_password}
                    required
                  />
                  <Input
                    label="Confirm New Password"
                    name="confirm_password"
                    type="password"
                    icon={Lock}
                    value={passwordData.confirm_password}
                    onChange={handlePasswordChange}
                    error={errors.confirm_password}
                    required
                  />
                  <div className="pt-4">
                    <Button type="submit" variant="primary" loading={loading}>
                      <Save className="w-4 h-4 mr-2" />
                      Change Password
                    </Button>
                  </div>
                </form>
              )}
            </div>
          </div>
        </main>
      </div>
    </div>
  );
};

export default StudentProfile;