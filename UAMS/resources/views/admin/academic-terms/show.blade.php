<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $term->name }}</h2>
            <a href="{{ route('admin.academic-terms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Terms</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Term Details</h3>
                <a href="{{ route('admin.academic-terms.edit', $term) }}" class="text-sm text-indigo-600 hover:text-indigo-500">Edit</a>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Term Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $term->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $term->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $term->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $term->start_date->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">End Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $term->end_date->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Class Sections ({{ $term->classSections->count() }})</h3>
            @if($term->classSections->isEmpty())
                <p class="text-sm text-gray-500">No class sections in this term.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($term->classSections as $section)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $section->name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $section->course->code ?? 'N/A' }} - {{ $section->course->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $section->teacher?->user?->name ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
