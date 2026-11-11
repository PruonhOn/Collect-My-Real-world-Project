// Owner: Admin Settings Management
import { useState, useEffect } from 'react';
import { Save, User, Lock, Bell, Database } from 'lucide-react';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import Button from '@/components/ui/Button';
import Input from '@/components/ui/Input';
import apiService from '@/services/api';

const AdminSettings = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('profile');
  const [loading, setLoading] = useState(false);

  const [profileData, setProfileData] = useState({
    name: '',
    username: '',
    email: '',
    phone: '',
  });

  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    confirm_password: '',
  });

  const [systemSettings, setSystemSettings] = useState({
    site_name: 'Student Management System',
    semester: '1',
    academic_year: '2024-2025',
    allow_registration: true,
  });

  const [errors, setErrors] = useState({});
  const [success, setSuccess] = useState('');

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await apiService.get('/admin/profile');
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

  const handleSystemChange = (e) => {
    const { name, value, type, checked } = e.target;
    setSystemSettings(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccess('');

    try {
      setLoading(true);
      const response = await apiService.put('/admin/profile', profileData);
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
      const response = await apiService.post('/admin/change-password', {
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

  const handleSystemSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccess('');

    try {
      setLoading(true);
      const response = await apiService.put('/admin/settings', systemSettings);
      if (response.success) {
        setSuccess('System settings updated successfully');
      }
    } catch (error) {
      setErrors({ submit: error.message });
    } finally {
      setLoading(false);
    }
  };

  const tabs = [
    { id: 'profile', label: 'Profile', icon: User },
    { id: 'password', label: 'Password', icon: Lock },
    { id: 'system', label: 'System', icon: Database },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="lg:pl-64 pt-16">
        <main className="p-6">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">Settings</h1>
            <p className="text-gray-600 mt-2">Manage your account and system settings</p>
          </div>

          <div className="bg-white rounded-lg shadow">
            <div className="border-b">
              <nav className="flex gap-4 px-6">
                {tabs.map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex items-center gap-2 px-4 py-4 border-b-2 font-medium text-sm transition-colors ${
                      activeTab === tab.id
                        ? 'border-primary-600 text-primary-600'
                        : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                    }`}
                  >
                    <tab.icon className="w-4 h-4" />
                    {tab.label}
                  </button>
                ))}
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
                <form onSubmit={handleProfileSubmit} className="max-w-2xl space-y-4">
                  <Input
                    label="Full Name"
                    name="name"
                    value={profileData.name}
                    onChange={handleProfileChange}
                    error={errors.name}
                    required
                  />
                  <Input
                    label="Username"
                    name="username"
                    value={profileData.username}
                    onChange={handleProfileChange}
                    error={errors.username}
                    required
                  />
                  <Input
                    label="Email"
                    name="email"
                    type="email"
                    value={profileData.email}
                    onChange={handleProfileChange}
                    error={errors.email}
                    required
                  />
                  <Input
                    label="Phone"
                    name="phone"
                    value={profileData.phone}
                    onChange={handleProfileChange}
                    error={errors.phone}
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
                    value={passwordData.current_password}
                    onChange={handlePasswordChange}
                    error={errors.current_password}
                    required
                  />
                  <Input
                    label="New Password"
                    name="new_password"
                    type="password"
                    value={passwordData.new_password}
                    onChange={handlePasswordChange}
                    error={errors.new_password}
                    required
                  />
                  <Input
                    label="Confirm New Password"
                    name="confirm_password"
                    type="password"
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

              {activeTab === 'system' && (
                <form onSubmit={handleSystemSubmit} className="max-w-2xl space-y-4">
                  <Input
                    label="Site Name"
                    name="site_name"
                    value={systemSettings.site_name}
                    onChange={handleSystemChange}
                    required
                  />
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Current Semester
                    </label>
                    <select
                      name="semester"
                      value={systemSettings.semester}
                      onChange={handleSystemChange}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                      <option value="1">Semester 1</option>
                      <option value="2">Semester 2</option>
                    </select>
                  </div>
                  <Input
                    label="Academic Year"
                    name="academic_year"
                    value={systemSettings.academic_year}
                    onChange={handleSystemChange}
                    required
                  />
                  <div className="flex items-center gap-3">
                    <input
                      type="checkbox"
                      id="allow_registration"
                      name="allow_registration"
                      checked={systemSettings.allow_registration}
                      onChange={handleSystemChange}
                      className="w-4 h-4 text-primary-600 rounded focus:ring-primary-500"
                    />
                    <label htmlFor="allow_registration" className="text-sm text-gray-700">
                      Allow student registration
                    </label>
                  </div>
                  <div className="pt-4">
                    <Button type="submit" variant="primary" loading={loading}>
                      <Save className="w-4 h-4 mr-2" />
                      Save Settings
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

export default AdminSettings;