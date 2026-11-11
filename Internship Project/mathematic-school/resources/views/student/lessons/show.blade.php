<x-app-layout :title="$lesson->title" :active="'student.courses.mine'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <a href="{{ route('student.courses.show', $course) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
            ← {{ __('messages.back') }} · {{ $course->title }}
        </a>
        <div class="flex items-center gap-2">
            @php
                $lessonIds = $course->lessons->pluck('id')->values();
                $currentIndex = $lessonIds->search($lesson->id);
                $prevId = $currentIndex > 0 ? $lessonIds[$currentIndex - 1] : null;
                $nextId = $currentIndex < $lessonIds->count() - 1 ? $lessonIds[$currentIndex + 1] : null;
            @endphp
            @if ($prevId)
                <a href="{{ route('student.lessons.show', [$course, $prevId]) }}" class="px-3 py-2 rounded-xl text-sm font-semibold bg-white border border-gray-300 hover:bg-gray-50">{{ __('messages.previous_lesson') }}</a>
            @endif
            @if ($nextId)
                <a href="{{ route('student.lessons.show', [$course, $nextId]) }}" class="px-3 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">{{ __('messages.next_lesson') }}</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card :padding="false" class="overflow-hidden">
                <div class="p-6 pb-4">
                    <h1 class="text-xl font-bold text-gray-800">{{ $lesson->order }}. {{ $lesson->title }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $lesson->description }}</p>
                </div>

                @if ($lesson->video_url || $lesson->video_path)
                    <div class="aspect-video bg-black">
                        @if ($lesson->video_url)
                            <video src="{{ $lesson->video_url }}" controls class="w-full h-full object-contain"></video>
                        @else
                            <video src="{{ asset('storage/' . $lesson->video_path) }}" controls class="w-full h-full object-contain"></video>
                        @endif
                    </div>
                @else
                    <div class="aspect-video bg-gray-100 flex flex-col items-center justify-center text-gray-400">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span class="text-sm mt-2">{{ __('messages.lesson') }}</span>
                    </div>
                @endif

                <div class="p-6 flex items-center justify-between flex-wrap gap-4">
                    <a href="{{ route('student.courses.show', $course) }}" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ $lesson->duration ? $lesson->duration . ' ' . __('messages.minutes') : '' }}
                    </a>

                    @if ($currentCompleted)
                        <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-green-100 text-green-700">
                            ✓ {{ __('messages.completed') }}
                        </div>
                    @else
                        <form method="POST" action="{{ route('student.lessons.complete', $lesson) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                                {{ __('messages.mark_complete') }}
                            </button>
                        </form>
                    @endif
                </div>
            </x-card>

            @if ($lesson->materials->isNotEmpty())
                <x-card>
                    <h3 class="font-semibold text-lg mb-4">{{ __('messages.materials') }}</h3>
                    <div class="space-y-2">
                        @foreach ($lesson->materials as $material)
                            <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium">{{ $material->title }}</span>
                                </div>
                                <span class="text-xs text-primary-600 font-semibold">{{ __('messages.download') }}</span>
                            </a>
                        @endforeach
                    </div>
                    @if ($lesson->pdf_path)
                        <a href="{{ asset('storage/' . $lesson->pdf_path) }}" target="_blank" class="mt-3 flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                            <span class="text-sm font-medium">{{ __('messages.pdf') }}</span>
                            <span class="text-xs text-primary-600 font-semibold">{{ __('messages.download') }}</span>
                        </a>
                    @endif
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('messages.course_lessons') }}</div>
                <div class="divide-y divide-gray-50">
                    @foreach ($course->lessons as $courseLesson)
                        <a href="{{ route('student.lessons.show', [$course, $courseLesson]) }}"
                            class="flex items-center gap-3 px-4 py-3 {{ $courseLesson->id === $lesson->id ? 'bg-primary-50' : 'hover:bg-gray-50' }}">
                            <div class="w-7 h-7 rounded-full {{ in_array($courseLesson->id, $completedIds) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-xs font-bold shrink-0">
                                {{ in_array($courseLesson->id, $completedIds) ? '✓' : $courseLesson->order }}
                            </div>
                            <span class="text-sm font-medium {{ $courseLesson->id === $lesson->id ? 'text-primary-700' : 'text-gray-700' }} truncate">{{ $courseLesson->title }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>

            @if ($course->quizzes->isNotEmpty())
                <x-card>
                    <h3 class="font-semibold text-lg mb-3">{{ __('messages.quizzes') }}</h3>
                    <div class="space-y-2">
                        @foreach ($course->quizzes as $quiz)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium truncate">{{ $quiz->title }}</div>
                                    <div class="text-xs text-gray-500">{{ $quiz->question_count }} {{ __('messages.questions') }}</div>
                                </div>
                                <a href="{{ route('student.quizzes.start', $quiz) }}" class="text-xs text-primary-600 font-semibold hover:underline shrink-0">{{ __('messages.take_quiz') }}</a>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>