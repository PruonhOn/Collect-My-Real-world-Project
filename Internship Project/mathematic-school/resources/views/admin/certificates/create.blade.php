<x-app-layout :title="__('messages.add') . ' ' . __('messages.certificate')" :active="'admin.certificates'">
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.add') }} {{ __('messages.certificate') }}</h1>
        <a href="{{ route('admin.certificates.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.certificates.store') }}">
        @csrf
        <x-card>
            <x-select name="enrollment_id" :label="__('messages.select_enrollment')" :required="true">
                <option value="">{{ __('messages.select') }}</option>
                @forelse ($completedEnrollments as $enrollment)
                    <option value="{{ $enrollment->id }}" @selected(old('enrollment_id') == $enrollment->id)>
                        {{ $enrollment->student?->full_name }} — {{ $enrollment->course?->title }}
                    </option>
                @empty
                @endforelse
            </x-select>

            <p class="text-sm text-gray-500 mt-4">{{ __('messages.eligible_count') }}: {{ $completedEnrollments->count() }}</p>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.certificates.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>