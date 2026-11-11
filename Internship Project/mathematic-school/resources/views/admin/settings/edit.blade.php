<x-app-layout :title="__('messages.settings')" :active="'admin.settings'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.settings') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="school_name" :label="__('messages.school_name')" :value="$settings['school_name']" />
                <x-input name="school_phone" :label="__('messages.school_phone')" :value="$settings['school_phone']" />
                <x-input name="school_email" :label="__('messages.school_email')" type="email" :value="$settings['school_email']" />
                <x-input name="currency" :label="__('messages.currency')" :value="$settings['currency']" />
                <x-input name="academic_year" :label="__('messages.academic_year')" :value="$settings['academic_year']" />
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.school_address') }}</label>
                    <textarea name="school_address" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ $settings['school_address'] }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
            </div>
        </x-card>
    </form>
</x-app-layout>