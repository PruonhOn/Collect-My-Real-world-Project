import React, { useState, useRef } from "react";
import { Users, BookOpen, BarChart3, Plus, Eye, Award, ArrowLeft, X, Search, FileUp, GraduationCap, Bell, LogOut, Printer, Edit, Trash2 } from "lucide-react";

// Add/Edit User Modal Component
function UserModal({ isOpen, onClose, onSave, editUser, defaultRole = "Admin" }) {
  const [formData, setFormData] = useState(editUser || {
    name: "",
    email: "",
    role: "Admin",
    status: "Active"
  });

  React.useEffect(() => {
    if (editUser) {
      setFormData(editUser);
    } else {
      setFormData({
        name: "",
        email: "",
        role: defaultRole,
        status: "Active"
      });
    }
  }, [editUser, isOpen, defaultRole]);

  if (!isOpen) return null;

  const handleSubmit = () => {
    if (!formData.name || !formData.email) {
      alert("Please fill in name and email");
      return;
    }
    onSave(formData);
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-2xl">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-bold text-gray-800">Add / Edit User</h2>
          <button onClick={onClose} className="text-gray-600 hover:text-gray-800">
            <X className="w-6 h-6" />
          </button>
        </div>

        <div className="space-y-4">
          <div>
            <label className="block mb-2 font-semibold text-gray-700">Name</label>
            <input
              type="text"
              value={formData.name}
              onChange={(e) => setFormData({...formData, name: e.target.value})}
              className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              placeholder="Enter name"
            />
          </div>

          <div>
            <label className="block mb-2 font-semibold text-gray-700">Email</label>
            <input
              type="email"
              value={formData.email}
              onChange={(e) => setFormData({...formData, email: e.target.value})}
              className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              placeholder="Enter email"
            />
          </div>

          <div>
            <label className="block mb-2 font-semibold text-gray-700">Role</label>
            <select
              value={formData.role}
              onChange={(e) => setFormData({...formData, role: e.target.value})}
              className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
            >
              <option>Admin</option>
              <option>Lecturer</option>
              <option>Student</option>
            </select>
          </div>

          <div>
            <label className="block mb-2 font-semibold text-gray-700">Status</label>
            <select
              value={formData.status}
              onChange={(e) => setFormData({...formData, status: e.target.value})}
              className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
            >
              <option>Active</option>
              <option>Inactive</option>
            </select>
          </div>

          <div className="flex gap-3 pt-4">
            <button
              onClick={onClose}
              className="flex-1 px-6 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition"
            >
              Close
            </button>
            <button
              onClick={handleSubmit}
              className="flex-1 px-6 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition"
            >
              Save
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

// Add Student Form Component
function AddStudentForm({ onBack, onAddStudent }) {
  const [formData, setFormData] = useState({
    year: "",
    department: "",
    firstName: "",
    lastName: "",
    studentId: "",
    gender: "Female",
    dateOfBirth: "",
    address: "",
    email: ""
  });

  const handleSubmit = () => {
    if (!formData.firstName || !formData.studentId) {
      alert("Please fill in required fields");
      return;
    }
    onAddStudent(formData);
    setFormData({
      year: "",
      department: "",
      firstName: "",
      lastName: "",
      studentId: "",
      gender: "Female",
      dateOfBirth: "",
      address: "",
      email: ""
    });
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-8">
      <div className="max-w-3xl mx-auto">
        <button
          onClick={onBack}
          className="mb-6 flex items-center gap-2 text-gray-600 hover:text-gray-800 font-semibold"
        >
          <ArrowLeft className="w-5 h-5" />
          Back to Dashboard
        </button>

        <div className="bg-white rounded-2xl shadow-xl p-8">
          <h2 className="text-3xl font-bold text-gray-800 mb-8 border-b pb-4">Add Student</h2>

          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-lg font-semibold text-gray-700 mb-2">Select Year</label>
                <select
                  value={formData.year}
                  onChange={(e) => setFormData({...formData, year: e.target.value})}
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                  <option value="">Choose year...</option>
                  <option value="Year 1">Year 1</option>
                  <option value="Year 2">Year 2</option>
                  <option value="Year 3">Year 3</option>
                  <option value="Year 4">Year 4</option>
                </select>
              </div>

              <div>
                <label className="block text-lg font-semibold text-gray-700 mb-2">Select Department</label>
                <select
                  value={formData.department}
                  onChange={(e) => setFormData({...formData, department: e.target.value})}
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                  <option value="">Choose department...</option>
                  <option value="Information Technology">Faculty of Information Technology</option>
                  <option value="English Literature">Department of English Literature</option>
                  <option value="Agronomy">Department of Agronomy</option>
                  <option value="Social Work">Department of Social Work</option>
                  <option value="Tourism Management">Department of Tourism Management</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">Student Name</label>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input
                  type="text"
                  placeholder="First Name"
                  value={formData.firstName}
                  onChange={(e) => setFormData({...formData, firstName: e.target.value})}
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                />
                <input
                  type="text"
                  placeholder="Last Name"
                  value={formData.lastName}
                  onChange={(e) => setFormData({...formData, lastName: e.target.value})}
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                />
              </div>
            </div>

            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">Student ID</label>
              <input
                type="text"
                placeholder="Enter student ID"
                value={formData.studentId}
                onChange={(e) => setFormData({...formData, studentId: e.target.value})}
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">Gender</label>
              <div className="flex gap-6">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    name="gender"
                    value="Female"
                    checked={formData.gender === "Female"}
                    onChange={(e) => setFormData({...formData, gender: e.target.value})}
                    className="w-5 h-5 text-blue-600"
                  />
                  <span className="text-lg">Female</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    name="gender"
                    value="Male"
                    checked={formData.gender === "Male"}
                    onChange={(e) => setFormData({...formData, gender: e.target.value})}
                    className="w-5 h-5 text-blue-600"
                  />
                  <span className="text-lg">Male</span>
                </label>
              </div>
            </div>

            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">Date of Birth</label>
              <input
                type="date"
                value={formData.dateOfBirth}
                onChange={(e) => setFormData({...formData, dateOfBirth: e.target.value})}
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">Student Address</label>
              <input
                type="text"
                placeholder="Enter student address"
                value={formData.address}
                onChange={(e) => setFormData({...formData, address: e.target.value})}
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">Email</label>
              <input
                type="email"
                placeholder="Enter email address"
                value={formData.email}
                onChange={(e) => setFormData({...formData, email: e.target.value})}
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            <button
              onClick={handleSubmit}
              className="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-4 rounded-lg font-bold text-lg hover:from-blue-600 hover:to-blue-700 transition flex items-center justify-center gap-2 shadow-lg"
            >
              <Plus className="w-6 h-6" />
              Add Student
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

// View Students Component
function ViewStudents({ students, onBack }) {
  return (
    <div className="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-8">
      <div className="max-w-6xl mx-auto">
        <button
          onClick={onBack}
          className="mb-6 flex items-center gap-2 text-gray-600 hover:text-gray-800 font-semibold"
        >
          <ArrowLeft className="w-5 h-5" />
          Back to Dashboard
        </button>

        <div className="bg-white rounded-2xl shadow-xl p-8">
          <h2 className="text-3xl font-bold text-gray-800 mb-8">Student List</h2>

          {students.length === 0 ? (
            <div className="text-center py-12 text-gray-500">
              <Users className="w-16 h-16 mx-auto mb-4 opacity-50" />
              <p className="text-xl">No students added yet</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="bg-gray-100 border-b-2 border-gray-300">
                    <th className="p-4 text-left font-bold">Student ID</th>
                    <th className="p-4 text-left font-bold">Name</th>
                    <th className="p-4 text-left font-bold">Department</th>
                    <th className="p-4 text-left font-bold">Year</th>
                    <th className="p-4 text-left font-bold">Gender</th>
                    <th className="p-4 text-left font-bold">Email</th>
                  </tr>
                </thead>
                <tbody>
                  {students.map((student, index) => (
                    <tr key={index} className="border-b hover:bg-gray-50">
                      <td className="p-4">{student.studentId}</td>
                      <td className="p-4">{student.firstName} {student.lastName}</td>
                      <td className="p-4">{student.department}</td>
                      <td className="p-4">{student.year}</td>
                      <td className="p-4">{student.gender}</td>
                      <td className="p-4">{student.email}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// Announcements Section Component
function AnnouncementsSection() {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showPrintModal, setShowPrintModal] = useState(false);
  const [filterFrom, setFilterFrom] = useState("");
  const [filterTo, setFilterTo] = useState("");
  const [filterDepartment, setFilterDepartment] = useState("");
  const [filterStatus, setFilterStatus] = useState("");
  const [announcements, setAnnouncements] = useState([
    { id: 1, title: "ពិធីចូលថ្មី", date: "2025-11-01", department: "IT", description: "ពិធីចូលថ្មី សំរាប់ និស្សិតថ្មី", status: "Published" },
    { id: 2, title: "ការប្រឡងពាក់កណ្តាលឆ្នាំ", date: "2025-11-15", department: "All", description: "កាលវិភាគប្រឡង Midterm", status: "Published" }
  ]);

  const [newAnnouncement, setNewAnnouncement] = useState({
    title: "",
    category: "ទូរស័ព្ទ",
    type: "New",
    content: "",
    sendToStudents: true
  });

  const handleCreateAnnouncement = () => {
    if (!newAnnouncement.title) {
      alert("សូមបំពេញចំណងជើង");
      return;
    }
    const announcement = {
      id: announcements.length + 1,
      title: newAnnouncement.title,
      date: new Date().toISOString().split('T')[0],
      department: "IT",
      description: newAnnouncement.content,
      status: "Published"
    };
    setAnnouncements([...announcements, announcement]);
    setNewAnnouncement({ title: "", category: "ទូរស័ព្ទ", type: "New", content: "", sendToStudents: true });
    setShowCreateModal(false);
    alert("បានបង្កើតសេចក្តីប្រកាសជោគជ័យ!");
  };

  const handleDelete = (id) => {
    if (window.confirm("តើអ្នកប្រាកដជាចង់លុបសេចក្តីប្រកាសនេះមែនទេ?")) {
      setAnnouncements(announcements.filter(a => a.id !== id));
    }
  };

  const handleEdit = (announcement) => {
    setNewAnnouncement({
      title: announcement.title,
      category: "ទូរស័ព្ទ",
      type: "New",
      content: announcement.description,
      sendToStudents: true
    });
    setShowCreateModal(true);
  };

  const filteredAnnouncements = announcements.filter(a => {
    const matchDept = filterDepartment ? a.department === filterDepartment : true;
    const matchStatus = filterStatus ? a.status === filterStatus : true;
    return matchDept && matchStatus;
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-[#f06292] rounded-xl p-4 flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white">Announcement</h2>
        <div className="flex gap-3">
          <button 
            onClick={() => setShowCreateModal(true)}
            className="px-4 py-2 bg-cyan-400 text-white rounded-lg font-semibold hover:bg-cyan-500 flex items-center gap-2"
          >
            <Plus className="w-4 h-4" />
            create announce
          </button>
          <button 
            onClick={() => setShowPrintModal(true)}
            className="px-4 py-2 bg-white text-gray-700 rounded-lg font-semibold hover:bg-gray-100 flex items-center gap-2"
          >
            <Printer className="w-4 h-4" />
            print
          </button>
        </div>
      </div>

      {/* Filter Row */}
      <div className="bg-[#67d6f8] rounded-xl p-4">
        <div className="grid grid-cols-5 gap-3 items-end">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">from</label>
            <input 
              type="date" 
              value={filterFrom}
              onChange={(e) => setFilterFrom(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"
              placeholder="mm/dd/yyyy"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">from</label>
            <input 
              type="date"
              value={filterTo}
              onChange={(e) => setFilterTo(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"
              placeholder="mm/dd/yyyy"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">department</label>
            <select 
              value={filterDepartment}
              onChange={(e) => setFilterDepartment(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm bg-cyan-200"
            >
              <option value="">all dept</option>
              <option value="IT">IT</option>
              <option value="Business">Business</option>
              <option value="All">All</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">status</label>
            <select 
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm bg-green-200"
            >
              <option value="">active</option>
              <option value="Published">Published</option>
              <option value="Draft">Draft</option>
            </select>
          </div>
          <button className="px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600">
            Export
          </button>
        </div>
      </div>

      {/* Announcements List */}
      <div className="bg-[#67d6f8] rounded-xl p-4">
        <h3 className="text-lg font-bold text-gray-800 mb-4">list announce</h3>
        
        {/* Table Header */}
        <div className="grid grid-cols-6 gap-2 text-sm font-bold text-gray-700 border-b border-gray-400 pb-2 mb-2">
          <span>#</span>
          <span>ចំណងជើង</span>
          <span>ថ្ងៃខែឆ្នាំ</span>
          <span>ពិពរណា</span>
          <span>ស្ថានភាព</span>
          <span>សកម្មភាព</span>
        </div>

        {/* Table Body */}
        {filteredAnnouncements.length === 0 ? (
          <div className="text-center py-8 text-gray-600">មិនមានសេចក្តីប្រកាស</div>
        ) : (
          filteredAnnouncements.map((announcement, idx) => (
            <div key={announcement.id} className="grid grid-cols-6 gap-2 text-sm py-3 border-b border-gray-300 items-center">
              <span>{idx + 1}</span>
              <span className="font-medium">{announcement.title}</span>
              <span>{announcement.date}</span>
              <span className="truncate">{announcement.description}</span>
              <span>
                <span className={`px-2 py-1 rounded text-xs font-semibold ${
                  announcement.status === 'Published' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white'
                }`}>
                  {announcement.status}
                </span>
              </span>
              <div className="flex gap-1">
                <button 
                  onClick={() => handleEdit(announcement)}
                  className="px-2 py-1 bg-blue-500 text-white rounded text-xs font-semibold hover:bg-blue-600"
                >
                  view
                </button>
                <button 
                  onClick={() => handleEdit(announcement)}
                  className="px-2 py-1 bg-yellow-500 text-white rounded text-xs font-semibold hover:bg-yellow-600"
                >
                  edit
                </button>
                <button 
                  onClick={() => handleDelete(announcement.id)}
                  className="px-2 py-1 bg-red-500 text-white rounded text-xs font-semibold hover:bg-red-600"
                >
                  delete
                </button>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Create Announcement Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-[#f8bbd9] rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-xl font-bold text-gray-800">create new announcement</h3>
              <button onClick={() => setShowCreateModal(false)} className="text-gray-600 hover:text-gray-800">
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">ចំណងជើង</label>
                <input 
                  type="text"
                  value={newAnnouncement.title}
                  onChange={(e) => setNewAnnouncement({...newAnnouncement, title: e.target.value})}
                  className="w-full px-3 py-2 rounded-lg border border-gray-300"
                  placeholder="123"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">វទ្វ</label>
                  <select 
                    value={newAnnouncement.category}
                    onChange={(e) => setNewAnnouncement({...newAnnouncement, category: e.target.value})}
                    className="w-full px-3 py-2 rounded-lg border border-gray-300 bg-cyan-200"
                  >
                    <option>ទូរស័ព្ទ</option>
                    <option>សំខាន់</option>
                    <option>ទូទៅ</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">ប្រភេទ</label>
                  <select 
                    value={newAnnouncement.type}
                    onChange={(e) => setNewAnnouncement({...newAnnouncement, type: e.target.value})}
                    className="w-full px-3 py-2 rounded-lg border border-gray-300"
                  >
                    <option>New</option>
                    <option>Update</option>
                    <option>Important</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">មាតិកា</label>
                <textarea 
                  value={newAnnouncement.content}
                  onChange={(e) => setNewAnnouncement({...newAnnouncement, content: e.target.value})}
                  className="w-full px-3 py-2 rounded-lg border border-gray-300 h-24"
                  placeholder="សរសេរមាតិកា..."
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">ក្រុមទូទាត់ (បញ្ចូលទៅ)</label>
                <div className="flex gap-4">
                  <span className="text-sm text-gray-600">Class: 01c</span>
                  <span className="text-sm text-gray-600">Dep: ហាត្រ</span>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <input 
                  type="checkbox"
                  checked={newAnnouncement.sendToStudents}
                  onChange={(e) => setNewAnnouncement({...newAnnouncement, sendToStudents: e.target.checked})}
                  className="w-4 h-4"
                />
                <label className="text-sm font-semibold text-gray-700">ផ្ញើការផ្តល់សិទ្ធិមើលកើសិស្ស/គ្រូ</label>
              </div>

              <div className="flex gap-3 pt-4">
                <button 
                  onClick={() => setShowCreateModal(false)}
                  className="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600"
                >
                  ថ្ល
                </button>
                <button 
                  onClick={handleCreateAnnouncement}
                  className="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600"
                >
                  រក្សាទុក
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Print Modal */}
      {showPrintModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl p-6 max-w-lg w-full mx-4 shadow-2xl">
            <div className="flex justify-between items-center mb-4">
              <div className="flex items-center gap-2">
                <span className="text-2xl">🎓</span>
                <span className="font-bold text-gray-800">SMS ADMIN SPI</span>
              </div>
              <button onClick={() => setShowPrintModal(false)} className="text-gray-600 hover:text-gray-800">
                <X className="w-6 h-6" />
              </button>
            </div>

            <h3 className="text-lg font-bold text-gray-800 mb-4">Print announcement list</h3>

            {/* Print Table Header */}
            <div className="grid grid-cols-5 gap-2 text-xs font-bold text-gray-700 border-b border-gray-300 pb-2 mb-2">
              <span>#</span>
              <span>ចំណងជើង</span>
              <span>ថ្ងៃខែឆ្នាំ</span>
              <span>ស្ថានភាព</span>
              <span>សកម្មភាព</span>
            </div>

            {/* Print Table Body */}
            {announcements.map((announcement, idx) => (
              <div key={announcement.id} className="grid grid-cols-5 gap-2 text-xs py-2 border-b border-gray-200 items-center">
                <span>{idx + 1}</span>
                <span className="font-medium">{announcement.title}</span>
                <span>{announcement.date}</span>
                <span>
                  <span className={`px-2 py-0.5 rounded text-xs font-semibold ${
                    announcement.status === 'Published' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white'
                  }`}>
                    {announcement.status}
                  </span>
                </span>
                <div className="flex gap-1">
                  <button className="px-1 py-0.5 bg-green-500 text-white rounded text-xs">view</button>
                  <button className="px-1 py-0.5 bg-red-500 text-white rounded text-xs">delete</button>
                </div>
              </div>
            ))}

            <div className="flex gap-3 mt-6">
              <button 
                onClick={() => setShowPrintModal(false)}
                className="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-400"
              >
                Archive Selected
              </button>
              <button 
                onClick={() => {
                  window.print();
                  setShowPrintModal(false);
                }}
                className="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600"
              >
                Send Notification
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// Dashboard Component
function Dashboard({ onLogout, onNavigate, studentCount, activeMenu, setActiveMenu }) {
  const [showUserModal, setShowUserModal] = useState(false);
  const [editUser, setEditUser] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [activeUserTab, setActiveUserTab] = useState("Admin");
  const [modalDefaultRole, setModalDefaultRole] = useState("Admin");
  const [users, setUsers] = useState([
    { id: 1, name: "Admin Mary", email: "admin@sms.com", role: "Admin", status: "Active" },
    { id: 2, name: "Lecturer Dara", email: "dara@sms.com", role: "Lecturer", status: "Active" },
    { id: 3, name: "Student Lina", email: "lina@sms.com", role: "Student", status: "Inactive" }
  ]);
  const fileInputRef = useRef(null);

  const handleSaveUser = (userData) => {
    if (editUser && editUser.id != null) {
      // Update existing user
      setUsers(users.map(u => u.id === editUser.id ? { ...userData, id: editUser.id } : u));
      setEditUser(null);
    } else {
      // Add new user
      const newUser = { ...userData, id: users.length + 1 };
      setUsers([...users, newUser]);
    }
    setShowUserModal(false);
  };

  const handleEditUser = (user) => {
    setEditUser(user);
    setModalDefaultRole(user.role);
    setShowUserModal(true);
  };

  const handleDeleteUser = (userId) => {
    if (window.confirm("Are you sure you want to delete this user?")) {
      setUsers(users.filter(u => u.id !== userId));
    }
  };

  const handleCloseModal = () => {
    setShowUserModal(false);
    setEditUser(null);
  };

  const filteredUsers = users
    .filter(user => user.role === activeUserTab)
    .filter(user =>
      user.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

  const totalStudents = users.filter(user => user.role === "Student").length || 0;
  const totalLecturers = users.filter(user => user.role === "Lecturer").length || 0;

  const handleImportClick = () => {
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
      fileInputRef.current.click();
    }
  };

  const handleImportCSV = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      const text = e.target?.result;
      if (typeof text !== "string") return;

      const lines = text
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

      if (lines.length === 0) {
        alert("CSV file is empty");
        return;
      }

      const [, ...dataLines] = lines; // skip header
      const nextIdStart = users.length + 1;
      const importedUsers = dataLines.map((line, idx) => {
        const [name = "", email = "", role = "Student", status = "Active"] = line.split(",").map((item) => item.trim());
        return {
          id: nextIdStart + idx,
          name: name || `Imported User ${idx + 1}`,
          email: email || `imported${idx + 1}@sms.com`,
          role: role || "Student",
          status: status || "Active",
        };
      });

      if (importedUsers.length === 0) {
        alert("No valid rows found in CSV");
        return;
      }

      setUsers((prev) => [...prev, ...importedUsers]);
      alert(`Imported ${importedUsers.length} users successfully`);
    };

    reader.readAsText(file);
  };

  return (
    <div className="flex min-h-screen bg-gray-100">
      {/* Sidebar */}
      <div className="w-56 bg-gradient-to-b from-cyan-400 to-cyan-500 p-4 flex flex-col shadow-xl">
        <div className="bg-cyan-300 rounded-xl p-6 mb-6 text-center">
          <div className="text-4xl mb-2">🎓</div>
          <div className="text-sm font-bold text-gray-800">SMS ADMIN SPI</div>
        </div>

        <nav className="space-y-2 text-sm text-white font-medium flex-1">
          <div 
            onClick={() => setActiveMenu('dashboard')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer shadow-lg transition ${
              activeMenu === 'dashboard' ? 'bg-red-500' : 'bg-cyan-600 hover:bg-cyan-700'
            }`}
          >
            <span className="text-xl">📊</span> 
            <span>Admin Dashboard</span>
          </div>
          <div 
            onClick={() => setActiveMenu('user')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'user' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">👤</span> 
            <span>User Management</span>
          </div>
          <div 
            onClick={() => setActiveMenu('routine')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'routine' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">🎯</span> 
            <span>Student MGT</span>
          </div>
          <div 
            onClick={() => setActiveMenu('attendance')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'attendance' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">📅</span> 
            <span>Attendance MGT</span>
          </div>
          <div 
            onClick={() => setActiveMenu('courses')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'courses' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">🎓</span> 
            <span>Course Management</span>
          </div>
          <div 
            onClick={() => setActiveMenu('grade')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'grade' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">📊</span> 
            <span>Grade</span>
          </div>
          <div 
            onClick={() => setActiveMenu('reports')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'reports' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">📈</span> 
            <span>Reports & Analytics</span>
          </div>
          <div 
            onClick={() => setActiveMenu('announcements')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'announcements' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">📢</span> 
            <span>Announcements</span>
          </div>
          <div 
            onClick={() => setActiveMenu('settings')}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer transition ${
              activeMenu === 'settings' ? 'bg-red-500 shadow-lg' : 'hover:bg-cyan-600'
            }`}
          >
            <span className="text-xl">⚙️</span> 
            <span>System Settings</span>
          </div>
        </nav>
      </div>

      {/* Main Content */}
      <div className="flex-1 p-6 bg-pink-100">
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold text-gray-800">
            {activeMenu === 'dashboard' && 'Admin Dashboard'}
            {activeMenu === 'user' && 'User Management'}
            {activeMenu === 'routine' && 'Routine Management'}
            {activeMenu === 'attendance' && 'Attendance Management'}
            {activeMenu === 'courses' && 'Course Management'}
            {activeMenu === 'grade' && 'Grade Management'}
            {activeMenu === 'reports' && 'Reports & Analytics'}
            {activeMenu === 'announcements' && 'Announcements'}
            {activeMenu === 'settings' && 'System Settings'}
          </h1>
          <button
            onClick={onLogout}
            className="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded font-semibold text-sm"
          >
            Logout
          </button>
        </div>

        {/* Dashboard Content */}
        {activeMenu === 'dashboard' && (
          <>
            {/* Stats Cards */}
            <div className="grid grid-cols-3 gap-4 mb-6">
              <div className="bg-white rounded-lg p-4 shadow">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-gray-600 text-sm font-semibold">Total Students</div>
                    <div className="text-3xl font-bold text-gray-800">{studentCount}</div>
                  </div>
                  <div className="bg-pink-100 p-3 rounded-lg">
                    <BookOpen className="w-6 h-6 text-pink-500" />
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg p-4 shadow">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-gray-600 text-sm font-semibold">Total Instructors</div>
                    <div className="text-3xl font-bold text-gray-800">25</div>
                  </div>
                  <div className="bg-pink-100 p-3 rounded-lg">
                    <Users className="w-6 h-6 text-pink-500" />
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg p-4 shadow">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-gray-600 text-sm font-semibold">Total Subjects</div>
                    <div className="text-3xl font-bold text-gray-800">12</div>
                  </div>
                  <div className="bg-blue-100 p-3 rounded-lg">
                    <BarChart3 className="w-6 h-6 text-blue-500" />
                  </div>
                </div>
              </div>
            </div>

            {/* Action Buttons */}
            <div className="grid grid-cols-3 gap-4 mb-6">
              <button 
                onClick={() => onNavigate('add')}
                className="bg-pink-300 hover:bg-pink-400 text-gray-800 px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition"
              >
                <Plus className="w-5 h-5" /> Add Student
              </button>
              <button 
                onClick={() => onNavigate('view')}
                className="bg-pink-300 hover:bg-pink-400 text-gray-800 px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition"
              >
                <Eye className="w-5 h-5" /> View Students
              </button>
              <button className="bg-pink-300 hover:bg-pink-400 text-gray-800 px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition">
                <Award className="w-5 h-5" /> Grade
              </button>
            </div>

            {/* Main Content Area */}
            <div className="grid grid-cols-3 gap-4">
              {/* Calendar Section */}
              <div className="bg-white rounded-lg shadow p-4 col-span-2">
                <div className="bg-blue-600 text-white p-3 rounded mb-4">
                  <h3 className="font-semibold">Calendar</h3>
                </div>
                <div className="bg-gray-50 p-3 rounded mb-4">
                  <select className="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    <option>Register ID</option>
                  </select>
                </div>
                <div className="bg-blue-400 text-white p-3 rounded mb-4 flex items-center justify-between">
                  <span className="font-semibold">Nov 2025</span>
                  <span className="text-sm">›</span>
                </div>
                <div className="grid grid-cols-7 gap-1">
                  {["S", "M", "T", "W", "T", "F", "S"].map((day, i) => (
                    <div key={i} className="text-center text-xs font-semibold text-gray-500 p-2">
                      {day}
                    </div>
                  ))}
                  {[...Array(35)].map((_, i) => (
                    <div
                      key={i}
                      className="text-center text-xs p-2 border border-gray-200 rounded hover:bg-blue-50"
                    >
                      {i % 30 !== 0 ? (i % 30) + 1 : ""}
                    </div>
                  ))}
                </div>
              </div>

              {/* Right Panel */}
              <div className="flex flex-col gap-4">
                <div className="bg-pink-300 rounded-lg shadow p-6 text-center">
                  <div className="bg-gray-200 w-16 h-16 rounded-lg mx-auto mb-4 flex items-center justify-center text-gray-400">
                    ⚪
                  </div>
                  <h3 className="text-lg font-semibold text-gray-800">Inactive</h3>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                  <h3 className="font-semibold text-gray-800 mb-2">Recent Time</h3>
                  <p className="text-xs text-gray-500 mb-4">From 01 Dec, 2025</p>
                  <div className="flex items-center justify-center h-32">
                    <div className="w-24 h-24 rounded-full bg-gradient-to-r from-pink-400 via-purple-500 to-blue-500 flex items-center justify-center">
                      <svg viewBox="0 0 100 100" className="w-full h-full">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" strokeWidth="3" strokeDasharray="141.3 282.6" transform="rotate(-90 50 50)" opacity="0.3" />
                        <circle cx="50" cy="50" r="45" fill="none" stroke="url(#grad)" strokeWidth="3" strokeDasharray="141.3 282.6" strokeLinecap="round" transform="rotate(-90 50 50)" />
                        <defs>
                          <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stopColor="#ec4899" />
                            <stop offset="50%" stopColor="#a855f7" />
                            <stop offset="100%" stopColor="#3b82f6" />
                          </linearGradient>
                        </defs>
                      </svg>
                    </div>
                  </div>
                  <div className="flex justify-around text-xs mt-4 pt-4 border-t border-gray-200">
                    <div className="text-center">
                      <div className="font-semibold text-gray-800">0</div>
                      <div className="text-gray-500">Product</div>
                    </div>
                    <div className="text-center">
                      <div className="font-semibold text-gray-800">0</div>
                      <div className="text-gray-500">Category</div>
                    </div>
                    <div className="text-center">
                      <div className="font-semibold text-gray-800">0</div>
                      <div className="text-gray-500">Growth</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </>
        )}

        {/* User Management */}
        {activeMenu === 'user' && (
          <div className="space-y-6">
            {/* Summary Cards */}
            <div className="grid grid-cols-4 gap-4">
              <div className="bg-white rounded-lg p-4 shadow-lg">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-sm text-gray-600 mb-1">Total Students</div>
                    <div className="text-2xl font-bold text-gray-800">{totalStudents}</div>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <BookOpen className="w-6 h-6 text-blue-500" />
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg p-4 shadow-lg">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-sm text-gray-600 mb-1">Total Lecturers</div>
                    <div className="text-2xl font-bold text-gray-800">{totalLecturers}</div>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center">
                    <Users className="w-6 h-6 text-pink-500" />
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg p-4 shadow-lg">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-sm text-gray-600 mb-1">Active Courses</div>
                    <div className="text-2xl font-bold text-gray-800">10</div>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <GraduationCap className="w-6 h-6 text-purple-500" />
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg p-4 shadow-lg">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-sm text-gray-600 mb-1">Pending Announcements</div>
                    <div className="text-2xl font-bold text-gray-800">4</div>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
                    <Bell className="w-6 h-6 text-orange-500" />
                  </div>
                </div>
              </div>
            </div>

            {/* User Management Section */}
            <div className="bg-white rounded-xl p-6 shadow-xl">
              <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
                <h3 className="text-2xl font-bold text-gray-800">User Management</h3>
                <button
                  onClick={onLogout}
                  className="px-4 py-2 bg-gray-800 text-white rounded-lg font-semibold hover:bg-gray-900 flex items-center gap-2 transition"
                >
                  <LogOut className="w-5 h-5" />
                  Logout
                </button>
              </div>

              {/* Role Tabs */}
              <div className="flex flex-wrap justify-center gap-4 mb-6">
                {["Admin", "Lecturer", "Student"].map((role) => (
                  <button
                    key={role}
                    onClick={() => setActiveUserTab(role)}
                    className={`px-6 py-3 rounded-2xl font-semibold min-w-[140px] transition ${
                      activeUserTab === role
                        ? "bg-gray-800 text-white shadow-lg"
                        : "bg-gray-200 text-gray-600 hover:bg-gray-300"
                    }`}
                  >
                    {role}
                  </button>
                ))}
              </div>
              
              {/* Action Buttons and Search */}
              <div className="flex flex-wrap items-center gap-3 mb-6">
                <button 
                  onClick={() => {
                    setEditUser(null);
                    setModalDefaultRole(activeUserTab);
                    setShowUserModal(true);
                  }}
                  className="px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 flex items-center gap-2 transition"
                >
                  <Plus className="w-5 h-5" />
                  Add new
                </button>
                <button
                  onClick={handleImportClick}
                  className="px-4 py-2 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 flex items-center gap-2 transition"
                >
                  <FileUp className="w-5 h-5" />
                  Import csv
                </button>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept=".csv"
                  className="hidden"
                  onChange={handleImportCSV}
                />
                <div className="flex-1 min-w-[200px]">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input
                      type="text"
                      placeholder="Search by user name"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="w-full pl-10 pr-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                    />
                  </div>
                </div>
              </div>

              {/* User Table */}
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b-2 border-gray-300 bg-gray-100">
                      <th className="p-4 text-left font-bold text-gray-800">#</th>
                      <th className="p-4 text-left font-bold text-gray-800">Name</th>
                      <th className="p-4 text-left font-bold text-gray-800">Email</th>
                      <th className="p-4 text-left font-bold text-gray-800">Role</th>
                      <th className="p-4 text-left font-bold text-gray-800">Status</th>
                      <th className="p-4 text-left font-bold text-gray-800">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.length === 0 ? (
                      <tr>
                        <td colSpan="6" className="p-8 text-center text-gray-500">
                          No users found
                        </td>
                      </tr>
                    ) : (
                      filteredUsers.map((user) => (
                        <tr key={user.id} className="border-b hover:bg-gray-50">
                          <td className="p-4">{user.id}</td>
                          <td className="p-4 font-medium">{user.name}</td>
                          <td className="p-4">{user.email}</td>
                          <td className="p-4">
                            <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                              {user.role}
                            </span>
                          </td>
                          <td className="p-4">
                            <span className={`px-3 py-1 rounded-full text-sm font-semibold ${
                              user.status === 'Active' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-red-100 text-red-800'
                            }`}>
                              {user.status}
                            </span>
                          </td>
                          <td className="p-4">
                            <div className="flex gap-2">
                              <button 
                                onClick={() => handleEditUser(user)}
                                className="px-3 py-1 bg-blue-500 text-white rounded font-semibold hover:bg-blue-600 transition"
                              >
                                Edit
                              </button>
                              <button 
                                onClick={() => handleDeleteUser(user.id)}
                                className="px-3 py-1 bg-red-500 text-white rounded font-semibold hover:bg-red-600 transition"
                              >
                                Delete
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            <UserModal 
              isOpen={showUserModal} 
              onClose={handleCloseModal} 
              onSave={handleSaveUser}
              editUser={editUser}
              defaultRole={modalDefaultRole}
            />
          </div>
        )}

        {/* Student Management */}
        {activeMenu === 'routine' && (
          <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="text-center py-12">
            
              <h2 className="text-2xl font-bold text-gray-800 mb-2">Comming Soon</h2>
            
            </div>
          </div>
        )}

        {/* Attendance Management */}
        {activeMenu === 'attendance' && (
          <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="text-center py-12">
             
              <h2 className="text-2xl font-bold text-gray-800 mb-2">Comming Soon</h2>
           
            </div>
          </div>
        )}

        {/* Course Management */}
        {activeMenu === 'courses' && (
          <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="text-center py-12">
              <div className="text-6xl mb-4">🎓</div>
              <h2 className="text-2xl font-bold text-gray-800 mb-2">Course Management</h2>
              <p className="text-gray-600">Add, edit, and manage courses and subjects</p>
            </div>
          </div>
        )}

        {/* Grade Management */}
        {activeMenu === 'grade' && (
          <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="text-center py-12">
              <div className="text-6xl mb-4">📊</div>
              <h2 className="text-2xl font-bold text-gray-800 mb-2">Grade Management</h2>
              <p className="text-gray-600">Manage student grades and academic performance</p>
            </div>
          </div>
        )}

        {/* Reports & Analytics */}
        {activeMenu === 'reports' && (
          <div className="bg-gray-900 rounded-lg shadow-lg p-6">
            <div className="flex justify-end gap-3 mb-6">
              <button className="px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 hover:bg-gray-600">
                Export PDF/Excel
              </button>
              <button className="px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 hover:bg-gray-600">
                Email Report
              </button>
            </div>

            <div className="mb-6">
              <h2 className="text-3xl font-bold text-white mb-1">Attendance Overview</h2>
              <p className="text-gray-400">Month: October 2024</p>
            </div>

            <div className="grid grid-cols-3 gap-4 mb-6">
              <div className="bg-cyan-300 rounded-xl p-4">
                <div className="flex items-center gap-3 mb-2">
                  <div className="text-2xl">👥</div>
                  <div className="text-3xl font-bold text-gray-800">85%</div>
                </div>
                <div className="text-sm font-semibold text-gray-700">Total Present</div>
              </div>

              <div className="bg-orange-300 rounded-xl p-4">
                <div className="flex items-center gap-3 mb-2">
                  <div className="text-2xl">⏰</div>
                  <div className="text-3xl font-bold text-gray-800">7%</div>
                </div>
                <div className="text-sm font-semibold text-gray-700">Total Late</div>
              </div>

              <div className="bg-red-300 rounded-xl p-4">
                <div className="flex items-center gap-3 mb-2">
                  <div className="text-2xl">❌</div>
                  <div className="text-3xl font-bold text-gray-800">8%</div>
                </div>
                <div className="text-sm font-semibold text-gray-700">Total Absent</div>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-6">
              <div className="bg-gray-800 rounded-xl p-6">
                <h3 className="text-xl font-bold text-white mb-4">Overall Attendance Status</h3>
                <div className="flex justify-center items-center mb-6">
                  <div className="relative w-64 h-64">
                    <svg viewBox="0 0 100 100" className="transform -rotate-90">
                      <circle cx="50" cy="50" r="35" fill="none" stroke="#1f2937" strokeWidth="15" />
                      <circle cx="50" cy="50" r="35" fill="none" stroke="#67e8f9" strokeWidth="15" strokeDasharray="110 220" />
                      <circle cx="50" cy="50" r="35" fill="none" stroke="#fca5a5" strokeWidth="15" strokeDasharray="110 220" strokeDashoffset="-110" />
                    </svg>
                    <div className="absolute inset-0 flex items-center justify-center">
                      <div className="text-center">
                        <div className="text-4xl font-bold text-white">1200</div>
                        <div className="text-sm text-gray-400">1200 Students</div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="space-y-2 text-sm">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className="w-3 h-3 rounded-full bg-cyan-300"></div>
                      <span className="text-white">Present</span>
                    </div>
                    <span className="text-cyan-300 font-bold">85%</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className="w-3 h-3 rounded-full bg-red-300"></div>
                      <span className="text-white">Absent</span>
                    </div>
                    <span className="text-red-300 font-bold">15%</span>
                  </div>
                </div>

                <div className="mt-8">
                  <h3 className="text-lg font-bold text-white mb-4">Daily Attendance Trend</h3>
                  <div className="h-48 bg-gray-900 rounded-lg p-4 relative">
                    <svg className="w-full h-full">
                      <polyline points="10,140 40,130 70,120 100,115 130,100 160,90 190,85 220,75 250,65 280,50" fill="rgba(103, 232, 249, 0.2)" stroke="#67e8f9" strokeWidth="2" />
                    </svg>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <div className="bg-gray-800 rounded-xl p-6">
                  <h3 className="text-xl font-bold text-white mb-4">Overall Attendance Status</h3>
                  
                  <div className="space-y-3 mb-6">
                    <input type="text" placeholder="Search" className="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600" />
                    <div className="grid grid-cols-2 gap-3">
                      <select className="px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600">
                        <option>Filter by Class</option>
                      </select>
                      <button className="px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 flex items-center gap-2">
                        📅 Filter by Date Range
                      </button>
                    </div>
                  </div>

                  <h4 className="text-lg font-bold text-white mb-3">Detailed Breakdown</h4>
                  <div className="space-y-2">
                    <div className="grid grid-cols-4 text-xs text-gray-400 font-semibold mb-2">
                      <div>Student Name</div>
                      <div>Class</div>
                      <div>Status</div>
                      <div>Action</div>
                    </div>
                    {[
                      { name: "Lim Sokha", class: "12A", status: "Present" },
                      { name: "Chay Bora", class: "12A", status: "Present" },
                      { name: "Chay Bora", class: "12B", status: "Late" },
                      { name: "Seng Rotha", class: "12A", status: "Absent" },
                      { name: "Seng Rotha", class: "12A", status: "Absent" },
                      { name: "Lim Sokha", class: "12A", status: "Present" },
                    ].map((student, idx) => (
                      <div key={idx} className="grid grid-cols-4 text-sm text-gray-300 py-2 border-b border-gray-700">
                        <div>{student.name}</div>
                        <div>{student.class}</div>
                        <div className={`${student.status === 'Present' ? 'text-green-400' : student.status === 'Late' ? 'text-orange-400' : 'text-red-400'}`}>
                          {student.status}
                        </div>
                        <div>
                          <button className="text-blue-400 hover:underline">View</button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Announcements */}
        {activeMenu === 'announcements' && (
          <AnnouncementsSection />
        )}

        {/* System Settings */}
        {activeMenu === 'settings' && (
          <div className="bg-gradient-to-br from-pink-50 to-blue-50 rounded-lg shadow-lg p-6">
            <div className="flex justify-between items-center mb-6">
              <div>
                <h2 className="text-3xl font-bold text-gray-800">System Settings</h2>
                <p className="text-gray-600">Site management and security</p>
              </div>
              <div className="flex gap-3">
                <button className="px-6 py-3 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600 flex items-center gap-2">
                  💾 Save Settings
                </button>
                <button className="px-6 py-3 bg-gray-300 text-gray-800 rounded-xl font-semibold hover:bg-gray-400 flex items-center gap-2">
                  ❓ Help
                </button>
              </div>
            </div>

            <div className="grid grid-cols-4 gap-6">
              <div className="col-span-1 space-y-2">
                <button className="w-full px-4 py-3 bg-purple-500 text-white rounded-lg font-semibold text-left">General</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Users & Members</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Content</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Security</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Notifications</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Backup & Restore</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Integrations</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">Log & Audit</button>
                <button className="w-full px-4 py-3 bg-white text-gray-700 rounded-lg font-semibold text-left hover:bg-gray-100">About</button>
              </div>

              <div className="col-span-3 bg-white rounded-xl p-6 shadow">
                <div className="mb-6">
                  <h3 className="text-2xl font-bold text-gray-800 mb-2">General Settings</h3>
                </div>

                <div className="space-y-6">
                  <div>
                    <label className="block text-lg font-semibold text-gray-700 mb-2">System Name</label>
                    <input type="text" value="SMS ADMIN SPI" className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                  </div>

                  <div>
                    <label className="block text-lg font-semibold text-gray-700 mb-2">Logo (URL)</label>
                    <input type="text" value="http://.../Logo.png" className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                  </div>

                  <div className="grid grid-cols-3 gap-4">
                    <div>
                      <label className="block text-lg font-semibold text-gray-700 mb-2">Timezone</label>
                      <select className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <option>Asia-Phnom Penh</option>
                        <option>Asia-Bangkok</option>
                        <option>Asia-Tokyo</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-lg font-semibold text-gray-700 mb-2">Date Format</label>
                      <select className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <option>DD/MM/YYYY</option>
                        <option>MM/DD/YYYY</option>
                        <option>YYYY-MM-DD</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-lg font-semibold text-gray-700 mb-2">Default Language</label>
                      <select className="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <option>Khmer</option>
                        <option>English</option>
                        <option>Thai</option>
                      </select>
                    </div>
                  </div>

                  <div className="pt-6 border-t border-gray-200">
                    <p className="text-gray-500 text-center py-8">Additional system settings can be configured here</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// Main App Component
export default function App() {
  const [currentView, setCurrentView] = useState('dashboard');
  const [students, setStudents] = useState([]);
  const [activeMenu, setActiveMenu] = useState('dashboard');

  const handleAddStudent = (studentData) => {
    setStudents([...students, studentData]);
    alert('Student added successfully!');
    setCurrentView('view');
  };

  const handleLogout = () => {
    alert('Logging out...');
  };

  if (currentView === 'add') {
    return <AddStudentForm onBack={() => setCurrentView('dashboard')} onAddStudent={handleAddStudent} />;
  }

  if (currentView === 'view') {
    return <ViewStudents students={students} onBack={() => setCurrentView('dashboard')} />;
  }

  return <Dashboard onLogout={handleLogout} onNavigate={setCurrentView} studentCount={students.length} activeMenu={activeMenu} setActiveMenu={setActiveMenu} />;
}