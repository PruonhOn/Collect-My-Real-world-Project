<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $course->code }} - {{ $course->name }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.courses.edit', $course) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
                <a href="{{ route('admin.courses.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Course Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Code</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $course->code }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $course->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Program</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $course->program->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Credits</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $course->credits ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Semester</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $course->semester ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $course->description ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Class Sections</h3>
                <a href="{{ route('admin.class-sections.create', ['course_id' => $course->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Section</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($course->classSections as $section)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                <a href="{{ route('admin.class-sections.show', $section) }}">{{ $section->section_name }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $section->teacher->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $section->schedule ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $section->enrollments_count ?? $section->enrollments->count() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $section->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $section->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('admin.class-sections.edit', $section) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No class sections for this course.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
