<x-app-layout :title="__('messages.certificate')" :active="'admin.certificates'">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.certificate') }}</h1>
        <a href="{{ route('admin.certificates.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <x-card>
        <div class="text-center py-10">
            <div class="w-20 h-20 mx-auto rounded-full bg-primary-100 text-primary-700 flex items-center justify-center mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="text-gray-500 text-sm uppercase tracking-wider mb-2">{{ __('messages.certificate_of_completion') }}</div>
            <div class="text-3xl font-bold text-gray-800 mb-2">{{ $certificate->student?->full_name }}</div>
            <p class="text-gray-500 text-lg mb-1">{{ $certificate->course?->title }}</p>
            <div class="text-sm text-gray-400 mt-4">
                {{ __('messages.certificate_id') }}: <span class="font-mono">{{ $certificate->certificate_id }}</span>
            </div>
            <div class="text-sm text-gray-400">
                {{ __('messages.issued_date') }}: {{ $certificate->issued_date?->format('d/m/Y') }}
            </div>
        </div>
        <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-100">
            <form method="POST" action="{{ route('admin.certificates.destroy', $certificate) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition">{{ __('messages.delete') }}</button>
            </form>
        </div>
    </x-card>
</x-app-layout>