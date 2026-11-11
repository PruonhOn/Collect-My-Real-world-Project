import React, { useState } from "react";
import {
  User,
  GraduationCap,
  Users,
  ClipboardList,
  Megaphone,
  Settings,
  LogOut,
  BookOpen,
  CalendarCheck,
  NotebookPen,
  UserCheck,
} from "lucide-react";

const navItems = [
  { label: "Admin dashboard", icon: <User className="w-4 h-4" /> },
  { label: "User Management", icon: <Users className="w-4 h-4" /> },
  { label: "Student MGT", icon: <UserCheck className="w-4 h-4" /> },
  { label: "Attendance MGT", icon: <CalendarCheck className="w-4 h-4" /> },
  { label: "Course Management", icon: <BookOpen className="w-4 h-4" /> },
  { label: "Grade", icon: <NotebookPen className="w-4 h-4" /> },
  { label: "Reports & Analytics", icon: <ClipboardList className="w-4 h-4" /> },
  { label: "Announcements", icon: <Megaphone className="w-4 h-4" /> },
  { label: "System Settings", icon: <Settings className="w-4 h-4" /> },
];

const TabButton = ({ label, isActive, onClick }) => (
  <button
    onClick={onClick}
    className={`flex-1 py-3 rounded-2xl text-lg font-semibold shadow-inner transition ${
      isActive
        ? "bg-gray-700 text-white shadow-lg"
        : "bg-[#f58fae] text-gray-700 hover:bg-[#f7a8bf]"
    }`}
  >
    {label}
  </button>
);

const InfoCard = ({ title, value }) => (
  <div className="bg-white/80 rounded-xl p-4 shadow">
    <div className="text-sm font-semibold text-gray-600 mb-1">{title}</div>
    <div className="text-2xl font-bold text-gray-800">{value}</div>
  </div>
);

const courseData = [
  { code: "CS101", schedule: "Mon/Wed 08:00" },
  { code: "CS102", schedule: "Tue/Thu 10:00" },
];

const attendanceRows = [
  { id: 1, name: "PM", present: true, absent: false, late: false },
  { id: 2, name: "PL", present: false, absent: true, late: false },
];

const gradebookRows = [
  { name: "Hai", assignment: 85, midterm: 80, final: 90 },
];

const AdminView = () => (
  <div className="space-y-6">
    <div className="bg-[#3ec6f0] rounded-2xl p-6 shadow-lg text-gray-800">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-2xl font-bold">My Courses</h2>
        <button className="flex items-center gap-2 bg-[#0077c7] text-white px-4 py-2 rounded-full text-sm font-semibold shadow">
          + Add Student
        </button>
      </div>
      <div className="grid grid-cols-2 gap-4">
        {courseData.map((course) => (
          <div
            key={course.code}
            className="bg-white rounded-xl px-4 py-3 text-center font-semibold"
          >
            <div className="text-lg">{course.code}</div>
            <div className="text-sm text-gray-600">{course.schedule}</div>
          </div>
        ))}
      </div>
    </div>

    <div className="bg-[#3ec6f0] rounded-2xl p-6 shadow-lg">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h2 className="text-2xl font-bold text-gray-800">Attendance</h2>
          <p className="text-gray-700 font-semibold">CS101 · 2025-11-05</p>
        </div>
        <div className="text-sm text-gray-700 font-semibold">#</div>
      </div>
      <div className="grid grid-cols-6 text-center font-semibold text-gray-700 border-b border-gray-500 pb-2 mb-3">
        <span>#</span>
        <span>Student</span>
        <span>Present</span>
        <span>Absent</span>
        <span>Late</span>
        <span />
      </div>
      {attendanceRows.map((row) => (
        <div key={row.id} className="grid grid-cols-6 text-center py-2 text-lg">
          <span>{row.id}</span>
          <span className="font-semibold">{row.name}</span>
          <span>{row.present ? "●" : "○"}</span>
          <span>{row.absent ? "●" : "○"}</span>
          <span>{row.late ? "●" : "○"}</span>
          <span />
        </div>
      ))}
      <div className="flex justify-end mt-4">
        <button className="flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow">
          💾 Save attendance
        </button>
      </div>
    </div>

    <div className="bg-[#3ec6f0] rounded-2xl p-6 shadow-lg">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-2xl font-bold text-gray-800">Gradebook · CS101</h2>
        <button className="px-4 py-2 bg-blue-700 text-white rounded-lg font-semibold text-sm">
          Publish Grade
        </button>
      </div>
      <div className="grid grid-cols-4 text-center font-semibold text-gray-700 border-b border-gray-500 pb-2 mb-3">
        <span>Student</span>
        <span>Assignment</span>
        <span>Midterm</span>
        <span>Final</span>
      </div>
      {gradebookRows.map((row) => (
        <div key={row.name} className="grid grid-cols-4 text-center py-2">
          <span className="font-semibold">{row.name}</span>
          <span>{row.assignment}</span>
          <span>{row.midterm}</span>
          <span>{row.final}</span>
        </div>
      ))}
      <div className="flex justify-end mt-4">
        <button className="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold text-sm">
          Publish
        </button>
      </div>
    </div>
  </div>
);

