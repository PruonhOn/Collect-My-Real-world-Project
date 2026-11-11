<x-app-layout :title="__('messages.assignments')" :active="'student.assignments'">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ __('messages.assignments') }}</h1>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.courses') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.deadline') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.marks') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assignments as $assignment)
                        @php
                            $submission = $assignment->submissions->first();
                            $statusBadge = 'gray';
                            $statusText = __('messages.not_submitted');
                            if ($submission) {
                                $statusText = $submission->status === 'graded' ? __('messages.graded') : __('messages.submitted');
                                $statusBadge = $submission->status === 'graded' ? 'green' : 'yellow';
                            }
                        @endphp
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $assignment->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $assignment->course->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $assignment->due_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $assignment->total_marks }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$statusBadge">{{ $statusText }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('student.assignments.show', $assignment) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_assignments') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $assignments->links() }}
    </div>
</x-app-layout>