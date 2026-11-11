<x-app-layout :title="__('messages.quizzes')" :active="'student.quizzes'">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ __('messages.quizzes') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($quizzes as $quiz)
            <x-card>
                <div class="flex items-center justify-between mb-2">
                    <x-badge color="primary">{{ $quiz->course->title }}</x-badge>
                    @if ($quiz->time_limit)
                        <span class="text-xs text-gray-500">{{ $quiz->time_limit }} {{ __('messages.minutes') }}</span>
                    @endif
                </div>
                <h3 class="font-semibold text-gray-800 mb-2">{{ $quiz->title }}</h3>
                <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ $quiz->description }}</p>

                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs text-gray-500">{{ $quiz->question_count }} {{ __('messages.questions') }} · {{ $quiz->passing_score }}% {{ __('messages.passing_score') }}</span>
                </div>

                @php
                    $latestAttempt = $quiz->attempts->sortByDesc('created_at')->first();
                @endphp

                @if ($latestAttempt)
                    <div class="flex items-center justify-between">
                        <x-badge :color="$latestAttempt->passed ? 'green' : 'red'">
                            {{ $latestAttempt->percentage }}% · {{ $latestAttempt->passed ? __('messages.passed') : __('messages.failed') }}
                        </x-badge>
                        @if ($quiz->allow_retry)
                            <a href="{{ route('student.quizzes.start', $quiz) }}" class="text-sm text-primary-600 font-semibold hover:underline">{{ __('messages.retry') }}</a>
                        @endif
                    </div>
                @else
                    <a href="{{ route('student.quizzes.start', $quiz) }}" class="block text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                        {{ __('messages.start_quiz') }}
                    </a>
                @endif
            </x-card>
        @empty
            <div class="col-span-full text-center py-16 text-gray-500">{{ __('messages.no_quizzes') }}</div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $quizzes->links() }}
    </div>
</x-app-layout>