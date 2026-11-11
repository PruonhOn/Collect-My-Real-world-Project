<x-app-layout :title="__('messages.edit') . ' ' . __('messages.category')" :active="'admin.categories'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.edit') }} {{ __('messages.category') }}</h1>
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
        @csrf
        @method('PUT')
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="name" :label="__('messages.name')" :value="old('name', $category->name)" required />
                <x-input name="slug" :label="__('messages.slug')" :value="old('slug', $category->slug)" />
                <x-select name="parent_id" :label="__('messages.parent_category')">
                    <option value="">{{ __('messages.none') }}</option>
                    @foreach ($parentCategories as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </x-select>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('description', $category->description) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>