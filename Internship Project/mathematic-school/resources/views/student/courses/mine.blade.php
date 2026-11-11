<x-app-layout :title="__('messages.my_courses')" :active="'student.courses.mine'">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.my_courses') }}</h1>
        <a href="{{ route('student.courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            {{ __('messages.browse_courses') }}
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($enrollments as $enrollment)
            <x-card :padding="false" class="overflow-hidden">
                @if ($enrollment->course->thumbnail)
                    <a href="{{ route('student.courses.show', $enrollment->course) }}">
                        <img src="{{ asset('storage/' . $enrollment->course->thumbnail) }}" alt="{{ $enrollment->course->title }}" class="h-40 w-full object-cover">
                    </a>
                @else
                    <a href="{{ route('student.courses.show', $enrollment->course) }}" class="block h-40 w-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                        <span class="text-4xl font-bold text-white/80">{{ mb_substr($enrollment->course->title, 0, 1) }}</span>
                    </a>
                @endif
                <div class="p-5">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="text-xs text-gray-500">{{ $enrollment->course->course_code }}</span>
                        <x-badge :color="$enrollment->status === 'completed' ? 'green' : 'blue'">{{ enrollment_status_label($enrollment->status) }}</x-badge>
                    </div>
                    <h3 class="font-semibold text-gray-800 line-clamp-1">{{ $enrollment->course->title }}</h3>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="text-gray-500">{{ __('messages.completion_percentage') }}</span>
                            <span class="font-semibold text-primary-600">{{ (float) $enrollment->completion_percentage }}%</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-600 rounded-full transition-all" style="width: {{ $enrollment->completion_percentage }}%"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('student.courses.show', $enrollment->course) }}" class="block text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                            {{ $enrollment->status === 'completed' ? __('messages.details') : __('messages.continue_learning') }}
                        </a>
                    </div>
                </div>
            </x-card>
        @empty
            <div class="col-span-full text-center py-16">
                <p class="text-gray-500 mb-4">{{ __('messages.no_enrollments') }}</p>
                <a href="{{ route('student.courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                    {{ __('messages.browse_courses') }}
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $enrollments->links() }}
    </div>
</x-app-layout>