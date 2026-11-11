<x-app-layout :title="__('messages.results')" :active="'teacher.quizzes'">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $quiz->title }}</h1>
            <p class="text-gray-500 text-sm">{{ __('messages.results') }}</p>
        </div>
        <a href="{{ route('teacher.quizzes.index', $quiz->course) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.score') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.total_marks') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.percentage') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.result') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $attempt->student?->full_name }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $attempt->score }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $attempt->total_marks }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $attempt->total_marks > 0 ? round(($attempt->score / $attempt->total_marks) * 100) : 0 }}%</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$attempt->passed ? 'green' : 'red'">{{ $attempt->passed ? __('messages.passed') : __('messages.failed') }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $attempt->submitted_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $attempts->links() }}
        </div>
    </x-card>
</x-app-layout>