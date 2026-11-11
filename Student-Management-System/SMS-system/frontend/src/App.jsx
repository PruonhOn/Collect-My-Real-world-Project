// src/App.jsx
import React, { useState } from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import LoginAdmin from "./pages/auth/LoginAdmin";
import AddStudent from "./pages/admin/AddStudent";
import StudentsList from "./pages/admin/StudentsList";
import GradeView from "./pages/admin/GradeView";
import Dashboard from "./pages/admin/Dashboard";
import "./App.css";

export default function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [userRole, setUserRole] = useState("");
  const [currentView, setCurrentView] = useState("dashboard"); // 'dashboard' | 'add' | 'view' | 'grade'
  const [students, setStudents] = useState([]);
  const [selectedStudent, setSelectedStudent] = useState(null);
  const [activeMenu, setActiveMenu] = useState("dashboard");

  const handleLogin = (role) => {
    setUserRole(role);
    setIsLoggedIn(true);
    setCurrentView("dashboard");
  };

  const handleLogout = () => {
    setIsLoggedIn(false);
    setUserRole("");
    setCurrentView("dashboard");
  };

  const handleAddStudent = (student) => {
    setStudents((prev) => [...prev, student]);
    setCurrentView("view");
  };

  const handleViewGrade = (student) => {
    setSelectedStudent(student);
    setCurrentView("grade");
  };

  if (!isLoggedIn) {
    return <LoginAdmin onLogin={handleLogin} />;
  }

  if (currentView === "add") {
    return (
      <AddStudent
        onAdd={handleAddStudent}
        onCancel={() => setCurrentView("dashboard")}
      />
    );
  }

  if (currentView === "view") {
    return (
      <StudentsList
        students={students}
        onViewGrade={handleViewGrade}
        onBack={() => setCurrentView("dashboard")}
      />
    );
  }

  if (currentView === "grade" && selectedStudent) {
    return (
      <GradeView
        student={selectedStudent}
        onBack={() => setCurrentView("view")}
      />
    );
  }

  return (
    <Dashboard
      onLogout={handleLogout}
      onNavigate={(view) => setCurrentView(view)}
      studentCount={students.length}
      activeMenu={activeMenu}
      setActiveMenu={setActiveMenu}
    />
  );
}
