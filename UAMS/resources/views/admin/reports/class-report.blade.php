<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Class Attendance Report</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="GET" action="{{ route('admin.reports.class') }}">
                <div class="flex flex-col sm:flex-row items-start sm:items-end space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class Section</label>
                        <select name="class_section_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select Section</option>
                            @foreach($classSections as $section)
                                <option value="{{ $section->id }}" {{ request()->input('class_section_id') == $section->id ? 'selected' : '' }}>{{ $section->course->code ?? '' }} - {{ $section->section_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>

        @if($selectedSection ?? null)
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">{{ $selectedSection->course->code ?? '' }} - {{ $selectedSection->section_name }}</h3>
                <a href="{{ route('admin.reports.class.excel', $selectedSection) }}" class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-sm hover:bg-green-200 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                            @foreach($sessions as $session)
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $session->session_date->format('M d') }}</th>
                            @endforeach
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($studentAttendances as $studentId => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $data['name'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $data['student_id'] }}</td>
                            @foreach($sessions as $session)
                            <td class="px-3 py-4 text-center">
                                @php $status = $data['sessions'][$session->id] ?? null; @endphp
                                @if($status === 'present')
                                    <span class="inline-block w-5 h-5 bg-green-100 text-green-800 rounded text-xs leading-5 font-semibold">P</span>
                                @elseif($status === 'absent')
                                    <span class="inline-block w-5 h-5 bg-red-100 text-red-800 rounded text-xs leading-5 font-semibold">A</span>
                                @else
                                    <span class="inline-block w-5 h-5 bg-gray-100 text-gray-400 rounded text-xs leading-5 font-semibold">-</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-medium {{ ($data['percentage'] ?? 0) >= 75 ? 'text-green-600' : 'text-red-600' }}">{{ $data['percentage'] ?? 0 }}%</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 3 + count($sessions) }}" class="px-6 py-12 text-center text-sm text-gray-500">
                                {{ isset($selectedSection) ? 'No attendance data for this section.' : 'Select a class section to view the report.' }}
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
