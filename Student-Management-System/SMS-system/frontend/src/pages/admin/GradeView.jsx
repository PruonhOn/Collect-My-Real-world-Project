import React from "react";

export default function GradeView({ student, onBack = () => {} }) {
  if (!student) return null;
  // mock grades (replace with real data later)
  const grades = [
    { subject: "Math", grade: "A" },
    { subject: "Science", grade: "B+" },
    { subject: "History", grade: "A-" },
  ];

  return (
    <div className="bg-white rounded-lg shadow p-6 mb-6">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold">Grades for {student.name}</h3>
        <button onClick={onBack} className="text-sm text-gray-600">
          Back
        </button>
      </div>
      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-gray-600">
            <th className="pb-2">Subject</th>
            <th className="pb-2">Grade</th>
          </tr>
        </thead>
        <tbody>
          {grades.map((g) => (
            <tr key={g.subject} className="border-t">
              <td className="py-2">{g.subject}</td>
              <td className="py-2">{g.grade}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
