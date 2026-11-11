// Owner: Chorn Daly - manage student list
import { useState, useEffect } from 'react';
import { Plus, Edit, Trash2, Eye } from 'lucide-react';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import DataTable from '@/components/table/DataTable';
import Button from '@/components/ui/Button';
import Modal from '@/components/ui/Modal';
import Input from '@/components/ui/Input';
import { ConfirmModal } from '@/components/ui/Modal';
import apiService from '@/services/api';
import { formatDate } from '@/utils/helpers';
import { validators, validateForm, hasErrors } from '@/utils/validators';

/**
 * Admin Students Management Page
 */
const AdminStudents = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    username: '',
    email: '',
    phone: '',
    gender: '',
    dob: '',
    department: '',
    year_level: '',
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Fetch students
  useEffect(() => {
    fetchStudents();
  }, []);

  const fetchStudents = async () => {
    try {
      setLoading(true);
      const response = await apiService.get('/admin/students');
      if (response.success) {
        setStudents(response.data);
      }
    } catch (error) {
      console.error('Error fetching students:', error);
    } finally {
      setLoading(false);
    }
  };

  // Handle form change
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  // Open add modal
  const handleAdd = () => {
    setSelectedStudent(null);
    setFormData({
      name: '',
      username: '',
      email: '',
      phone: '',
      gender: '',
      dob: '',
      department: '',
      year_level: '',
    });
    setErrors({});
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (student) => {
    setSelectedStudent(student);
    setFormData({
      name: student.name,
      username: student.username,
      email: student.email,
      phone: student.phone,
      gender: student.gender,
      dob: student.dob,
      department: student.department,
      year_level: student.year_level,
    });
    setErrors({});
    setShowModal(true);
  };

  // Handle submit
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Validation
    const rules = {
      name: [(val) => validators.required(val, 'Name')],
      username: [(val) => validators.required(val, 'Username'), (val) => validators.username(val)],
      email: [(val) => validators.required(val, 'Email'), (val) => validators.email(val)],
      phone: [(val) => validators.required(val, 'Phone'), (val) => validators.phone(val)],
      gender: [(val) => validators.required(val, 'Gender')],
      dob: [(val) => validators.required(val, 'Date of birth')],
      department: [(val) => validators.required(val, 'Department')],
      year_level: [(val) => validators.required(val, 'Year level')],
    };

    const validationErrors = validateForm(formData, rules);
    if (hasErrors(validationErrors)) {
      setErrors(validationErrors);
      return;
    }

    try {
      setSubmitting(true);
      if (selectedStudent) {
        // Update student
        await apiService.put(`/admin/students/${selectedStudent.student_id}`, formData);
      } else {
        // Create student
        await apiService.post('/admin/students', formData);
      }
      setShowModal(false);
      fetchStudents();
    } catch (error) {
      console.error('Error saving student:', error);
      setErrors({ submit: error.message });
    } finally {
      setSubmitting(false);
    }
  };

  // Handle delete
  const handleDelete = (student) => {
    setSelectedStudent(student);
    setShowDeleteModal(true);
  };

  const confirmDelete = async () => {
    try {
      setSubmitting(true);
      await apiService.delete(`/admin/students/${selectedStudent.student_id}`);
      setShowDeleteModal(false);
      fetchStudents();
    } catch (error) {
      console.error('Error deleting student:', error);
    } finally {
      setSubmitting(false);
    }
  };

  // Table columns
  const columns = [
    { key: 'student_id', label: 'ID' },
    { key: 'name', label: 'Name' },
    { key: 'username', label: 'Username' },
    { key: 'email', label: 'Email' },
    { key: 'department', label: 'Department' },
    { key: 'year_level', label: 'Year' },
    {
      key: 'actions',
      label: 'Actions',
      sortable: false,
      render: (_, student) => (
        <div className="flex gap-2">
          <button
            onClick={() => handleEdit(student)}
            className="p-2 text-primary-600 hover:bg-primary-50 rounded"
          >
            <Edit className="w-4 h-4" />
          </button>
          <button
            onClick={() => handleDelete(student)}
            className="p-2 text-danger-600 hover:bg-danger-50 rounded"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      ),
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      <div className="lg:pl-64 pt-16">
        <main className="p-6">
          {/* Page header */}
          <div className="flex items-center justify-between mb-8">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Students</h1>
              <p className="text-gray-600 mt-2">Manage student records</p>
            </div>
            <Button variant="primary" onClick={handleAdd}>
              <Plus className="w-5 h-5 mr-2" />
              Add Student
            </Button>
          </div>

          {/* Students table */}
          <div className="bg-white rounded-lg shadow">
            <DataTable
              columns={columns}
              data={students}
              loading={loading}
              searchable={true}
              sortable={true}
            />
          </div>
        </main>
      </div>

      {/* Add/Edit Modal */}
      <Modal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        title={selectedStudent ? 'Edit Student' : 'Add Student'}
        size="lg"
        footer={
          <>
            <Button variant="ghost" onClick={() => setShowModal(false)}>
              Cancel
            </Button>
            <Button variant="primary" onClick={handleSubmit} loading={submitting}>
              {selectedStudent ? 'Update' : 'Create'}
            </Button>
          </>
        }
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          {errors.submit && (
            <div className="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-lg">
              {errors.submit}
            </div>
          )}

          <div className="grid md:grid-cols-2 gap-4">
            <Input
              label="Full Name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              error={errors.name}
              required
            />
            <Input
              label="Username"
              name="username"
              value={formData.username}
              onChange={handleChange}
              error={errors.username}
              required
            />
            <Input
              label="Email"
              name="email"
              type="email"
              value={formData.email}
              onChange={handleChange}
              error={errors.email}
              required
            />
            <Input
              label="Phone"
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              error={errors.phone}
              required
            />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Gender <span className="text-danger-500">*</span>
              </label>
              <select
                name="gender"
                value={formData.gender}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                required
              >
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
              {errors.gender && <p className="mt-1 text-sm text-danger-500">{errors.gender}</p>}
            </div>
            <Input
              label="Date of Birth"
              name="dob"
              type="date"
              value={formData.dob}
              onChange={handleChange}
              error={errors.dob}
              required
            />
            <Input
              label="Department"
              name="department"
              value={formData.department}
              onChange={handleChange}
              error={errors.department}
              required
            />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Year Level <span className="text-danger-500">*</span>
              </label>
              <select
                name="year_level"
                value={formData.year_level}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                required
              >
                <option value="">Select Year</option>
                <option value="1">Year 1</option>
                <option value="2">Year 2</option>
                <option value="3">Year 3</option>
                <option value="4">Year 4</option>
              </select>
              {errors.year_level && <p className="mt-1 text-sm text-danger-500">{errors.year_level}</p>}
            </div>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <ConfirmModal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={confirmDelete}
        title="Delete Student"
        message={`Are you sure you want to delete ${selectedStudent?.name}? This action cannot be undone.`}
        confirmText="Delete"
        variant="danger"
        loading={submitting}
      />
    </div>
  );
};

export default AdminStudents;