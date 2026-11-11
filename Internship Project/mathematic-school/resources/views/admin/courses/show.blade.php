<x-app-layout :title="__('messages.course')" :active="'admin.courses'">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ $course->title }}</h1>
            <x-badge :color="$course->status === 'published' ? 'green' : ($course->status === 'pending_review' ? 'yellow' : ($course->status === 'archived' ? 'gray' : 'red'))">{{ course_status_label($course->status) }}</x-badge>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.courses.edit', $course) }}" class="px-4 py-2 rounded-xl text-sm font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200 hover:bg-yellow-100 transition">{{ __('messages.edit') }}</a>
            <a href="{{ route('admin.courses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('messages.description') }}</h3>
                <p class="text-gray-600 leading-relaxed">{{ $course->description }}</p>
                @if ($course->requirements)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.requirements') }}</h4>
                    <p class="text-gray-600 leading-relaxed">{{ $course->requirements }}</p>
                @endif
                @if ($course->objectives)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.objectives') }}</h4>
                    <p class="text-gray-600 leading-relaxed">{{ $course->objectives }}</p>
                @endif
            </x-card>

            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.lessons') }} ({{ $course->lessons->count() }})</h3>
                    <a href="{{ route('admin.lessons.create', ['course_id' => $course->id]) }}" class="text-xs text-primary-600 font-medium">{{ __('messages.add') }} {{ __('messages.lesson') }}</a>
                </div>
                @forelse ($course->lessons as $lesson)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center">{{ $lesson->order }}</span>
                            <div>
                                <div class="text-sm font-medium">{{ $lesson->title }}</div>
                                <div class="text-xs text-gray-400">{{ $lesson->duration }} {{ __('messages.minutes') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($lesson->is_free)
                                <x-badge color="green">{{ __('messages.free') }}</x-badge>
                            @endif
                            <x-badge :color="$lesson->is_published ? 'green' : 'yellow'">{{ $lesson->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm py-4 text-center">{{ __('messages.no_records') }}</p>
                @endforelse
            </x-card>

            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.enrollments') }} ({{ $course->enrollments->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.enrollment_date') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.progress_percent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($course->enrollments as $enrollment)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium">{{ $enrollment->student?->full_name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $enrollment->enrolled_at?->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3"><x-badge color="gray">{{ enrollment_status_label($enrollment->status) }}</x-badge></td>
                                    <td class="px-4 py-3">{{ round($enrollment->completion_percentage) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                                <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.course_code') }}</dt><dd class="font-medium font-mono text-xs">{{ $course->course_code }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.category') }}</dt><dd class="font-medium">{{ $course->category?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.age_group') }}</dt><dd class="font-medium">{{ $course->age_group }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.level') }}</dt><dd class="font-medium">{{ $course->level ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.duration') }}</dt><dd class="font-medium">{{ $course->duration }} {{ __('messages.minutes') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.price') }}</dt><dd class="font-medium">{{ $course->price > 0 ? '$' . number_format($course->price, 2) : __('messages.free') }}</dd></div>
                </dl>
                <h4 class="font-semibold text-gray-800 mt-6 mb-3">{{ __('messages.teachers') }}</h4>
                <div class="flex flex-wrap gap-2">
                    @forelse ($course->teachers as $teacher)
                        <span class="px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-medium">{{ $teacher->full_name }}</span>
                    @empty
                        <span class="text-gray-500 text-sm">{{ __('messages.no_teachers') }}</span>
                    @endforelse
                </div>
                <h4 class="font-semibold text-gray-800 mt-6 mb-3">{{ __('messages.quizzes') }} ({{ $course->quizzes->count() }})</h4>
                <ul class="space-y-1 text-sm text-gray-600">
                    @forelse ($course->quizzes as $quiz)
                        <li class="flex justify-between border-b border-gray-50 pb-1">
                            <span>{{ $quiz->title }}</span>
                            <x-badge :color="$quiz->is_published ? 'green' : 'yellow'">{{ $quiz->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
                        </li>
                    @empty
                        <li class="text-gray-500">{{ __('messages.no_records') }}</li>
                    @endforelse
                </ul>
                <h4 class="font-semibold text-gray-800 mt-6 mb-3">{{ __('messages.assignments') }} ({{ $course->assignments->count() }})</h4>
                <ul class="space-y-1 text-sm text-gray-600">
                    @forelse ($course->assignments as $assignment)
                        <li class="flex justify-between border-b border-gray-50 pb-1">
                            <span>{{ $assignment->title }}</span>
                            <span class="text-gray-400">{{ $assignment->due_date?->format('d/m/Y') }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">{{ __('messages.no_records') }}</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-app-layout>