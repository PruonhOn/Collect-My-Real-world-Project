<x-app-layout :title="__('messages.edit') . ' ' . __('messages.lesson')" :active="'admin.lessons'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.edit') }} {{ __('messages.lesson') }}</h1>
        <a href="{{ route('admin.lessons.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.lessons.update', $lesson) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-select name="course_id" :label="__('messages.course')" :required="true">
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id', $lesson->course_id) == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </x-select>
                <div class="grid grid-cols-2 gap-4">
                    <x-input name="order" :label="__('messages.order')" type="number" min="1" :value="old('order', $lesson->order)" />
                    <x-input name="duration" :label="__('messages.duration')" type="number" min="0" :value="old('duration', $lesson->duration)" />
                </div>
                <div class="md:col-span-2">
                    <x-input name="title" :label="__('messages.title')" :value="old('title', $lesson->title)" required />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('description', $lesson->description) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.video_url') }}</label>
                    <input type="url" name="video_url" value="{{ old('video_url', $lesson->video_url) }}" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div class="md:col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.video_file') }}</label>
                    <input type="file" name="video_file" accept="video/*" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @if ($lesson->video_path)
                        <p class="text-xs text-gray-400 mt-1">{{ $lesson->video_path }}</p>
                    @endif
                </div>
                <div class="md:col-span-2 sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.pdf_file') }}</label>
                    <input type="file" name="pdf_file" accept="application/pdf" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    @if ($lesson->pdf_path)
                        <p class="text-xs text-gray-400 mt-1">{{ $lesson->pdf_path }}</p>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.external_resources') }}</label>
                    <textarea name="external_resources" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('external_resources', $lesson->external_resources) }}</textarea>
                </div>
                <div class="md:col-span-2 flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_free" value="1" @checked(old('is_free', $lesson->is_free)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        {{ __('messages.free_lesson') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $lesson->is_published)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        {{ __('messages.publish_now') }}
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.lessons.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>