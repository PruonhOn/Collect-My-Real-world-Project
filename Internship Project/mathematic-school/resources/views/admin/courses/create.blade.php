<x-app-layout :title="__('messages.add') . ' ' . __('messages.course')" :active="'admin.courses'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.add') }} {{ __('messages.course') }}</h1>
        <a href="{{ route('admin.courses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="title" :label="__('messages.course_title')" :value="old('title')" required />
                <x-input name="course_code" :label="__('messages.course_code')" :value="old('course_code')" required />
                <x-select name="category_id" :label="__('messages.category')" :required="true">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="age_group" :label="__('messages.age_group')" :required="true">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach (age_groups() as $group => $label)
                        <option value="{{ $group }}" @selected(old('age_group') === $group)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input name="level" :label="__('messages.level')" :value="old('level')" />
                <x-input name="duration" :label="__('messages.duration')" type="number" :value="old('duration')" />
                <x-input name="price" :label="__('messages.price')" type="number" step="0.01" min="0" :value="old('price')" />
                <x-select name="status" :label="__('messages.status')" :required="true">
                    <option value="draft" @selected(old('status') === 'draft')>{{ __('messages.draft') }}</option>
                    <option value="pending_review" @selected(old('status') === 'pending_review')>{{ __('messages.pending_review') }}</option>
                    <option value="published" @selected(old('status') === 'published')>{{ __('messages.published') }}</option>
                    <option value="archived" @selected(old('status') === 'archived')>{{ __('messages.archived') }}</option>
                </x-select>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.thumbnail') }}</label>
                    <input type="file" name="thumbnail" accept="image/*" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('description') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.requirements') }}</label>
                    <textarea name="requirements" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('requirements') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.objectives') }}</label>
                    <textarea name="objectives" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('objectives') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.teachers') }}</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        @forelse ($teachers as $teacher)
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer text-sm">
                                <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}" @checked(is_array(old('teacher_ids')) && in_array($teacher->id, old('teacher_ids'))) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                {{ $teacher->full_name }}
                            </label>
                        @empty
                            <p class="text-gray-500 text-sm">{{ __('messages.no_teachers') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.courses.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>