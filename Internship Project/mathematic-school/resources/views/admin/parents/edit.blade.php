<x-app-layout :title="__('messages.edit') . ' ' . __('messages.parents')" :active="'admin.parents'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.edit') }} {{ $parent->full_name }}</h1>
        <a href="{{ route('admin.parents.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.parents.update', $parent) }}">
        @csrf @method('PUT')
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="full_name" :label="__('messages.full_name')" :value="old('full_name', $parent->full_name)" required />
                <x-input name="phone" :label="__('messages.phone')" :value="old('phone', $parent->phone)" />
                <x-input name="email" :label="__('messages.email')" type="email" :value="old('email', $parent->email)" />
                <x-input name="address" :label="__('messages.address')" :value="old('address', $parent->address)" />
                <x-input name="occupation" :label="'មុខរបរ'" :value="old('occupation', $parent->occupation)" />
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.children') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-xl p-3">
                    @foreach ($children as $child)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="children_ids[]" value="{{ $child->id }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @checked($parent->children->contains($child->id))>
                            {{ $child->full_name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.parents.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>
