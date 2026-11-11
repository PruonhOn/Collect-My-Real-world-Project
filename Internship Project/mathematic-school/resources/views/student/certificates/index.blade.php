<x-app-layout :title="__('messages.certificates')" :active="'student.certificates'">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ __('messages.certificates') }}</h1>

    @if ($certificates->isEmpty())
        <x-card>
            <div class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <p class="text-gray-500 mb-4">{{ __('messages.no_certificates') }}</p>
                <a href="{{ route('student.courses.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700">{{ __('messages.browse_courses') }}</a>
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($certificates as $certificate)
                <x-card>
                    <div class="text-center py-4">
                        <div class="w-16 h-16 mx-auto rounded-full bg-primary-100 text-primary-700 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">{{ $certificate->course->title }}</h3>
                        <div class="text-xs text-gray-500 mt-1">{{ $certificate->certificate_id }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $certificate->issued_date?->format('d/m/Y') }}</div>
                    </div>
                    <a href="{{ route('student.certificates.show', $certificate) }}" class="block text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                        {{ __('messages.view') }}
                    </a>
                </x-card>
            @endforeach
        </div>
    @endif

    <div class="mt-6">
        {{ $certificates->links() }}
    </div>
</x-app-layout>