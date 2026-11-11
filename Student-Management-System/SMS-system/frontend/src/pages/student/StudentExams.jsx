// Owner: Dang Selive - student exams page
// Owner: Student Exams Page
import { useState, useEffect } from 'react';
import { ClipboardList, Calendar, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import Button from '@/components/ui/Button';
import apiService from '@/services/api';

const StudentExams = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [exams, setExams] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('upcoming');

  useEffect(() => {
    fetchExams();
  }, []);

  const fetchExams = async () => {
    try {
      setLoading(true);
      const response = await apiService.get('/student/exams');
      if (response.success) {
        setExams(response.data || []);
      }
    } catch (error) {
      console.error('Error fetching exams:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (exam) => {
    if (exam.status === 'completed') {
      return (
        <span className="bg-success-100 text-success-700 text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
          <CheckCircle className="w-3 h-3" />
          Completed
        </span>
      );
    }
    if (exam.status === 'missed') {
      return (
        <span className="bg-danger-100 text-danger-700 text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
          <XCircle className="w-3 h-3" />
          Missed
        </span>
      );
    }
    return (
      <span className="bg-warning-100 text-warning-700 text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
        <AlertCircle className="w-3 h-3" />
        Upcoming
      </span>
    );
  };

  const filteredExams = exams.filter((exam) => {
    if (activeTab === 'upcoming') return exam.status === 'upcoming';
    if (activeTab === 'completed') return exam.status === 'completed';
    return true;
  });

  const ExamCard = ({ exam }) => (
    <div className="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="bg-primary-100 p-3 rounded-lg">
            <ClipboardList className="w-6 h-6 text-primary-600" />
          </div>
          <div>
            <h3 className="font-semibold text-gray-900">{exam.exam_name}</h3>
            <p className="text-sm text-gray-500">{exam.course_name}</p>
          </div>
        </div>
        {getStatusBadge(exam)}
      </div>

      <div className="space-y-3 mb-4">
        <div className="flex items-center gap-2 text-sm text-gray-600">
          <Calendar className="w-4 h-4" />
          <span>{exam.exam_date}</span>
        </div>
        <div className="flex items-center gap-2 text-sm text-gray-600">
          <Clock className="w-4 h-4" />
          <span>{exam.start_time} - {exam.end_time}</span>
        </div>
      </div>

      {exam.status === 'completed' && exam.score !== undefined && (
        <div className="bg-gray-50 rounded-lg p-4 mb-4">
          <div className="flex items-center justify-between">
            <span className="text-sm text-gray-600">Your Score</span>
            <span className="text-2xl font-bold text-primary-600">
              {exam.score}/{exam.total_marks}
            </span>
          </div>
          <div className="mt-2">
            <div className="w-full bg-gray-200 rounded-full h-2">
              <div
                className="bg-primary-600 h-2 rounded-full"
                style={{ width: `${(exam.score / exam.total_marks) * 100}%` }}
              ></div>
            </div>
          </div>
        </div>
      )}

      <div className="flex items-center justify-between pt-4 border-t">
        <div className="text-sm text-gray-600">
          <span className="font-medium">Total Marks:</span> {exam.total_marks}
        </div>
        {exam.status === 'upcoming' && (
          <Button variant="primary" size="sm">
            View Details
          </Button>
        )}
        {exam.status === 'completed' && (
          <Button variant="ghost" size="sm">
            View Results
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
            <h1 className="text-3xl font-bold text-gray-900">My Exams</h1>
            <p className="text-gray-600 mt-2">View your upcoming and completed exams</p>
          </div>

          <div className="mb-6">
            <div className="border-b">
              <nav className="flex gap-4">
                <button
                  onClick={() => setActiveTab('upcoming')}
                  className={`px-4 py-3 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'upcoming'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  Upcoming
                </button>
                <button
                  onClick={() => setActiveTab('completed')}
                  className={`px-4 py-3 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'completed'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  Completed
                </button>
                <button
                  onClick={() => setActiveTab('all')}
                  className={`px-4 py-3 border-b-2 font-medium text-sm transition-colors ${
                    activeTab === 'all'
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                  }`}
                >
                  All Exams
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
              {filteredExams.length > 0 ? (
                filteredExams.map((exam) => (
                  <ExamCard key={exam.exam_id} exam={exam} />
                ))
              ) : (
                <div className="col-span-full text-center py-12">
                  <ClipboardList className="w-16 h-16 mx-auto text-gray-300 mb-4" />
                  <p className="text-gray-600">
                    {activeTab === 'upcoming'
                      ? 'No upcoming exams'
                      : activeTab === 'completed'
                      ? 'No completed exams yet'
                      : 'No exams available'}
                  </p>
                </div>
              )}
            </div>
          )}
        </main>
      </div>
    </div>
  );
};

export default StudentExams;