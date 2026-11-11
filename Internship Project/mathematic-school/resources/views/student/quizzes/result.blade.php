<x-app-layout :title="__('messages.your_score')" :active="'student.quizzes'">
    <div class="max-w-3xl mx-auto">
        @if (session('success'))
            <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
        @endif

        <x-card>
            <div class="text-center pt-4 pb-2">
                <div class="text-sm text-gray-500">{{ $quiz->title }}</div>
                <h1 class="text-3xl font-bold text-gray-800 mt-1">{{ $attempt->percentage }}%</h1>
                <x-badge :color="$attempt->passed ? 'green' : 'red'" class="mt-3">
                    {{ $attempt->passed ? __('messages.passed') : __('messages.failed') }}
                </x-badge>
                <div class="text-sm text-gray-500 mt-3">
                    {{ __('messages.your_score') }}: {{ $attempt->score }} / {{ $attempt->total_marks }} {{ __('messages.marks') }}
                </div>
                <div class="text-xs text-gray-400 mt-1">{{ $attempt->submitted_at?->format('d/m/Y H:i') }}</div>
            </div>

            @if ($quiz->allow_retry)
                <div class="text-center mt-4">
                    <a href="{{ route('student.quizzes.start', $quiz) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">
                        {{ __('messages.retry') }}
                    </a>
                </div>
            @endif
        </x-card>

        <div class="mt-6 space-y-6">
            @foreach ($attempt->answers as $index => $answer)
                <x-card>
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <h3 class="font-semibold text-gray-800">
                            <span class="text-primary-600">{{ $index + 1 }}.</span> {{ $answer->question->question }}
                        </h3>
                        <x-badge :color="$answer->is_correct ? 'green' : 'red'">
                            {{ $answer->is_correct ? '✓' : '✗' }}
                        </x-badge>
                    </div>
                    <div class="text-sm">
                        <div class="text-gray-500">
                            {{ __('messages.your_score') }}:
                            <span class="font-medium {{ $answer->is_correct ? 'text-green-700' : 'text-red-700' }}">
                                {{ $answer->is_correct ? $answer->question->marks : 0 }} / {{ $answer->question->marks }}
                            </span>
                        </div>
                        <div class="text-green-700 mt-2">{{ __('messages.correct_answer') }}:
                            {{ $answer->question->answers->where('is_correct', true)->pluck('answer')->implode(', ') }}
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6">
            <a href="{{ route('student.quizzes.index') }}" class="text-sm text-primary-600 font-semibold hover:underline">← {{ __('messages.back') }}</a>
        </div>
    </div>
</x-app-layout>