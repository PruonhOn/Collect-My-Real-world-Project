<x-app-layout :title="$student->full_name" :active="'parent.children'">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('parent.children.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold">
                {{ strtoupper(mb_substr($student->full_name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
                <p class="text-xs text-gray-500">{{ $student->student_id }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <a href="{{ route('parent.children.progress', $student) }}" class="p-4 rounded-2xl bg-white border border-gray-200 hover:border-primary-300 transition text-center">
            <div class="text-lg mb-1">
                <svg class="w-6 h-6 mx-auto text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="text-xs font-medium text-gray-600">{{ __('messages.progress') }}</div>
        </a>
        <a href="{{ route('parent.children.results', $student) }}" class="p-4 rounded-2xl bg-white border border-gray-200 hover:border-primary-300 transition text-center">
            <div class="text-lg mb-1">
                <svg class="w-6 h-6 mx-auto text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-xs font-medium text-gray-600">{{ __('messages.my_results') }}</div>
        </a>
        <a href="{{ route('parent.children.certificates', $student) }}" class="p-4 rounded-2xl bg-white border border-gray-200 hover:border-primary-300 transition text-center">
            <div class="text-lg mb-1">
                <svg class="w-6 h-6 mx-auto text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <div class="text-xs font-medium text-gray-600">{{ __('messages.certificates') }}</div>
        </a>
        <a href="{{ route('parent.children.payments', $student) }}" class="p-4 rounded-2xl bg-white border border-gray-200 hover:border-primary-300 transition text-center">
            <div class="text-lg mb-1">
                <svg class="w-6 h-6 mx-auto text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h2m4 0h4M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
            </div>
            <div class="text-xs font-medium text-gray-600">{{ __('messages.payments') }}</div>
        </a>
        <a href="{{ route('parent.children.attendance', $student) }}" class="p-4 rounded-2xl bg-white border border-gray-200 hover:border-primary-300 transition text-center">
            <div class="text-lg mb-1">
                <svg class="w-6 h-6 mx-auto text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="text-xs font-medium text-gray-600">{{ __('messages.attendance') }}</div>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-card>
            <h3 class="font-semibold text-lg mb-4">{{ __('messages.my_courses') }}</h3>
            <div class="space-y-3">
                @forelse ($student->enrollments as $enrollment)
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-sm font-medium text-gray-800">{{ $enrollment->course->title }}</span>
                            <x-badge :color="$enrollment->status === 'completed' ? 'green' : 'blue'">{{ enrollment_status_label($enrollment->status) }}</x-badge>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-primary-600 rounded-full" style="width: {{ $enrollment->completion_percentage }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-primary-600">{{ (float) $enrollment->completion_percentage }}%</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('messages.no_enrollments') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h3 class="font-semibold text-lg mb-4">{{ __('messages.certificates') }}</h3>
            <div class="space-y-3">
                @forelse ($student->certificates as $cert)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ $cert->course->title }}</div>
                            <div class="text-xs text-gray-500">{{ $cert->certificate_id }}</div>
                        </div>
                        <a href="{{ route('parent.children.certificates', $student) }}" class="text-xs text-primary-600 font-semibold hover:underline">{{ __('messages.view') }}</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('messages.no_certificates') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>