// Owner: Dang Selive - student course list
// Owner: Student Courses Page
import { useState, useEffect } from 'react';
import { BookOpen, Users, Clock, CheckCircle } from 'lucide-react';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import Button from '@/components/ui/Button';
import apiService from '@/services/api';

const StudentCourses = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [courses, setCourses] = useState([]);
  const [enrolledCourses, setEnrolledCourses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('enrolled');

  useEffect(() => {
    fetchCourses();
  }, []);

  const fetchCourses = async () => {
    try {
      setLoading(true);
      const response = await apiService.get('/student/courses');
      if (response.success) {
        setEnrolledCourses(response.data.enrolled || []);
        setCourses(response.data.available || []);
      }
    } catch (error) {
      console.error('Error fetching courses:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleEnroll = async (courseId) => {
    try {
      const response = await apiService.post('/student/courses/enroll', {
        course_id: courseId,
      });
      if (response.success) {
        fetchCourses();
      }
    } catch (error) {
      console.error('Error enrolling in course:', error);
    }
  };

  const CourseCard = ({ course, isEnrolled }) => (
    <div className="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="bg-primary-100 p-3 rounded-lg">
            <BookOpen className="w-6 h-6 text-primary-600" />
          </div>
          <div>
            <h3 className="font-semibold text-gray-900">{course.course_name}</h3>
            <p className="text-sm text-gray-500">{course.course_code}</p>
          </div>
        </div>
        {isEnrolled && (
          <span className="bg-success-100 text-success-700 text-xs px-3 py-1 rounded-full font-medium">
            Enrolled
          </span>
        )}
      </div>

      <p className="text-gray-600 text-sm mb-4 line-clamp-2">
        {course.description || 'No description available'}
      </p>

      <div className="grid grid-cols-3 gap-4 mb-4">
        <div className="text-center">
          <p className="text-xs text-gray-500">Credits</p>
          <p className="font-semibold text-gray-900">{course.credits}</p>
        </div>
        <div className="text-center">
          <p className="text-xs text-gray-500">Semester</p>
          <p className="font-semibold text-gray-900">{course.semester}</p>
        </div>
        <div className="text-center">
          <p className="text-xs text-gray-500">Year</p>
          <p className="font-semibold text-gray-900">{course.year_level}</p>
        </div>
      </div>

      <div className="flex items-center justify-between pt-4 border-t">
        <div className="flex items-center gap-2 text-sm text-gray-600">
          <Users className="w-4 h-4" />
          <span>{course.enrolled_count || 0} students</span>
        </div>
        {!isEnrolled && (
          <Button
            variant="primary"
            size="sm"
            onClick={() => handleEnroll(course.course_id)}
          >
            Enroll
          </Button>
        )}
        {isEnrolled && (
          <Button variant="ghost" size="sm">
            View Details
          </Button>
        )}
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="lg:pl-64 pt-16">
        <main className="p-6">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">My Courses</h1>
            <p className="text-gray-600 mt-2">View and manage your enrolled courses</p>
          </div>

          <div className="mb-6">
            <div className="border-b">
              <nav className="flex gap-4">
                <button
                  onClick={() => setActiveTab('enrolled')}
                  className={`px-4 py-3 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'enrolled'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  Enrolled Courses ({enrolledCourses.length})
                </button>
                <button
                  onClick={() => setActiveTab('available')}
                  className={`px-4 py-3 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'available'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  Available Courses ({courses.length})
                </button>
              </nav>
            </div>
          </div>

          {loading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {activeTab === 'enrolled' ? (
                enrolledCourses.length > 0 ? (
                  enrolledCourses.map((course) => (
                    <CourseCard key={course.course_id} course={course} isEnrolled={true} />
                  ))
                ) : (
                  <div className="col-span-full text-center py-12">
                    <BookOpen className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <p className="text-gray-600">You haven't enrolled in any courses yet</p>
                    <Button
                      variant="primary"
                      className="mt-4"
                      onClick={() => setActiveTab('available')}
                    >
                      Browse Available Courses
                    </Button>
                  </div>
                )
              ) : courses.length > 0 ? (
                courses.map((course) => (
                  <CourseCard key={course.course_id} course={course} isEnrolled={false} />
                ))
              ) : (
                <div className="col-span-full text-center py-12">
                  <BookOpen className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                  <p className="text-gray-600">No courses available at the moment</p>
                </div>
              )}
            </div>
          )}
        </main>
      </div>
    </div>
  );
};

export default StudentCourses;