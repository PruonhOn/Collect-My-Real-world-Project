<x-app-layout :title="__('messages.create') . ' ' . __('messages.courses')" :active="'teacher.courses'">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('messages.create') }} {{ __('messages.courses') }}</h2>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.courses.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="title" :label="__('messages.course_title')" required value="{{ old('title') }}" />
                <x-input name="course_code" :label="__('messages.course_code')" required value="{{ old('course_code') }}" />
                <x-select name="category_id" :label="__('messages.category')" :value="old('category_id')" required>
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="age_group" :label="__('messages.age_group')" :value="old('age_group')">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($ageGroups as $key => $label)
                        <option value="{{ $key }}" @selected(old('age_group') === $key)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input name="level" :label="__('messages.level')" value="{{ old('level') }}" />
                <x-input name="duration" type="number" :label="__('messages.duration')" value="{{ old('duration') }}" />
                <x-input name="price" type="number" step="0.01" :label="__('messages.price_usd')" value="{{ old('price') }}" />
                <div>
                    <x-select name="status" :label="__('messages.status')" :value="old('status', 'draft')" required>
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>{{ __('messages.draft') }}</option>
                        <option value="pending_review" @selected(old('status') === 'pending_review')>{{ __('messages.pending_review') }}</option>
                        <option value="published" @selected(old('status') === 'published')>{{ __('messages.published') }}</option>
                        <option value="archived" @selected(old('status') === 'archived')>{{ __('messages.archived') }}</option>
                    </x-select>
                </div>
                <div class="md:col-span-2">
                    <x-textarea name="description" :label="__('messages.description')" :rows="4" />
                </div>
                <div>
                    <x-input name="thumbnail" type="file" :label="__('messages.thumbnail')" accept="image/*" />
                </div>
                <div>
                    <x-textarea name="requirements" :label="__('messages.requirements')" :rows="3" />
                </div>
                <div>
                    <x-textarea name="objectives" :label="__('messages.objectives')" :rows="3" />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold hover:bg-gray-50">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>
