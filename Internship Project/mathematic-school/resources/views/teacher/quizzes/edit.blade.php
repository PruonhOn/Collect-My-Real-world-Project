<x-app-layout :title="__('messages.edit') . ' ' . __('messages.quiz')" :active="'teacher.quizzes'">
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $initialQuestions = $quiz->questions->map(function ($question) {
            return [
                'question' => $question->question,
                'type' => $question->question_type,
                'marks' => $question->marks,
                'answers' => $question->answers->map(fn ($a) => [
                    'answer' => $a->answer,
                    'is_correct' => (bool) $a->is_correct,
                ])->values()->toArray(),
            ];
        })->values()->toArray();
    @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.edit') }} {{ __('messages.quiz') }}</h1>
        <a href="{{ route('teacher.quizzes.index', $course) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('teacher.quizzes.update', [$course, $quiz]) }}" x-data="quizBuilder({{ json_encode($initialQuestions) }})">
        @csrf
        @method('PUT')
        <x-card class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="title" :label="__('messages.title')" :value="old('title', $quiz->title)" required />
                <x-select name="lesson_id" :label="__('messages.lesson')">
                    <option value="">{{ __('messages.none') }}</option>
                    @foreach ($lessons as $lesson)
                        <option value="{{ $lesson->id }}" @selected(old('lesson_id', $quiz->lesson_id) == $lesson->id)>{{ $lesson->title }}</option>
                    @endforeach
                </x-select>
                <x-input name="time_limit" :label="__('messages.time_limit_minutes')" type="number" min="1" :value="old('time_limit', $quiz->time_limit)" />
                <x-input name="passing_score" :label="__('messages.passing_score')" type="number" step="0.5" min="0" :value="old('passing_score', $quiz->passing_score)" />
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('description', $quiz->description) }}</textarea>
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="allow_retry" value="1" @checked(old('allow_retry', $quiz->allow_retry)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        {{ __('messages.allow_retry') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $quiz->is_published)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        {{ __('messages.publish_now') }}
                    </label>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-gray-800">{{ __('messages.questions') }}</h3>
                <button type="button" @click="addQuestion()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-primary-50 text-primary-700 border border-primary-200 hover:bg-primary-100 transition">
                    + {{ __('messages.add_question') }}
                </button>
            </div>

            <template x-if="questions.length === 0">
                <p class="text-center text-gray-500 py-8 text-sm">{{ __('messages.no_questions_yet') }}</p>
            </template>

            <div class="space-y-6">
                <template x-for="(q, qi) in questions" :key="qi">
                    <div class="rounded-xl border border-gray-200 p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700" x-text="'{{ __('messages.question') }} #' + (qi + 1)"></span>
                            <button type="button" @click="removeQuestion(qi)" class="text-red-600 text-xs font-medium">✕ {{ __('messages.remove') }}</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <input type="text" name="questions[][question]" x-model="q.question" :placeholder="'{{ __('messages.question') }}'" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm" required>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <select name="questions[][type]" x-model="q.type" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                                    <option value="single">{{ __('messages.single_choice') }}</option>
                                    <option value="multiple">{{ __('messages.multiple_choice') }}</option>
                                    <option value="true_false">{{ __('messages.true_false') }}</option>
                                    <option value="short">{{ __('messages.short_answer') }}</option>
                                </select>
                                <input type="number" name="questions[][marks]" x-model="q.marks" min="1" :placeholder="'{{ __('messages.marks') }}'" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                            </div>
                        </div>

                        <template x-if="q.type !== 'short'">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-500">{{ __('messages.answers') }}</span>
                                    <button type="button" @click="addAnswer(q)" class="text-xs text-primary-600 font-medium">+ {{ __('messages.add_answer') }}</button>
                                </div>
                                <template x-for="(a, ai) in q.answers" :key="ai">
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="questions[][answers][][answer]" x-model="a.answer" :placeholder="'{{ __('messages.answer') }} ' + (ai + 1)" class="flex-1 rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                                        <label class="flex items-center gap-1 text-xs text-gray-600 whitespace-nowrap">
                                            <input type="checkbox" name="questions[][answers][][is_correct]" value="1" x-model="a.is_correct" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            {{ __('messages.correct') }}
                                        </label>
                                        <button type="button" @click="q.answers.splice(ai, 1)" class="text-red-600 text-xs">✕</button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="q.type === 'short'">
                            <p class="text-xs text-gray-400">{{ __('messages.short_answer_hint') }}</p>
                        </template>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('teacher.quizzes.index', $course) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>

    <script>
        function quizBuilder(initial = null) {
            return {
                questions: initial && initial.length
                    ? initial
                    : [{ question: '', type: 'single', marks: 1, answers: [{ answer: '', is_correct: false }] }],
                addQuestion() {
                    this.questions.push({ question: '', type: 'single', marks: 1, answers: [{ answer: '', is_correct: false }] });
                },
                removeQuestion(index) {
                    this.questions.splice(index, 1);
                },
                addAnswer(question) {
                    question.answers.push({ answer: '', is_correct: false });
                },
            };
        }
    </script>
</x-app-layout>