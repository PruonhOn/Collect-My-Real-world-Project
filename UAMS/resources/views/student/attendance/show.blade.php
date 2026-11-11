<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('student.attendance.index') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Attendance Record Detail') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                {{-- Record Header --}}
                <div class="mb-6 pb-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Attendance Record</h3>
                    <p class="text-sm text-gray-500">Record ID: #{{ $attendanceRecord->id }}</p>
                </div>

                {{-- Record Details --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Course Information --}}
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-gray-900">Course Information</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Course</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->session->classSection->course->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Section</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->session->classSection->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Teacher</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->session->classSection->teacher->user->name ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Session Information --}}
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-gray-900">Session Information</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->session->date?->format('l, F j, Y') ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Time</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $attendanceRecord->session->start_time?->format('g:i A') ?? 'N/A' }} - {{ $attendanceRecord->session->end_time?->format('g:i A') ?? 'N/A' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Method</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($attendanceRecord->method ?? 'N/A') }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Attendance Status --}}
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-gray-900">Attendance Status</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if($attendanceRecord->status === 'present')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Present</span>
                                    @elseif($attendanceRecord->status === 'absent')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Absent</span>
                                    @elseif($attendanceRecord->status === 'late')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Late</span>
                                    @elseif($attendanceRecord->status === 'excused')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Excused</span>
                                    @endif
                                </dd>
                            </div>
                            @if($attendanceRecord->reason)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Reason</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->reason }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Record Metadata --}}
                    <div class="space-y-4">
                        <h4 class="text-lg font-medium text-gray-900">Record Information</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Marked By</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->markedBy->name ?? 'System' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Marked At</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $attendanceRecord->created_at?->format('M d, Y g:i A') ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Back Button --}}
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('student.attendance.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
