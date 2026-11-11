<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $classSection->section_name }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.class-sections.edit', $classSection) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
                <a href="{{ route('admin.class-sections.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Section Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Section Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $classSection->section_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Course</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $classSection->course->code ?? 'N/A' }} - {{ $classSection->course->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teacher</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $classSection->teacher->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Term</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $classSection->academicTerm->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Schedule</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $classSection->schedule ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Capacity</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $classSection->enrollments_count ?? $classSection->enrollments->count() }}/{{ $classSection->capacity }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $classSection->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $classSection->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Enrolled Students</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($classSection->enrollments as $enrollment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->student->student_id ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $enrollment->student->user->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->student->user->email ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No students enrolled in this section.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
