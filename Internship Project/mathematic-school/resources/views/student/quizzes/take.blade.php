<x-app-layout :title="$quiz->title" :active="'student.quizzes'">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $quiz->title }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $quiz->course->title }} · {{ $quiz->question_count }} {{ __('messages.questions') }} · {{ $quiz->passing_score }}% {{ __('messages.passing_score') }}</p>
            </div>
            @if ($quiz->time_limit)
                <div class="text-sm font-semibold text-red-600" x-data="{ timeLeft: {{ $quiz->time_limit * 60 }}, timer: null }" x-init="timer = setInterval(() => { timeLeft--; if (timeLeft <= 0) { clearInterval(timer); document.getElementById('submit-quiz').click(); } }, 1000)">
                    {{ __('messages.quiz_time_left') }}: <span x-text="Math.floor(timeLeft / 60) + ':' + String(timeLeft % 60).padStart(2, '0')"></span>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}">
            @csrf
            <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">

            <div class="space-y-6">
                @foreach ($quiz->questions as $index => $question)
                    <x-card>
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <h3 class="font-semibold text-gray-800">
                                <span class="text-primary-600">{{ $index + 1 }}.</span> {{ $question->question }}
                            </h3>
                            <span class="text-xs text-gray-500 shrink-0">{{ $question->marks }} {{ __('messages.marks') }}</span>
                        </div>

                        @if ($question->question_type === 'true_false')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($question->answers as $answer)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="answers[{{ $question->id }}][]" value="{{ $answer->id }}" class="h-4 w-4 text-primary-600">
                                        <span class="text-sm font-medium">{{ $answer->answer }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($question->answers as $answer)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50">
                                        <input type="{{ $question->question_type === 'multiple_answer' ? 'checkbox' : 'radio' }}"
                                            name="answers[{{ $question->id }}][]" value="{{ $answer->id }}"
                                            class="h-4 w-4 text-primary-600">
                                        <span class="text-sm font-medium">{{ $answer->answer }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </x-card>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" id="submit-quiz" class="px-6 py-3 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                    {{ __('messages.submit') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>