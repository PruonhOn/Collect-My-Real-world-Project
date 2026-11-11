<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Term Attendance Report</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="GET" action="{{ route('admin.reports.term') }}">
                <div class="flex flex-col sm:flex-row items-start sm:items-end space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Academic Term</label>
                        <select name="term_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ request()->input('term_id') == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">Generate Report</button>
                    </div>
                </div>
            </form>
        </div>

        @if($selectedTerm ?? null)
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $selectedTerm->name }} - Attendance Overview</h3>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $reportData['totalSections'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Active Sections</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $reportData['totalStudents'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Enrolled Students</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $reportData['totalSessions'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Sessions Conducted</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ $reportData['avgAttendance'] ?? 0 }}%</p>
                    <p class="text-sm text-gray-500">Avg Attendance</p>
                </div>
            </div>

            <!-- Section-wise breakdown -->
            <h4 class="text-md font-medium text-gray-900 mb-3">Section-wise Attendance</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Attendance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['sectionStats'] ?? [] as $stat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $stat['courseCode'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stat['sectionName'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stat['teacherName'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stat['enrolled'] ?? 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stat['sessions'] ?? 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ ($stat['percentage'] ?? 0) >= 75 ? 'text-green-600' : 'text-red-600' }}">{{ $stat['percentage'] ?? 0 }}%</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($stat['percentage'] ?? 0) >= 75 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ($stat['percentage'] ?? 0) >= 75 ? 'Good' : 'Needs Attention' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No data available for this term.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
