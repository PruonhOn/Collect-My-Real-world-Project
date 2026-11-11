<x-app-layout :title="__('messages.progress')" :active="'parent.children'">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('parent.children.show', $student) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <span class="text-sm text-gray-500">· {{ __('messages.progress') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.my_courses') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $enrollments->count() }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.completed_courses') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $enrollments->where('status', 'completed')->count() }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.learning_time') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">
                @if ($totalLearningTime >= 3600)
                    {{ number_format($totalLearningTime / 3600, 1) }} {{ __('messages.hours') }}
                @else
                    {{ number_format($totalLearningTime / 60) }} {{ __('messages.minutes') }}
                @endif
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        @forelse ($enrollments as $enrollment)
            <x-card>
                <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800">{{ $enrollment->course->title }}</h3>
                        <p class="text-xs text-gray-500">{{ $enrollment->course->course_code }}</p>
                    </div>
                    <x-badge :color="$enrollment->status === 'completed' ? 'green' : 'blue'">{{ enrollment_status_label($enrollment->status) }}</x-badge>
                </div>

                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-500">{{ __('messages.course_progress') }}</span>
                    <span class="font-semibold text-primary-600">{{ (float) $enrollment->completion_percentage }}%</span>
                </div>
                <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden mb-6">
                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $enrollment->completion_percentage }}%"></div>
                </div>

                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xl font-bold text-gray-800">{{ $enrollment->total_lessons }}</div>
                        <div class="text-xs text-gray-500">{{ __('messages.total_lessons') }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-green-50">
                        <div class="text-xl font-bold text-green-700">{{ $enrollment->completed_lessons }}</div>
                        <div class="text-xs text-gray-500">{{ __('messages.completed_lessons') }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xl font-bold text-gray-800">{{ $enrollment->total_lessons - $enrollment->completed_lessons }}</div>
                        <div class="text-xs text-gray-500">{{ __('messages.remaining_lessons') }}</div>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card>
                <div class="text-center py-12 text-gray-500">{{ __('messages.no_enrollments') }}</div>
            </x-card>
        @endforelse
    </div>
</x-app-layout>