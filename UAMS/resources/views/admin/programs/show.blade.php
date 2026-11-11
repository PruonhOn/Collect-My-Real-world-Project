<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $program->name }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.programs.edit', $program) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
                <a href="{{ route('admin.programs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Program Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $program->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Code</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $program->code }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Department</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $program->department->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Level</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($program->level ?? 'N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Duration</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $program->duration ? $program->duration . ' years' : 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $program->description ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Courses</h3>
                <a href="{{ route('admin.courses.create', ['program_id' => $program->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Course</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($program->courses as $course)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                <a href="{{ route('admin.courses.show', $course) }}">{{ $course->name }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->credits ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->semester ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No courses in this program.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
