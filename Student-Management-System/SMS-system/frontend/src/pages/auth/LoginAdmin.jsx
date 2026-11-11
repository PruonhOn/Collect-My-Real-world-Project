import React, { useState } from "react";

export default function LoginAdmin({ onLogin }) {
  const [role, setRole] = useState("admin");
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [idCard, setIdCard] = useState("");

  const handleSubmit = (e) => {
    e.preventDefault();

    // Admin: require password to be exactly 'admin123'
    if (role === "admin") {
      if (!username || !password) {
        alert("Please enter username and password for admin.");
        return;
      }
      if (password !== "admin123") {
        alert("Invalid admin password. Demo admin password is: admin123");
        return;
      }
      onLogin(role);
      return;
    }

    // Student: require both username and idCard
    if (role === "student") {
      if (!username || !idCard) {
        alert("Please enter username and ID Card for student.");
        return;
      }
      onLogin(role);
      return;
    }

    // Lecturer: normal username/password check (no fixed demo)
    if (role === "lecturer") {
      if (!username || !password) {
        alert("Please enter username and password for lecturer.");
        return;
      }
      onLogin(role);
      return;
    }

    alert("Please fill all required fields!");
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center bg-cover bg-center relative"
      style={{
        backgroundImage: "url('/src/assets/SPI-Campus-no-logo-Crop.png')",
      }}
    >
      <div className="absolute inset-0 bg-black/30 backdrop-blur-[2px]" />

      <div className="relative w-full max-w-[440px] bg-white/90 rounded-[32px] shadow-2xl border border-white/70 p-10">
        <div className="text-center mb-8">
          <div className="text-4xl mb-2">👩‍🎓</div>
          <h1 className="text-2xl font-extrabold text-gray-900">
            Student Managment System
          </h1>
          <p className="text-sm text-gray-500">Sign in to continue</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-semibold text-gray-600 mb-1">
              Role
            </label>
            <select
              value={role}
              onChange={(e) => setRole(e.target.value)}
              className="w-full border-2 border-gray-200 rounded-2xl px-4 py-2.5 focus:border-pink-300 focus:ring-2 focus:ring-pink-200 text-gray-700 font-semibold"
            >
              <option value="admin">Admin</option>
              <option value="student">Student</option>
              <option value="lecturer">Lecturer</option>
            </select>
          </div>

          {/* Admin & Lecturer login fields */}
          {(role === "admin" || role === "lecturer") && (
            <>
              <div>
                <label className="block text-sm font-semibold text-gray-600 mb-1">
                  Username
                </label>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full border-2 border-gray-200 rounded-2xl px-4 py-2.5 focus:border-pink-300 focus:ring-2 focus:ring-pink-200"
                  placeholder="Enter username"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-600 mb-1">
                  Password
                </label>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full border-2 border-gray-200 rounded-2xl px-4 py-2.5 focus:border-pink-300 focus:ring-2 focus:ring-pink-200"
                  placeholder="Enter password"
                />
              </div>
            </>
          )}

          {/* Student login fields: Username + ID Card */}
          {role === "student" && (
            <>
              <div>
                <label className="block text-sm font-semibold text-gray-600 mb-1">
                  Username
                </label>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full border-2 border-gray-200 rounded-2xl px-4 py-2.5 focus:border-pink-300 focus:ring-2 focus:ring-pink-200"
                  placeholder="Enter username"
                />
              </div>

              <div>
                <label className="block text-sm font-semibold text-gray-600 mb-1">
                  ID Card
                </label>
                <input
                  type="text"
                  value={idCard}
                  onChange={(e) => setIdCard(e.target.value)}
                  className="w-full border-2 border-gray-200 rounded-2xl px-4 py-2.5 focus:border-pink-300 focus:ring-2 focus:ring-pink-200"
                  placeholder="Enter student ID card"
                />
              </div>
            </>
          )}

          {/* Login Button */}
          <button
            type="submit"
            className="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-pink-500 hover:to-pink-500 text-white py-2.5 rounded-2xl font-semibold text-lg shadow-md transition hover:scale-[1.01]"
          >
            Login
          </button>

          {/* Demo hints */}
          <div className="text-xs text-center text-gray-600 leading-relaxed">
            Admin demo password: <strong>admin123</strong>.
            <br />
            Students must provide both username and ID card.
          </div>
        </form>
      </div>
    </div>
  );
}
