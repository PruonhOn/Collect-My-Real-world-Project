<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $faculty->name }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.faculties.edit', $faculty) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
                <a href="{{ route('admin.faculties.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Faculty Details -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Faculty Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $faculty->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Code</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $faculty->code }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $faculty->description ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Departments -->
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Departments</h3>
                <a href="{{ route('admin.departments.create', ['faculty_id' => $faculty->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Department</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Head</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($faculty->departments as $department)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                <a href="{{ route('admin.departments.show', $department) }}">{{ $department->name }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $department->code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $department->head->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('admin.departments.edit', $department) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No departments in this faculty.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
