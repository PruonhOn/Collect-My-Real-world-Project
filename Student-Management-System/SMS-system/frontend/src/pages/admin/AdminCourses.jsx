import React, { useMemo, useState } from "react";
import {
  BookOpen,
  Download,
  Filter,
  PlusCircle,
  ChevronDown,
  ChevronUp,
  Search,
} from "lucide-react";

const MOCK_COURSES = [
  {
    id: 1,
    code: "CS101",
    title: "Introduction to CS",
    department: "IT",
    lecturer: "Dr. Sok",
    semester: "2025-1",
    schedule: "Mon/Wed 08:00",
  },
  {
    id: 2,
    code: "CS102",
    title: "Software Engineering",
    department: "IT",
    lecturer: "Ms. Lina",
    semester: "2025-1",
    schedule: "Tue/Thu 10:00",
  },
];

const FilterSelect = ({ label, value, options, onChange }) => (
  <label className="flex-1 min-w-[140px] text-sm font-semibold text-gray-600">
    <span className="sr-only">{label}</span>
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full rounded-xl bg-[#3ec6f0] text-gray-800 font-semibold px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 shadow-inner"
    >
      <option value="">{label}</option>
      {options.map((opt) => (
        <option key={opt} value={opt}>
          {opt}
        </option>
      ))}
    </select>
  </label>
);

const CourseRow = ({ course }) => {
  const [open, setOpen] = useState(false);

  return (
    <div className="bg-[#67d6f8] rounded-2xl p-4 text-gray-800 shadow mb-4">
      <div className="grid grid-cols-[40px,90px,1fr,120px,130px,140px,150px,150px] gap-3 items-center text-sm font-semibold">
        <span>{course.id}</span>
        <span>{course.code}</span>
        <span>{course.title}</span>
        <span>{course.department}</span>
        <span>{course.lecturer}</span>
        <span>{course.semester}</span>
        <span>{course.schedule}</span>
        <div className="relative">
          <button
            onClick={() => setOpen((prev) => !prev)}
            className="flex items-center justify-between w-full bg-[#8abefc] text-white px-4 py-2 rounded-xl font-semibold"
          >
            Actions
            {open ? (
              <ChevronUp className="w-4 h-4" />
            ) : (
              <ChevronDown className="w-4 h-4" />
            )}
          </button>
          {open && (
            <div className="absolute right-0 mt-2 w-40 bg-white text-gray-700 rounded-xl shadow-lg border border-gray-200 z-10">
              {["Enroll", "Manage grade", "Edit", "Delete"].map((action) => (
                <button
                  key={action}
                  className="w-full text-left px-4 py-2 hover:bg-blue-50 font-medium text-sm"
                >
                  {action}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default function AdminCourses() {
  const [search, setSearch] = useState("");
  const [departmentFilter, setDepartmentFilter] = useState("");
  const [lecturerFilter, setLecturerFilter] = useState("");

  const filteredCourses = useMemo(() => {
    return MOCK_COURSES.filter((course) => {
      const matchesSearch =
        course.title.toLowerCase().includes(search.toLowerCase()) ||
        course.code.toLowerCase().includes(search.toLowerCase());
      const matchesDept = departmentFilter
        ? course.department === departmentFilter
        : true;
      const matchesLecturer = lecturerFilter
        ? course.lecturer === lecturerFilter
        : true;
      return matchesSearch && matchesDept && matchesLecturer;
    });
  }, [search, departmentFilter, lecturerFilter]);

  return (
    <div className="min-h-screen bg-gradient-to-r from-[#1aa3d8] via-[#29bade] to-[#53d6f8] flex">
      <aside className="w-64 bg-[#0186c4] text-white flex flex-col items-center py-8 shadow-2xl">
        <div className="text-center mb-12">
          <div className="text-4xl">🎓</div>
          <div className="mt-2 font-bold">SMS ADMIN SPI</div>
        </div>
        <nav className="w-full px-6 space-y-2">
          {[
            "Admin dashboard",
            "User Management",
            "Student MGT",
            "Attendance MGT",
            "Course Management",
            "Grade",
            "Reports & Analytics",
            "Announcements",
            "System Settings",
          ].map((label) => (
            <button
              key={label}
              className={`w-full flex items-center gap-3 px-4 py-2 rounded-full font-semibold text-sm ${
                label === "Course Management"
                  ? "bg-red-500"
                  : "bg-white text-[#0186c4]"
              }`}
            >
              <BookOpen className="w-4 h-4" />
              <span>{label}</span>
            </button>
          ))}
        </nav>
      </aside>

      <main className="flex-1 bg-[#fde2ea]">
        <header className="flex items-center justify-between px-8 py-6 border-b border-pink-200 bg-[#f6c1d0]">
          <div>
            <h1 className="text-3xl font-bold text-red-500">
              Course Management
            </h1>
            <p className="text-sm text-red-700 font-semibold">
              Create courses, assign lecturers, enroll students
            </p>
          </div>
          <div className="flex items-center gap-3">
            <button className="flex items-center gap-2 bg-white text-gray-700 font-semibold px-4 py-2 rounded-xl shadow hover:bg-gray-100">
              <Download className="w-4 h-4" />
              Import CSV
            </button>
            <button className="flex items-center gap-2 bg-blue-500 text-white font-semibold px-5 py-2 rounded-xl shadow hover:bg-blue-600">
              <PlusCircle className="w-5 h-5" />
              Create Course
            </button>
            <button className="bg-[#4a9fd8] text-white px-4 py-2 rounded-xl font-semibold shadow">
              logout
            </button>
          </div>
        </header>

        <section className="p-8 space-y-6">
          <div className="bg-[#67d6f8] rounded-2xl p-4 flex flex-wrap gap-3 shadow-lg">
            <label className="flex-1 min-w-[200px] relative">
              <span className="sr-only">Search course by title</span>
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 w-4 h-4" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search course by title"
                className="w-full rounded-xl bg-white pl-10 pr-4 py-2 font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-200 shadow-inner"
              />
            </label>

            <FilterSelect
              label="All Dept"
              value={departmentFilter}
              options={["IT", "Business", "Math"]}
              onChange={setDepartmentFilter}
            />

            <FilterSelect
              label="All Lecture"
              value={lecturerFilter}
              options={["Dr. Sok", "Ms. Lina"]}
              onChange={setLecturerFilter}
            />

            <button className="flex items-center gap-2 px-4 py-2 bg-white rounded-xl font-semibold text-gray-800 shadow hover:bg-gray-50">
              <Filter className="w-4 h-4" />
              Import CSV
            </button>
          </div>

          <div className="bg-[#67d6f8] rounded-3xl p-6 shadow-2xl border border-white/40">
            <div className="flex items-center justify-between mb-4 text-gray-800">
              <h2 className="text-2xl font-bold">Courses</h2>
              <span className="font-semibold">{filteredCourses.length} Courses</span>
            </div>

            <div className="grid grid-cols-[40px,90px,1fr,120px,130px,140px,150px,150px] gap-3 text-sm font-bold text-gray-700 mb-4 border-b border-gray-500 pb-2">
              {["#", "Code", "Title", "Dept", "Lecturer", "Semester", "Schedule", "Actions"].map(
                (heading) => (
                  <span key={heading}>{heading}</span>
                )
              )}
            </div>

            {filteredCourses.length === 0 ? (
              <div className="text-center text-gray-600 py-12 font-semibold">
                No courses match your filters.
              </div>
            ) : (
              filteredCourses.map((course) => (
                <CourseRow key={course.id} course={course} />
              ))
            )}
          </div>
        </section>
      </main>
    </div>
  );
}
