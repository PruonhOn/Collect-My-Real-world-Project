<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Attendance Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Page Header --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-2xl font-bold text-gray-900">Comprehensive Attendance Report</h3>
                <p class="mt-1 text-sm text-gray-500">Detailed breakdown of your attendance across all courses.</p>
            </div>

            {{-- Overall Summary --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Overall Summary</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $report->total_sessions ?? 0 }}</div>
                        <div class="text-xs text-gray-500">Total Sessions</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $report->total_present ?? 0 }}</div>
                        <div class="text-xs text-green-600">Present</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $report->total_absent ?? 0 }}</div>
                        <div class="text-xs text-red-600">Absent</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $report->total_late ?? 0 }}</div>
                        <div class="text-xs text-yellow-600">Late</div>
                    </div>
                </div>
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Overall Attendance Rate</span>
                        <span class="text-sm font-medium {{ $overallRate < 75 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($overallRate, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div
                            class="h-3 rounded-full {{ $overallRate < 75 ? 'bg-red-600' : 'bg-green-600' }}"
                            style="width: {{ $overallRate }}%"
                        ></div>
                    </div>
                </div>
            </div>

            {{-- Per-Course Attendance Table --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">Course-wise Attendance</h4>
                </div>
                <div class="overflow-x-auto">
                    @if(empty($report->courses) || count($report->courses) === 0)
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No course data</h3>
                            <p class="mt-1 text-sm text-gray-500">No attendance records found for your courses.</p>
                        </div>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sessions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Excused</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($report->courses as $course)
                                    @php
                                        $attendanceRate = $course->total_sessions > 0 ? round(($course->present / $course->total_sessions) * 100, 1) : 0;
                                        $isWarning = $attendanceRate < 75;
                                    @endphp
                                    <tr class="{{ $isWarning ? 'bg-red-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $course->course_name ?? 'N/A' }}
                                            @if($isWarning)
                                                <svg class="inline-block w-4 h-4 ml-1 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->section_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->total_sessions ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->present ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->absent ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->late ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->excused ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                                    <div
                                                        class="h-2 rounded-full {{ $isWarning ? 'bg-red-600' : 'bg-green-600' }}"
                                                        style="width: {{ $attendanceRate }}%"
                                                    ></div>
                                                </div>
                                                <span class="text-sm font-medium {{ $isWarning ? 'text-red-600' : 'text-green-600' }}">{{ $attendanceRate }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            {{-- Back Button --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <a href="{{ route('student.attendance.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Attendance
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
