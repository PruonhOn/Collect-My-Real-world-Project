<x-app-layout :title="__('messages.add') . ' ' . __('messages.students')" :active="'admin.students'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.add') }} {{ __('messages.students') }}</h1>
        <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="full_name" :label="__('messages.full_name')" :value="old('full_name')" required />
                <x-input name="first_name" :label="__('messages.first_name')" :value="old('first_name')" />
                <x-input name="last_name" :label="__('messages.last_name')" :value="old('last_name')" />
                <x-input name="date_of_birth" :label="__('messages.date_of_birth')" type="date" :value="old('date_of_birth')" required />
                <x-select name="gender" :label="__('messages.gender')" required>
                    <option value="">{{ __('messages.select') }}</option>
                    <option value="male" @selected(old('gender') === 'male')>{{ __('messages.male') }}</option>
                    <option value="female" @selected(old('gender') === 'female')>{{ __('messages.female') }}</option>
                </x-select>
                <x-input name="phone" :label="__('messages.phone')" :value="old('phone')" />
                <x-input name="address" :label="__('messages.address')" :value="old('address')" />
                <x-input name="email" :label="__('messages.email')" type="email" :value="old('email')" />
                <x-input name="password" :label="__('messages.password')" type="password" />
                <x-select name="parent_id" :label="__('messages.guardian')">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->full_name }}</option>
                    @endforeach
                </x-select>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.students.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>
