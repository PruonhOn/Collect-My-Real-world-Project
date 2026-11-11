<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Attendance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Page Header --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-2xl font-bold text-gray-900">Attendance Overview</h3>
                <p class="mt-1 text-sm text-gray-500">View your attendance breakdown by section.</p>
            </div>

            {{-- Enrolled Sections with Attendance --}}
            @if($enrollments->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No enrollments</h3>
                        <p class="mt-1 text-sm text-gray-500">You are not currently enrolled in any sections.</p>
                    </div>
                </div>
            @else
                <div class="space-y-4" x-data="{ openSection: null }">
                    @foreach($enrollments as $enrollment)
                        @php
                            $records = $enrollment->attendance ?? collect();
                            $presentCount = $records->where('status', 'present')->count();
                            $absentCount = $records->where('status', 'absent')->count();
                            $lateCount = $records->where('status', 'late')->count();
                            $excusedCount = $records->where('status', 'excused')->count();
                            $totalSessions = $records->count();
                            $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0;
                        @endphp
                        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            {{-- Section Header --}}
                            <button
                                @click="openSection === {{ $enrollment->id }} ? openSection = null : openSection = {{ $enrollment->id }}"
                                class="w-full p-6 text-left hover:bg-gray-50 transition-colors focus:outline-none"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900">{{ $enrollment->course->name ?? 'N/A' }}</h4>
                                        <p class="text-sm text-gray-500">Section {{ $enrollment->classSection->name ?? 'N/A' }} • Teacher: {{ $enrollment->classSection->teacher->user->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <div class="text-lg font-semibold {{ $attendanceRate < 75 ? 'text-red-600' : 'text-green-600' }}">{{ $attendanceRate }}%</div>
                                            <div class="text-xs text-gray-500">{{ $totalSessions }} sessions</div>
                                        </div>
                                        <svg
                                            class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': openSection === {{ $enrollment->id }} }"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </button>

                            {{-- Section Details (Expandable) --}}
                            <div
                                x-show="openSection === {{ $enrollment->id }}"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 max-h-0"
                                x-transition:enter-end="opacity-100 max-h-96"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 max-h-96"
                                x-transition:leave-end="opacity-0 max-h-0"
                                class="border-t border-gray-200"
                            >
                                <div class="p-6">
                                    {{-- Attendance Progress Bar --}}
                                    <div class="mb-6">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">Attendance Rate</span>
                                            <span class="text-sm font-medium {{ $attendanceRate < 75 ? 'text-red-600' : 'text-green-600' }}">{{ $attendanceRate }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div
                                                class="h-2.5 rounded-full {{ $attendanceRate < 75 ? 'bg-red-600' : 'bg-green-600' }}"
                                                style="width: {{ $attendanceRate }}%"
                                            ></div>
                                        </div>
                                    </div>

                                    {{-- Attendance Summary --}}
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                                        <div class="bg-green-50 rounded-lg p-4 text-center">
                                            <div class="text-2xl font-bold text-green-600">{{ $presentCount }}</div>
                                            <div class="text-xs text-green-600">Present</div>
                                        </div>
                                        <div class="bg-red-50 rounded-lg p-4 text-center">
                                            <div class="text-2xl font-bold text-red-600">{{ $absentCount }}</div>
                                            <div class="text-xs text-red-600">Absent</div>
                                        </div>
                                        <div class="bg-yellow-50 rounded-lg p-4 text-center">
                                            <div class="text-2xl font-bold text-yellow-600">{{ $lateCount }}</div>
                                            <div class="text-xs text-yellow-600">Late</div>
                                        </div>
                                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                                            <div class="text-2xl font-bold text-blue-600">{{ $excusedCount }}</div>
                                            <div class="text-xs text-blue-600">Excused</div>
                                        </div>
                                    </div>

                                    {{-- View Details Link --}}
                                    <div class="text-center">
                                        <a href="{{ route('student.report') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            View Detailed Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
