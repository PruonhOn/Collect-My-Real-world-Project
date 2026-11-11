// Owner: Dang Selive - student dashboard UI
import { useState, useEffect } from 'react';
import { BookOpen, Calendar, FileText, TrendingUp } from 'lucide-react';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import { useAuth } from '@/hooks/useAuth';
import apiService from '@/services/api';
import { formatDate } from '@/utils/helpers';

/**
 * Student Dashboard Page
 */
const StudentDashboard = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [dashboardData, setDashboardData] = useState({
    courses: [],
    recentGrades: [],
    announcements: [],
    stats: {
      enrolled_courses: 0,
      total_credits: 0,
      attendance_rate: 0,
      gpa: 0,
    },
  });

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await apiService.get('/student/dashboard');
      if (response.success) {
        setDashboardData(response.data);
      }
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const stats = [
    {
      title: 'Enrolled Courses',
      value: dashboardData.stats.enrolled_courses,
      icon: BookOpen,
      color: 'bg-blue-500',
    },
    {
      title: 'Total Credits',
      value: dashboardData.stats.total_credits,
      icon: FileText,
      color: 'bg-green-500',
    },
    {
      title: 'Attendance Rate',
      value: `${dashboardData.stats.attendance_rate}%`,
      icon: Calendar,
      color: 'bg-purple-500',
    },
    {
      title: 'GPA',
      value: dashboardData.stats.gpa,
      icon: TrendingUp,
      color: 'bg-orange-500',
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="lg:pl-64 pt-16">
        <main className="p-6">
          {/* Welcome section */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">
              Welcome back, {user?.name}!
            </h1>
            <p className="text-gray-600 mt-2">Here's your academic overview</p>
          </div>

          {/* Statistics cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {stats.map((stat) => {
              const Icon = stat.icon;
              return (
                <div key={stat.title} className="bg-white rounded-lg shadow p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-gray-600 mb-1">{stat.title}</p>
                      <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                    </div>
                    <div className={`w-12 h-12 ${stat.color} rounded-lg flex items-center justify-center`}>
                      <Icon className="w-6 h-6 text-white" />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="grid lg:grid-cols-2 gap-6">
            {/* Current courses */}
            <div className="bg-white rounded-lg shadow">
              <div className="p-6 border-b">
                <h2 className="text-xl font-semibold text-gray-900">Current Courses</h2>
              </div>
              <div className="p-6">
                {loading ? (
                  <div className="text-center py-8">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                  </div>
                ) : dashboardData.courses.length > 0 ? (
                  <div className="space-y-4">
                    {dashboardData.courses.map((course) => (
                      <div key={course.course_id} className="p-4 border rounded-lg hover:bg-gray-50">
                        <div className="flex items-start justify-between">
                          <div>
                            <h3 className="font-semibold text-gray-900">{course.course_name}</h3>
                            <p className="text-sm text-gray-600">{course.course_code}</p>
                            <p className="text-sm text-gray-600 mt-1">
                              Lecturer: {course.lecturer_name}
                            </p>
                          </div>
                          <span className="text-sm font-medium text-primary-600">
                            {course.credit} Credits
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-center text-gray-500 py-8">No courses enrolled</p>
                )}
              </div>
            </div>

            {/* Recent announcements */}
            <div className="bg-white rounded-lg shadow">
              <div className="p-6 border-b">
                <h2 className="text-xl font-semibold text-gray-900">Announcements</h2>
              </div>
              <div className="p-6">
                {loading ? (
                  <div className="text-center py-8">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                  </div>
                ) : dashboardData.announcements.length > 0 ? (
                  <div className="space-y-4">
                    {dashboardData.announcements.map((announcement) => (
                      <div key={announcement.announcement_id} className="pb-4 border-b last:border-0">
                        <h3 className="font-semibold text-gray-900 mb-1">
                          {announcement.title}
                        </h3>
                        <p className="text-sm text-gray-600 mb-2">
                          {announcement.content}
                        </p>
                        <p className="text-xs text-gray-500">
                          {formatDate(announcement.created_at)}
                        </p>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-center text-gray-500 py-8">No announcements</p>
                )}
              </div>
            </div>

            {/* Recent grades */}
            <div className="bg-white rounded-lg shadow lg:col-span-2">
              <div className="p-6 border-b">
                <h2 className="text-xl font-semibold text-gray-900">Recent Grades</h2>
              </div>
              <div className="p-6">
                {loading ? (
                  <div className="text-center py-8">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                  </div>
                ) : dashboardData.recentGrades.length > 0 ? (
                  <div className="overflow-x-auto">
                    <table className="min-w-full">
                      <thead>
                        <tr className="border-b">
                          <th className="text-left py-3 px-4 text-sm font-semibold text-gray-900">Course</th>
                          <th className="text-left py-3 px-4 text-sm font-semibold text-gray-900">Assessment</th>
                          <th className="text-left py-3 px-4 text-sm font-semibold text-gray-900">Grade</th>
                          <th className="text-left py-3 px-4 text-sm font-semibold text-gray-900">Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        {dashboardData.recentGrades.map((grade, index) => (
                          <tr key={index} className="border-b last:border-0">
                            <td className="py-3 px-4 text-sm text-gray-900">{grade.course_name}</td>
                            <td className="py-3 px-4 text-sm text-gray-600">{grade.title}</td>
                            <td className="py-3 px-4">
                              <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded ${
                                grade.grade >= 90 ? 'bg-green-100 text-green-800' :
                                grade.grade >= 80 ? 'bg-blue-100 text-blue-800' :
                                grade.grade >= 70 ? 'bg-yellow-100 text-yellow-800' :
                                'bg-red-100 text-red-800'
                              }`}>
                                {grade.grade}
                              </span>
                            </td>
                            <td className="py-3 px-4 text-sm text-gray-600">
                              {formatDate(grade.created_at)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <p className="text-center text-gray-500 py-8">No grades available</p>
                )}
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  );
};

export default StudentDashboard;