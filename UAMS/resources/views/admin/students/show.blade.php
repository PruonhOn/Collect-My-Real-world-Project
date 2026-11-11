<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $student->user->name ?? 'Student Details' }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.students.edit', $student) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
                <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Student Details -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Student Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Student ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $student->student_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $student->user->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $student->user->email ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Department</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $student->department->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Program</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $student->program->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $student->phone ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Attendance Summary -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Attendance Summary</h3>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $student->enrollments->pluck('id')->count() }}</p>
                    <p class="text-sm text-gray-500">Enrolled Courses</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $attendanceStats['total'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Total Sessions</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $attendanceStats['present'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Present</p>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $attendanceStats['absent'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Absent</p>
                </div>
            </div>
        </div>

        <!-- Enrollments -->
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Enrollments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($student->enrollments as $enrollment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $enrollment->classSection->course->code ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->classSection->section_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->classSection->academicTerm->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $enrollment->status === 'enrolled' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($enrollment->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No enrollments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
