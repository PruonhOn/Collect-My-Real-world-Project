<x-app-layout :title="__('messages.edit') . ' ' . __('messages.courses')" :active="'teacher.courses'">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('messages.edit') }}: {{ $course->title }}</h2>
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

    <form method="POST" action="{{ route('teacher.courses.update', $course) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input name="title" :label="__('messages.course_title')" required :value="$course->title" />
                <x-input name="course_code" :label="__('messages.course_code')" required :value="$course->course_code" />
                <x-select name="category_id" :label="__('messages.category')" :value="$course->category_id" required>
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($course->category_id == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-select>
                <x-select name="age_group" :label="__('messages.age_group')" :value="$course->age_group">
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach ($ageGroups as $key => $label)
                        <option value="{{ $key }}" @selected($course->age_group === $key)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input name="level" :label="__('messages.level')" :value="$course->level" />
                <x-input name="duration" type="number" :label="__('messages.duration')" :value="$course->duration" />
                <x-input name="price" type="number" step="0.01" :label="__('messages.price_usd')" :value="$course->price" />
                <div>
                    <x-select name="status" :label="__('messages.status')" :value="$course->status" required>
                        <option value="draft" @selected($course->status === 'draft')>{{ __('messages.draft') }}</option>
                        <option value="pending_review" @selected($course->status === 'pending_review')>{{ __('messages.pending_review') }}</option>
                        <option value="published" @selected($course->status === 'published')>{{ __('messages.published') }}</option>
                        <option value="archived" @selected($course->status === 'archived')>{{ __('messages.archived') }}</option>
                    </x-select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm @error('description') border-red-300 @enderror">{{ old('description', $course->description) }}</textarea>
                </div>
                <div>
                    <x-input name="thumbnail" type="file" :label="__('messages.thumbnail')" accept="image/*" />
                    @if ($course->thumbnail)
                        <p class="mt-1 text-xs text-gray-500">{{ $course->thumbnail }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.requirements') }}</label>
                    <textarea name="requirements" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm @error('requirements') border-red-300 @enderror">{{ old('requirements', $course->requirements) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.objectives') }}</label>
                    <textarea name="objectives" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm @error('objectives') border-red-300 @enderror">{{ old('objectives', $course->objectives) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-button type="primary" :submit="true">{{ __('messages.update') }}</x-button>
                <a href="{{ route('teacher.courses.show', $course) }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold hover:bg-gray-50">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>
