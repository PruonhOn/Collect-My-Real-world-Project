<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Attendance Report</h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="GET" action="{{ route('admin.reports.student') }}">
                <div class="flex flex-col sm:flex-row items-start sm:items-end space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                        <select name="student_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select Student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ request()->input('student_id') == $student->id ? 'selected' : '' }}>{{ $student->student_id }} - {{ $student->user->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>

        @if($selectedStudent ?? null)
        <!-- Student Info -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Attendance for {{ $selectedStudent->user->name ?? 'Student' }}</h3>
                @if(isset($selectedStudent))
                <a href="{{ route('admin.reports.student.pdf', $selectedStudent) }}" class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-sm hover:bg-red-200 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export PDF
                </a>
                @endif
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $reportData['total'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Total Sessions</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $reportData['present'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Present</p>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $reportData['absent'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Absent</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $reportData['percentage'] ?? 0 }}%</p>
                    <p class="text-sm text-gray-500">Attendance Rate</p>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['records'] ?? [] as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->session->classSection->course->code ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $record->session->classSection->section_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $record->session->session_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $record->status === 'present' ? 'bg-green-100 text-green-800' : ($record->status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                {{ isset($selectedStudent) ? 'No attendance records found.' : 'Select a student to view their attendance report.' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
