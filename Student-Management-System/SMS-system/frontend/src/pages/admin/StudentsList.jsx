import React, { useState } from "react";

export default function StudentsList({
  students = [],
  onViewGrade = () => {},
  onBack = () => {},
}) {
  const [selected, setSelected] = useState(null);

  return (
    <div className="bg-white rounded-lg shadow p-6 mb-6">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold">Students List</h3>
        <div>
          <button onClick={onBack} className="text-sm text-gray-600 mr-3">
            Back
          </button>
        </div>
      </div>

      {students.length === 0 ? (
        <div className="text-gray-500">No students yet.</div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm table-auto">
            <thead>
              <tr className="text-left text-gray-600">
                <th className="pb-2 pr-4">Roll</th>
                <th className="pb-2 pr-4">Name</th>
                <th className="pb-2 pr-4">ID Card</th>
                <th className="pb-2 pr-4">Email</th>
                <th className="pb-2 pr-4">Dept</th>
                <th className="pb-2 pr-4">Course</th>
                <th className="pb-2 pr-4">City</th>
                <th className="pb-2 pr-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              {students.map((s) => (
                <tr key={s.id} className="border-t">
                  <td className="py-2 pr-4">{s.roll}</td>
                  <td className="py-2 pr-4">{s.name}</td>
                  <td className="py-2 pr-4">{s.idCard}</td>
                  <td className="py-2 pr-4">{s.email}</td>
                  <td className="py-2 pr-4">
                    {(s.departments || []).join(", ")}
                  </td>
                  <td className="py-2 pr-4">{s.course}</td>
                  <td className="py-2 pr-4">{s.city}</td>
                  <td className="py-2 pr-4">
                    <button
                      onClick={() => setSelected(s)}
                      className="text-indigo-600 hover:underline text-sm mr-3"
                    >
                      View
                    </button>
                    <button
                      onClick={() => onViewGrade(s)}
                      className="text-green-600 hover:underline text-sm"
                    >
                      Grade
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal / detail view */}
      {selected && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="bg-white rounded-lg p-6 w-[90%] max-w-2xl">
            <div className="flex justify-between items-center mb-4">
              <h4 className="font-semibold">Student Details</h4>
              <button
                onClick={() => setSelected(null)}
                className="text-gray-600"
              >
                Close
              </button>
            </div>

            <div className="grid grid-cols-2 gap-3 text-sm text-gray-700">
              <div>
                <strong>Roll:</strong> {selected.roll}
              </div>
              <div>
                <strong>Name:</strong> {selected.name}
              </div>
              <div>
                <strong>Father's name:</strong> {selected.fatherName}
              </div>
              <div>
                <strong>DOB:</strong> {selected.dob}
              </div>
              <div>
                <strong>Mobile:</strong> {selected.mobile}
              </div>
              <div>
                <strong>Email:</strong> {selected.email}
              </div>
              <div className="col-span-2">
                <strong>Departments:</strong>{" "}
                {(selected.departments || []).join(", ")}
              </div>
              <div>
                <strong>Course:</strong> {selected.course}
              </div>
              <div>
                <strong>City:</strong> {selected.city}
              </div>
              <div className="col-span-2">
                <strong>Address:</strong> {selected.address}
              </div>
              <div className="col-span-2">
                <strong>Photo:</strong>{" "}
                {selected.photoFilename
                  ? selected.photoFilename
                  : "No file uploaded"}
              </div>
            </div>

            <div className="mt-4 text-right">
              <button
                onClick={() => setSelected(null)}
                className="bg-gray-200 px-4 py-2 rounded"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
