<x-app-layout :title="__('messages.certificates')" :active="'parent.children'">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('parent.children.show', $student) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <span class="text-sm text-gray-500">· {{ __('messages.certificates') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($certificates as $certificate)
            <x-card>
                <div class="text-center py-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-primary-100 text-primary-700 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="font-semibold text-gray-800">{{ $certificate->course->title }}</h3>
                    <div class="text-xs text-gray-500 mt-1">{{ $certificate->certificate_id }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $certificate->issued_date?->format('d/m/Y') }}</div>
                </div>
            </x-card>
        @empty
            <div class="col-span-full">
                <x-card>
                    <div class="text-center py-12 text-gray-500">{{ __('messages.no_certificates') }}</div>
                </x-card>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $certificates->links() }}
    </div>
</x-app-layout>