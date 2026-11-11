<x-app-layout :title="$course->title" :active="'teacher.courses'">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $course->title }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('teacher.courses.edit', $course) }}" class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold hover:bg-gray-50">{{ __('messages.edit') }}</a>
                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:underline">{{ __('messages.back') }}</a>
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 mb-6">
        <x-card>
            <div class="flex flex-col md:flex-row md:items-start gap-6">
                @if ($course->thumbnail)
                    <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="w-full md:w-64 h-40 object-cover rounded-xl">
                @endif
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <span class="text-sm font-medium text-gray-500">{{ $course->course_code }}</span>
                        <x-badge color="primary">{{ $course->category?->name ?? __('messages.no_data') }}</x-badge>
                        @php
                            $badge = match ($course->status) {
                                'published' => 'green',
                                'pending_review' => 'yellow',
                                'archived' => 'gray',
                                default => 'red',
                            };
                        @endphp
                        <x-badge :color="$badge">{{ course_status_label($course->status) }}</x-badge>
                    </div>
                    <p class="text-gray-700 mb-3">{{ $course->description }}</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">{{ __('messages.age_group') }}</div>
                            <div class="font-medium">{{ $course->age_group ? (age_groups()[$course->age_group] ?? $course->age_group) : '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('messages.level') }}</div>
                            <div class="font-medium">{{ $course->level ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('messages.duration') }}</div>
                            <div class="font-medium">{{ $course->duration ? $course->duration . ' ' . __('messages.minutes') : '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('messages.price') }}</div>
                            <div class="font-medium">{{ $course->price > 0 ? '$' . number_format($course->price, 2) : __('messages.free') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">{{ __('messages.lessons') }} ({{ $course->lessons->count() }})</h3>
                <a href="{{ route('teacher.lessons.create', $course) }}" class="text-sm text-primary-600 font-semibold hover:underline">+ {{ __('messages.add') }}</a>
            </div>
            @forelse ($course->lessons as $lesson)
                <div class="py-2 flex items-center justify-between border-b border-gray-100 last:border-0">
                    <div>
                        <div class="font-medium text-sm">{{ $lesson->order }}. {{ $lesson->title }}</div>
                        <div class="text-xs text-gray-500">{{ $lesson->duration ? $lesson->duration . ' ' . __('messages.minutes') : '—' }}</div>
                    </div>
                    <a href="{{ route('teacher.lessons.edit', [$course, $lesson]) }}" class="text-sm text-blue-600 hover:underline">{{ __('messages.edit') }}</a>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4 text-center">{{ __('messages.no_records') }}</p>
            @endforelse
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">{{ __('messages.quizzes') }} ({{ $course->quizzes->count() }})</h3>
                <a href="{{ route('teacher.quizzes.create', $course) }}" class="text-sm text-primary-600 font-semibold hover:underline">+ {{ __('messages.add') }}</a>
            </div>
            @forelse ($course->quizzes as $quiz)
                <div class="py-2 flex items-center justify-between border-b border-gray-100 last:border-0">
                    <div>
                        <div class="font-medium text-sm">{{ $quiz->title }}</div>
                        <div class="text-xs text-gray-500">{{ $quiz->question_count }} {{ __('messages.questions') }}</div>
                    </div>
                    <a href="{{ route('teacher.quizzes.edit', [$course, $quiz]) }}" class="text-sm text-blue-600 hover:underline">{{ __('messages.edit') }}</a>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4 text-center">{{ __('messages.no_records') }}</p>
            @endforelse
        </x-card>
    </div>

    <x-card class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg">{{ __('messages.assignments') }} ({{ $course->assignments->count() }})</h3>
            <a href="{{ route('teacher.assignments.create', $course) }}" class="text-sm text-primary-600 font-semibold hover:underline">+ {{ __('messages.add') }}</a>
        </div>
        @forelse ($course->assignments as $assignment)
            <div class="py-2 flex items-center justify-between border-b border-gray-100 last:border-0">
                <div>
                    <div class="font-medium text-sm">{{ $assignment->title }}</div>
                    <div class="text-xs text-gray-500">{{ $assignment->total_marks }} {{ __('messages.marks') }}</div>
                </div>
                <a href="{{ route('teacher.assignments.edit', [$course, $assignment]) }}" class="text-sm text-blue-600 hover:underline">{{ __('messages.edit') }}</a>
            </div>
        @empty
            <p class="text-sm text-gray-500 py-4 text-center">{{ __('messages.no_records') }}</p>
        @endforelse
    </x-card>
</x-app-layout>
