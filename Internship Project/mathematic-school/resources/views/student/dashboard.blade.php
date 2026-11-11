<x-app-layout :title="__('messages.dashboard')" :active="'student.dashboard'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif
    @if (session('info'))
        <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-700 mb-6">{{ session('info') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $totalCourses }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.my_courses') }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-100 text-green-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $completedCourses }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.completed_courses') }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $certificates }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.certificates') }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-100 text-yellow-700 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <div class="text-2xl font-bold">{{ $pendingAssignments }}</div>
                    <div class="text-sm text-gray-500">{{ __('messages.pending_assignments') }}</div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg">{{ __('messages.my_courses') }}</h3>
                    <a href="{{ route('student.courses.index') }}" class="text-sm text-primary-600 font-semibold hover:underline">{{ __('messages.browse_courses') }}</a>
                </div>

                @forelse ($enrollments as $enrollment)
                    <a href="{{ route('student.courses.show', $enrollment->course) }}" class="block p-3 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div class="min-w-0">
                                <div class="font-medium text-sm truncate">{{ $enrollment->course->title }}</div>
                                <div class="text-xs text-gray-500">{{ $enrollment->course->course_code }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-primary-600">{{ (float) $enrollment->completion_percentage }}%</div>
                                    <div class="text-xs text-gray-500">{{ __('messages.completion_percentage') }}</div>
                                </div>
                                <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $enrollment->completion_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-10">
                        <p class="text-sm text-gray-500 mb-4">{{ __('messages.no_enrollments') }}</p>
                        <a href="{{ route('student.courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">
                            {{ __('messages.browse_courses') }}
                        </a>
                    </div>
                @endforelse
            </x-card>
        </div>

        <x-card>
            <h3 class="font-semibold text-lg mb-4">{{ __('messages.quick_actions') }}</h3>
            <div class="space-y-3">
                <a href="{{ route('student.progress') }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <span class="text-sm font-medium">{{ __('messages.progress') }}</span>
                </a>
                <a href="{{ route('student.quizzes.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm font-medium">{{ __('messages.quizzes') }}</span>
                </a>
                <a href="{{ route('student.assignments.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-sm font-medium">{{ __('messages.assignments') }}</span>
                </a>
                <a href="{{ route('student.grades') }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    <span class="text-sm font-medium">{{ __('messages.grades') }}</span>
                </a>
                <a href="{{ route('student.certificates.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    <span class="text-sm font-medium">{{ __('messages.certificates') }}</span>
                </a>
            </div>
        </x-card>
    </div>
</x-app-layout>