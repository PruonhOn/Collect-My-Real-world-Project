<x-app-layout :title="__('messages.dashboard')" :active="'teacher.dashboard'">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('messages.dashboard') }}</h2>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $courses->count() }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.my_courses') }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-100 text-green-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $totalStudents }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.students') }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $totalEnrollments }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.enrollments') }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-100 text-yellow-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $pendingSubmissions }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.pending') }}</div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">{{ __('messages.my_courses') }}</h3>
                <a href="{{ route('teacher.courses.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">
                    + {{ __('messages.add') }}
                </a>
            </div>

            @forelse ($courses as $course)
                <a href="{{ route('teacher.courses.show', $course) }}" class="block p-3 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium text-sm truncate">{{ $course->title }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $course->enrollments_count }} {{ __('messages.students') }} · {{ $course->category?->name ?? __('messages.no_data') }}
                            </div>
                        </div>
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
                </a>
            @empty
                <p class="text-sm text-gray-500 py-6 text-center">{{ __('messages.no_records') }}</p>
            @endforelse
        </x-card>

        <x-card>
            <h3 class="font-semibold text-lg mb-4">{{ __('messages.view') }} {{ __('messages.submissions') }}</h3>
            @if ($pendingSubmissions > 0)
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('messages.pending') }} {{ $pendingSubmissions }} {{ __('messages.submission') }}·{{ __('messages.details') }}
                </p>
                <a href="{{ route('teacher.submissions.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">
                    {{ __('messages.grade') }}
                </a>
            @else
                <p class="text-sm text-gray-500 py-6 text-center">{{ __('messages.no_data') }}</p>
            @endif
        </x-card>
    </div>
</x-app-layout>