const LecturerView = () => (
  <div className="space-y-6">
    <div className="grid grid-cols-3 gap-4">
      <InfoCard title="Active Courses" value="02" />
      <InfoCard title="Students This Term" value="45" />
      <InfoCard title="Pending Tasks" value="03" />
    </div>
    <div className="bg-white rounded-2xl shadow-lg p-6">
      <h2 className="text-2xl font-bold text-gray-800 mb-4">Upcoming Classes</h2>
      <div className="grid grid-cols-2 gap-4">
        {courseData.map((course) => (
          <div key={course.code} className="p-4 rounded-xl bg-gray-100">
            <div className="text-lg font-bold text-gray-800">
              {course.code}
            </div>
            <div className="text-sm text-gray-600">{course.schedule}</div>
          </div>
        ))}
      </div>
    </div>
    <div className="grid grid-cols-2 gap-6">
      <div className="bg-[#3ec6f0] rounded-2xl p-6 shadow-lg text-gray-800">
        <h3 className="text-xl font-bold mb-3">Recent Attendance</h3>
        <p className="text-sm">CS101 · 90% overall</p>
        <p className="text-sm">Absent this month · 2</p>
      </div>
      <div className="bg-white rounded-2xl p-6 shadow-lg">
        <h3 className="text-xl font-bold text-gray-800 mb-3">Notifications</h3>
        <p className="text-gray-600">No new notifications</p>
      </div>
    </div>
  </div>
);

const StudentView = () => (
  <div className="space-y-6">
    <div className="grid grid-cols-3 gap-4">
      <div className="bg-white rounded-2xl shadow p-4">
        <div className="font-bold text-gray-800">My Info</div>
        <p className="text-sm text-gray-600">PM · Year 2 · Dept: IT</p>
      </div>
      <div className="bg-white rounded-2xl shadow p-4">
        <div className="font-bold text-gray-800">Next class</div>
        <p className="text-sm text-gray-600">CS101 · Mon 08:00</p>
      </div>
      <div className="bg-white rounded-2xl shadow p-4">
        <div className="font-bold text-gray-800">Notifications</div>
        <p className="text-sm text-gray-600">No new notifications</p>
      </div>
    </div>

    <div className="bg-[#3ec6f0] rounded-2xl p-6 shadow-lg text-gray-800">
      <h3 className="text-xl font-bold mb-2">Attendance Summary</h3>
      <p>90% overall</p>
      <p>Absent this month: 2</p>
    </div>

    <div className="bg-white rounded-2xl shadow-lg p-6">
      <h3 className="text-xl font-bold text-gray-800 mb-4">Gradebook</h3>
      <div className="grid grid-cols-4 text-center font-semibold text-gray-700 border-b border-gray-200 pb-2 mb-3">
        <span>Course</span>
        <span>Midterm</span>
        <span>Final</span>
        <span>Final Grade</span>
      </div>
      <div className="grid grid-cols-4 text-center py-2">
        <span>CS101</span>
        <span>80</span>
        <span>90</span>
        <span>85</span>
      </div>
    </div>
  </div>
);

export default function AdminDashboard() {
  const [activeTab, setActiveTab] = useState("Admin");

  const renderContent = () => {
    if (activeTab === "Lecturer") return <LecturerView />;
    if (activeTab === "Student") return <StudentView />;
    return <AdminView />;
  };

  return (
    <div className="min-h-screen bg-gradient-to-r from-cyan-400 via-cyan-300 to-sky-200 flex">
      <aside className="w-64 bg-[#0186c4] text-white flex flex-col items-center py-8 shadow-2xl">
        <div className="text-center mb-12">
          <div className="text-4xl">🎓</div>
          <div className="mt-2 font-bold">SMS ADMIN SPI</div>
        </div>
        <nav className="w-full px-6 space-y-2">
          {navItems.map((item) => (
            <button
              key={item.label}
              className={`w-full flex items-center gap-3 px-4 py-2 rounded-full font-semibold text-sm ${
                item.label === "User Management"
                  ? "bg-red-500"
                  : "bg-white text-[#0186c4]"
              }`}
            >
              {item.icon}
              <span>{item.label}</span>
            </button>
          ))}
        </nav>
      </aside>

      <main className="flex-1 bg-[#f5b5c9]">
        <header className="flex items-center justify-between bg-[#f8c3d3] px-10 py-6 shadow-inner">
          <div className="flex flex-1 gap-4 max-w-2xl">
            {["Admin", "Lecturer", "Student"].map((tab) => (
              <TabButton
                key={tab}
                label={tab}
                isActive={activeTab === tab}
                onClick={() => setActiveTab(tab)}
              />
            ))}
          </div>
          <button className="ml-6 flex items-center gap-2 bg-[#4a9fd8] text-white px-5 py-2 rounded-lg font-semibold shadow">
            <LogOut className="w-4 h-4" />
            logout
          </button>
        </header>

        <section className="p-10">{renderContent()}</section>
      </main>
    </div>
  );
}
