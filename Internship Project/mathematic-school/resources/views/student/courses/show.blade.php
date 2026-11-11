<x-app-layout :title="$course->title" :active="'student.courses'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 mb-6">
        <x-card :padding="false" class="overflow-hidden">
            <div class="flex flex-col lg:flex-row">
                <div class="lg:w-1/3">
                    @if ($course->thumbnail)
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-56 lg:h-full object-cover">
                    @else
                        <div class="w-full h-56 bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                            <span class="text-6xl font-bold text-white/80">{{ mb_substr($course->title, 0, 1) }}</span>
                        </div>
                    @endif
                </div>
                <div class="p-6 flex-1">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <span class="text-sm font-medium text-gray-500">{{ $course->course_code }}</span>
                        <x-badge color="primary">{{ $course->category?->name ?? __('messages.no_data') }}</x-badge>
                        @if ($course->price > 0)
                            <x-badge color="green">${{ number_format($course->price, 2) }}</x-badge>
                        @else
                            <x-badge color="green">{{ __('messages.free') }}</x-badge>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $course->title }}</h1>
                    <p class="text-gray-600 mb-4">{{ $course->description }}</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-4">
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
                            <div class="text-gray-500">{{ __('messages.teacher') }}</div>
                            <div class="font-medium">{{ $course->teachers->pluck('full_name')->implode(', ') ?: '—' }}</div>
                        </div>
                    </div>

                    @if ($enrollment)
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                            <div class="flex-1">
                                <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $enrollment->completion_percentage }}%"></div>
                                </div>
                            </div>
                            <div>
                                <span class="text-sm font-semibold text-primary-600">{{ (float) $enrollment->completion_percentage }}%</span>
                                <span class="text-xs text-gray-500">{{ __('messages.completed') }}</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('student.courses.mine') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                                {{ __('messages.continue_learning') }}
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('student.courses.enroll', $course) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                                {{ __('messages.enroll') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </x-card>
    </div>

    @if ($course->requirements || $course->objectives)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            @if ($course->requirements)
                <x-card>
                    <h3 class="font-semibold text-lg mb-3">{{ __('messages.requirements') }}</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $course->requirements }}</p>
                </x-card>
            @endif
            @if ($course->objectives)
                <x-card>
                    <h3 class="font-semibold text-lg mb-3">{{ __('messages.objectives') }}</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $course->objectives }}</p>
                </x-card>
            @endif
        </div>
    @endif

    <x-card>
        <h3 class="font-semibold text-lg mb-4">{{ __('messages.lessons') }} ({{ $course->lessons->count() }})</h3>
        <div class="divide-y divide-gray-100">
            @forelse ($course->lessons as $lesson)
                <div class="flex items-center justify-between py-3 gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full {{ in_array($lesson->id, $completedLessonIds) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-xs font-bold shrink-0">
                            {{ in_array($lesson->id, $completedLessonIds) ? '✓' : $lesson->order }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">{{ $lesson->title }}</div>
                            <div class="text-xs text-gray-500">
                                @if ($lesson->video_url || $lesson->video_path)
                                    {{ __('messages.video_lesson') }} ·
                                @endif
                                {{ $lesson->duration ? $lesson->duration . ' ' . __('messages.minutes') : __('messages.lesson') }}
                            </div>
                        </div>
                    </div>
                    @if ($enrollment)
                        <a href="{{ route('student.lessons.show', [$course, $lesson]) }}" class="text-sm text-primary-600 font-semibold hover:underline shrink-0">{{ __('messages.view') }}</a>
                    @else
                        <span class="text-xs text-gray-400 shrink-0">{{ __('messages.enroll') }}</span>
                    @endif
                </div>
            @empty
                <div class="py-8 text-center text-gray-500">{{ __('messages.no_records') }}</div>
            @endforelse
        </div>
    </x-card>
</x-app-layout>