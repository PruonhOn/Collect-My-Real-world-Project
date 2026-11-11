<x-app-layout :title="__('messages.my_results')" :active="'parent.children'">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('parent.children.show', $student) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <span class="text-sm text-gray-500">· {{ __('messages.my_results') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-card :padding="false">
            <div class="p-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('messages.quizzes') }}</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.score') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quizAttempts as $attempt)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 font-medium">{{ $attempt->quiz->title }}</td>
                                <td class="px-4 py-3">{{ $attempt->percentage }}%</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$attempt->passed ? 'green' : 'red'">{{ $attempt->passed ? __('messages.passed') : __('messages.failed') }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_quizzes') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $quizAttempts->links() }}</div>
        </x-card>

        <x-card :padding="false">
            <div class="p-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('messages.assignments') }}</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.grade') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 font-medium">{{ $submission->assignment->title }}</td>
                                <td class="px-4 py-3">
                                    {{ $submission->grade !== null ? $submission->grade . ' / ' . $submission->assignment->total_marks : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $color = $submission->status === 'graded' ? 'green' : 'yellow';
                                        $label = $submission->status === 'graded' ? __('messages.graded') : __('messages.pending');
                                    @endphp
                                    <x-badge :color="$color">{{ $label }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $submission->submitted_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_assignments') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $submissions->links() }}</div>
        </x-card>
    </div>
</x-app-layout>