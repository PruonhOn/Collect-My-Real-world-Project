<x-app-layout :title="__('messages.certificate')" :active="'student.certificates'">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('student.certificates.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <a href="{{ route('certificates.verify') }}" class="text-sm text-primary-600 font-semibold hover:underline">{{ __('messages.verify_certificate') }}</a>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border-8 border-primary-100 overflow-hidden">
            <div class="p-8 sm:p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-primary-100 text-primary-700 flex items-center justify-center mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="text-base font-bold text-gray-700">{{ school_setting('school_name_km') }}</div>
                <div class="text-gray-500 text-sm mt-0.5">{{ $certificate->course->title }}</div>

                <div class="max-w-lg mx-auto mt-8">
                    <p class="text-gray-600">{{ __('messages.this_certificate_confirms') }}</p>
                    <div class="text-2xl font-bold text-gray-800 mt-3">{{ $certificate->student->full_name }}</div>
                    <p class="text-gray-600 mt-3">{{ __('messages.has_successfully_completed') }}</p>
                    <div class="text-xl font-semibold text-primary-700 mt-2">{{ $certificate->course->title }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-10 text-sm">
                    <div>
                        <div class="text-gray-500">{{ __('messages.cert_id') }}</div>
                        <div class="font-semibold text-gray-800 mt-1">{{ $certificate->certificate_id }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">{{ __('messages.issued_date') }}</div>
                        <div class="font-semibold text-gray-800 mt-1">{{ $certificate->issued_date?->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">{{ __('messages.teacher') }}</div>
                        <div class="font-semibold text-gray-800 mt-1">{{ $certificate->course->teachers->pluck('full_name')->implode(', ') ?: '—' }}</div>
                    </div>
                </div>

                <div class="border-t border-gray-200 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <div class="font-semibold text-gray-800">{{ $certificate->course->creator?->name }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ school_setting('school_name') }}</div>
                    </div>
                    <div class="text-xs text-gray-400">{{ $certificate->issued_date?->format('Y-m-d') }}</div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                {{ __('messages.print') }}
            </button>
        </div>
    </div>
</x-app-layout>