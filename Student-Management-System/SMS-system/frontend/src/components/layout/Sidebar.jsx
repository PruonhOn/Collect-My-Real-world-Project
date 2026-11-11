// Owner: Lin Sreydeth - sidebar navigation
// Owner: Sidebar Navigation Component
import { X, Home, Users, BookOpen, ClipboardList, Settings, LogOut, GraduationCap, UserCircle } from 'lucide-react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import authService from '@/services/auth';

const Sidebar = ({ isOpen, onClose }) => {
  const location = useLocation();
  const navigate = useNavigate();
  const user = authService.getCurrentUser();
  const isAdmin = user?.role === 'admin';

  const handleLogout = async () => {
    await authService.logout();
    navigate('/');
  };

  const adminMenuItems = [
    { path: '/admin/dashboard', icon: Home, label: 'Dashboard' },
    { path: '/admin/students', icon: Users, label: 'Students' },
    { path: '/admin/courses', icon: BookOpen, label: 'Courses' },
    { path: '/admin/settings', icon: Settings, label: 'Settings' },
  ];

  const studentMenuItems = [
    { path: '/student/dashboard', icon: Home, label: 'Dashboard' },
    { path: '/student/courses', icon: BookOpen, label: 'My Courses' },
    { path: '/student/exams', icon: ClipboardList, label: 'My Exams' },
    { path: '/student/profile', icon: UserCircle, label: 'My Profile' },
  ];

  const menuItems = isAdmin ? adminMenuItems : studentMenuItems;

  return (
    <>
      {/* Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed top-0 left-0 z-50 h-screen w-64 bg-white border-r border-gray-200 transition-transform duration-300 ease-in-out lg:translate-x-0 ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b">
            <div className="flex items-center gap-3">
              <div className="bg-primary-600 p-2 rounded-lg">
                <GraduationCap className="w-6 h-6 text-white" />
              </div>
              <div>
                <h2 className="font-bold text-gray-900">SMS</h2>
                <p className="text-xs text-gray-500">{isAdmin ? 'Admin' : 'Student'}</p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="lg:hidden text-gray-500 hover:text-gray-700"
            >
              <X className="w-6 h-6" />
            </button>
          </div>

          {/* User Info */}
          <div className="p-6 border-b">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                <span className="text-primary-600 font-semibold">
                  {user?.name?.charAt(0).toUpperCase() || 'U'}
                </span>
              </div>
              <div>
                <p className="font-medium text-gray-900">{user?.name || 'User'}</p>
                <p className="text-xs text-gray-500">{user?.email || ''}</p>
              </div>
            </div>
          </div>

          {/* Navigation */}
          <nav className="flex-1 p-4 overflow-y-auto">
            <ul className="space-y-2">
              {menuItems.map((item) => {
                const isActive = location.pathname === item.path;
                return (
                  <li key={item.path}>
                    <Link
                      to={item.path}
                      onClick={onClose}
                      className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-colors ${
                        isActive
                          ? 'bg-primary-50 text-primary-600'
                          : 'text-gray-700 hover:bg-gray-100'
                      }`}
                    >
                      <item.icon className="w-5 h-5" />
                      <span className="font-medium">{item.label}</span>
                    </Link>
                  </li>
                );
              })}
            </ul>
          </nav>

          {/* Logout */}
          <div className="p-4 border-t">
            <button
              onClick={handleLogout}
              className="flex items-center gap-3 w-full px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <LogOut className="w-5 h-5" />
              <span className="font-medium">Logout</span>
            </button>
          </div>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;