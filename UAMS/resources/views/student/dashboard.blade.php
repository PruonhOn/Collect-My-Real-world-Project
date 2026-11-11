<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Welcome Message --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-2xl font-bold text-gray-900">Welcome back, {{ $student->user->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">Here's an overview of your attendance.</p>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl font-bold text-gray-900">{{ count($enrollments) }}</div>
                    <div class="text-sm text-gray-500">Enrolled Courses</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($overallAttendanceRate, 1) }}%</div>
                    <div class="text-sm text-gray-500">Overall Attendance Rate</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl font-bold text-gray-900">{{ $enrollments->sum(function($e) { return $e->attendance->where('status', 'present')->count(); }) }}</div>
                    <div class="text-sm text-gray-500">Sessions Attended</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl font-bold {{ count($warnings) > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ count($warnings) }}</div>
                    <div class="text-sm text-gray-500">Absence Warnings</div>
                </div>
            </div>

            {{-- Absence Warnings --}}
            @if(count($warnings) > 0)
                <div class="space-y-3">
                    @foreach($warnings as $warning)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <span class="font-medium">{{ $warning['course_name'] }}</span> - Section {{ $warning['section_name'] }}: {{ $warning['message'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Enrolled Sections --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Enrolled Sections</h3>
                </div>
                <div class="p-6">
                    @if($enrollments->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No enrollments</h3>
                            <p class="mt-1 text-sm text-gray-500">You are not currently enrolled in any sections.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($enrollments as $enrollment)
                                @php
                                    $totalSessions = $enrollment->attendance->count();
                                    $presentCount = $enrollment->attendance->where('status', 'present')->count();
                                    $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0;
                                @endphp
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">{{ $enrollment->course->name ?? 'N/A' }}</h4>
                                            <p class="text-xs text-gray-500">Section {{ $enrollment->classSection->name ?? 'N/A' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold {{ $attendanceRate < 75 ? 'text-red-600' : 'text-green-600' }}">{{ $attendanceRate }}%</div>
                                            <div class="text-xs text-gray-500">{{ $totalSessions }} sessions</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $attendanceRate }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Attendance Records --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Attendance Records</h3>
                </div>
                <div class="p-6">
                    @if($recentRecords->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No records</h3>
                            <p class="mt-1 text-sm text-gray-500">No attendance records found.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentRecords as $record)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->session->classSection->course->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->session->classSection->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->session->date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($record->status === 'present')
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Present</span>
                                                @elseif($record->status === 'absent')
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Absent</span>
                                                @elseif($record->status === 'late')
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Late</span>
                                                @elseif($record->status === 'excused')
                                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Excused</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('student.attendance.show', $record) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
