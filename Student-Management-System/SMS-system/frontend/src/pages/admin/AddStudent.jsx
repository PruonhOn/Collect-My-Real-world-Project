import React, { useState } from "react";
import {
  Users,
  GraduationCap,
  BookOpen,
  UserCircle,
  Plus,
  Eye,
  Award,
  Calendar,
  ArrowLeft,
} from "lucide-react";

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
    email: "",
  });

  const handleSubmit = () => {
    if (!formData.firstName || !formData.studentId) {
      alert("Please fill in required fields");
      return;
    }
    onAddStudent(formData);
    // Reset form
    setFormData({
      year: "",
      department: "",
      firstName: "",
      lastName: "",
      studentId: "",
      gender: "Female",
      dateOfBirth: "",
      address: "",
      email: "",
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
          <h2 className="text-3xl font-bold text-gray-800 mb-8 border-b pb-4">
            Add Student
          </h2>

          <div className="space-y-6">
            {/* Select Year */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-lg font-semibold text-gray-700 mb-2">
                  Select Year
                </label>
                <select
                  value={formData.year}
                  onChange={(e) =>
                    setFormData({ ...formData, year: e.target.value })
                  }
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                  <option value="">Choose year...</option>
                  <option value="Year 1">Year 1</option>
                  <option value="Year 2">Year 2</option>
                  <option value="Year 3">Year 3</option>
                  <option value="Year 4">Year 4</option>
                </select>
              </div>

              {/* Select Department */}
              <div>
                <label className="block text-lg font-semibold text-gray-700 mb-2">
                  Select Department
                </label>
                <select
                  value={formData.department}
                  onChange={(e) =>
                    setFormData({ ...formData, department: e.target.value })
                  }
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                  <option value="">Choose department...</option>
                  <option value="Computer Science">Computer Science</option>
                  <option value="Engineering">Engineering</option>
                  <option value="Business">Business</option>
                  <option value="Arts">Arts</option>
                </select>
              </div>
            </div>

            {/* Student Name */}
            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">
                Student Name
              </label>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input
                  type="text"
                  placeholder="First Name"
                  value={formData.firstName}
                  onChange={(e) =>
                    setFormData({ ...formData, firstName: e.target.value })
                  }
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                />
                <input
                  type="text"
                  placeholder="Last Name"
                  value={formData.lastName}
                  onChange={(e) =>
                    setFormData({ ...formData, lastName: e.target.value })
                  }
                  className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                />
              </div>
            </div>

            {/* Student ID */}
            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">
                Student ID
              </label>
              <input
                type="text"
                placeholder="Enter student ID"
                value={formData.studentId}
                onChange={(e) =>
                  setFormData({ ...formData, studentId: e.target.value })
                }
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            {/* Gender */}
            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">
                Gender
              </label>
              <div className="flex gap-6">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    name="gender"
                    value="Female"
                    checked={formData.gender === "Female"}
                    onChange={(e) =>
                      setFormData({ ...formData, gender: e.target.value })
                    }
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
                    onChange={(e) =>
                      setFormData({ ...formData, gender: e.target.value })
                    }
                    className="w-5 h-5 text-blue-600"
                  />
                  <span className="text-lg">Male</span>
                </label>
              </div>
            </div>

            {/* Date of Birth */}
            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">
                Date of Birth
              </label>
              <input
                type="date"
                value={formData.dateOfBirth}
                onChange={(e) =>
                  setFormData({ ...formData, dateOfBirth: e.target.value })
                }
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            {/* Student Address */}
            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">
                Student Address
              </label>
              <input
                type="text"
                placeholder="Enter student address"
                value={formData.address}
                onChange={(e) =>
                  setFormData({ ...formData, address: e.target.value })
                }
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            {/* Email */}
            <div>
              <label className="block text-lg font-semibold text-gray-700 mb-2">
                Email
              </label>
              <input
                type="email"
                placeholder="Enter email address"
                value={formData.email}
                onChange={(e) =>
                  setFormData({ ...formData, email: e.target.value })
                }
                className="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
              />
            </div>

            {/* Submit Button */}
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
          <h2 className="text-3xl font-bold text-gray-800 mb-8">
            Student List
          </h2>

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
                      <td className="p-4">
                        {student.firstName} {student.lastName}
                      </td>
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

// Dashboard Component
function Dashboard({ onNavigate, studentCount }) {
  const [selectedDate, setSelectedDate] = useState(new Date());

  const getMonthName = (date) => {
    const months = [
      "January",
      "February",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
      "September",
      "October",
      "November",
      "December",
    ];
    return months[date.getMonth()];
  };

  const getDaysInMonth = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];
    for (let i = 0; i < startingDayOfWeek; i++) {
      days.push(null);
    }
    for (let i = 1; i <= daysInMonth; i++) {
      days.push(i);
    }
    return days;
  };

  const changeMonth = (direction) => {
    setSelectedDate(
      new Date(
        selectedDate.getFullYear(),
        selectedDate.getMonth() + direction,
        1
      )
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-8">
      <div className="max-w-7xl mx-auto">
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-4xl font-bold text-gray-800">Admin Dashboard</h1>
          <button className="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition">
            Logout
          </button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-2xl shadow-lg p-6">
            <div className="flex items-center justify-between mb-3">
              <div>
                <div className="text-sm text-gray-600 mb-1">Total Students</div>
                <div className="text-4xl font-bold text-gray-800">
                  {studentCount}
                </div>
              </div>
              <div className="w-16 h-16 rounded-full bg-pink-100 flex items-center justify-center">
                <BookOpen className="w-8 h-8 text-pink-500" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-lg p-6">
            <div className="flex items-center justify-between mb-3">
              <div>
                <div className="text-sm text-gray-600 mb-1">
                  Total Instructors
                </div>
                <div className="text-4xl font-bold text-gray-800">25</div>
              </div>
              <div className="w-16 h-16 rounded-full bg-pink-100 flex items-center justify-center">
                <Users className="w-8 h-8 text-pink-500" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-lg p-6">
            <div className="flex items-center justify-between mb-3">
              <div>
                <div className="text-sm text-gray-600 mb-1">Total Subjects</div>
                <div className="text-4xl font-bold text-gray-800">12</div>
              </div>
              <div className="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                <GraduationCap className="w-8 h-8 text-blue-500" />
              </div>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <button
            onClick={() => onNavigate("add")}
            className="bg-gradient-to-r from-pink-400 to-pink-500 text-white p-6 rounded-2xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-3 font-bold text-lg"
          >
            <Plus className="w-6 h-6" />
            Add Student
          </button>

          <button
            onClick={() => onNavigate("view")}
            className="bg-gradient-to-r from-pink-300 to-pink-400 text-white p-6 rounded-2xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-3 font-bold text-lg"
          >
            <Eye className="w-6 h-6" />
            View Students
          </button>

          <button className="bg-white border-2 border-pink-300 text-gray-800 p-6 rounded-2xl shadow-lg hover:shadow-xl transition flex items-center justify-center gap-3 font-bold text-lg">
            <Award className="w-6 h-6" />
            Grade
          </button>
        </div>

        {/* Calendar */}
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
          <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
            <h3 className="text-xl font-bold">Calendar</h3>
          </div>

          <div className="p-6">
            <div className="flex items-center justify-between bg-blue-500 text-white px-6 py-3 rounded-lg mb-4">
              <button
                onClick={() => changeMonth(-1)}
                className="hover:bg-blue-600 px-3 py-1 rounded transition text-xl font-bold"
              >
                ‹
              </button>
              <div className="font-bold text-lg">
                {getMonthName(selectedDate)} {selectedDate.getFullYear()}
              </div>
              <button
                onClick={() => changeMonth(1)}
                className="hover:bg-blue-600 px-3 py-1 rounded transition text-xl font-bold"
              >
                ›
              </button>
            </div>

            <div className="grid grid-cols-7 gap-2 text-center text-sm mb-3">
              {["S", "M", "T", "W", "T", "F", "S"].map((day, i) => (
                <div key={i} className="font-bold text-gray-700 py-2">
                  {day}
                </div>
              ))}
            </div>

            <div className="grid grid-cols-7 gap-2 text-center">
              {getDaysInMonth(selectedDate).map((day, index) => (
                <div
                  key={index}
                  className={`py-3 rounded-lg font-medium transition ${
                    day
                      ? day === new Date().getDate() &&
                        selectedDate.getMonth() === new Date().getMonth()
                        ? "bg-blue-600 text-white shadow-lg"
                        : "hover:bg-gray-100 cursor-pointer text-gray-700"
                      : ""
                  }`}
                >
                  {day || ""}
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Main App
export default function App() {
  const [currentView, setCurrentView] = useState("dashboard");
  const [students, setStudents] = useState([]);

  const handleAddStudent = (studentData) => {
    setStudents([...students, studentData]);
    alert("Student added successfully!");
    setCurrentView("view");
  };

  if (currentView === "add") {
    return (
      <AddStudentForm
        onBack={() => setCurrentView("dashboard")}
        onAddStudent={handleAddStudent}
      />
    );
  }

  if (currentView === "view") {
    return (
      <ViewStudents
        students={students}
        onBack={() => setCurrentView("dashboard")}
      />
    );
  }

  return (
    <Dashboard onNavigate={setCurrentView} studentCount={students.length} />
  );
}
