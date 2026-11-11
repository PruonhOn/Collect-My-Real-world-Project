<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Class Report - {{ $classSection->course->name ?? 'N/A' }} - {{ $classSection->name }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('teacher.sections.show', $classSection) }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    Back to Section
                </a>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('teacher.reports.pdf', $classSection) }}"
                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                        Export PDF
                    </a>
                    <a href="{{ route('teacher.reports.excel', $classSection) }}"
                        class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-4 rounded-lg">
                        Export Excel
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Report Header --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Course</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $classSection->course->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Section</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $classSection->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Term</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $classSection->term->name ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center">
                        <p class="text-sm font-medium text-gray-500">Overall Class Attendance Rate:</p>
                        <p class="ml-3 text-2xl font-bold text-indigo-600">{{ number_format($overallRate, 1) }}%</p>
                    </div>
                </div>
            </div>

            {{-- Student Attendance Summary --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Student Attendance Summary</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Code</th>
                                <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Excused</th>
                                <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Rate %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($report['students'] as $index => $student)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $student['student_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $student['student_code'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ $student['present'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ $student['absent'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ $student['late'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $student['excused'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        @php $rate = $student['percentage'] ?? 0; @endphp
                                        @if($rate >= 75)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ number_format($rate, 1) }}%
                                            </span>
                                        @elseif($rate >= 50)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                {{ number_format($rate, 1) }}%
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                {{ number_format($rate, 1) }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No attendance data available for this section.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Overall Stats --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Overall Statistics</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-3xl font-bold text-green-600">{{ $report['overall']['present'] ?? 0 }}</p>
                        <p class="text-sm text-gray-600">Total Present</p>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <p class="text-3xl font-bold text-red-600">{{ $report['overall']['absent'] ?? 0 }}</p>
                        <p class="text-sm text-gray-600">Total Absent</p>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <p class="text-3xl font-bold text-yellow-600">{{ $report['overall']['late'] ?? 0 }}</p>
                        <p class="text-sm text-gray-600">Total Late</p>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-3xl font-bold text-blue-600">{{ $report['overall']['excused'] ?? 0 }}</p>
                        <p class="text-sm text-gray-600">Total Excused</p>
                    </div>
                    <div class="text-center p-4 bg-indigo-50 rounded-lg">
                        <p class="text-3xl font-bold text-indigo-600">{{ number_format($overallRate, 1) }}%</p>
                        <p class="text-sm text-gray-600">Attendance Rate</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
