<x-app-layout :title="__('messages.add') . ' ' . __('messages.teachers')" :active="'admin.teachers'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.add') }} {{ __('messages.teachers') }}</h1>
        <a href="{{ route('admin.teachers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.teachers.store') }}">
        @csrf
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="full_name" :label="__('messages.full_name')" :value="old('full_name')" required />
                <x-input name="phone" :label="__('messages.phone')" :value="old('phone')" />
                <x-input name="email" :label="__('messages.email')" type="email" :value="old('email')" />
                <x-input name="address" :label="__('messages.address')" :value="old('address')" />
                <x-input name="specialization" :label="'ជំនាញ'" :value="old('specialization')" />
                <x-input name="qualification" :label="'វិញ្ញាបនបត្រ'" :value="old('qualification')" />
                <x-select name="status" :label="__('messages.status')" required>
                    <option value="">{{ __('messages.select') }}</option>
                    <option value="active" @selected(old('status') === 'active')>{{ __('messages.active') }}</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>{{ __('messages.inactive') }}</option>
                    <option value="on_leave" @selected(old('status') === 'on_leave')>{{ __('messages.pending') }}</option>
                </x-select>
                <x-select name="create_user" :label="'បង្កើតគណនីអ្នកប្រើប្រាស់'">
                    <option value="0" @selected(old('create_user', '0') == '0')>{{ __('messages.no') }}</option>
                    <option value="1" @selected(old('create_user') == '1')>{{ __('messages.yes') }}</option>
                </x-select>
                <x-input name="password" :label="__('messages.password')" type="password" />
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>
