<x-app-layout :title="__('messages.edit') . ' ' . __('messages.announcements')" :active="'admin.announcements'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.edit') }} {{ __('messages.announcements') }}</h1>
        <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
        @csrf
        @method('PUT')
        <x-card>
            <div class="space-y-6">
                <x-input name="title" :label="__('messages.title')" :value="old('title', $announcement->title)" required />
                <x-select name="audience" :label="__('messages.audience')" :required="true">
                    <option value="all" @selected(old('audience', $announcement->audience) === 'all')>{{ __('messages.all') }}</option>
                    <option value="students" @selected(old('audience', $announcement->audience) === 'students')>{{ __('messages.students') }}</option>
                    <option value="parents" @selected(old('audience', $announcement->audience) === 'parents')>{{ __('messages.parents') }}</option>
                    <option value="teachers" @selected(old('audience', $announcement->audience) === 'teachers')>{{ __('messages.teachers') }}</option>
                </x-select>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.content') }}</label>
                    <textarea name="content" rows="6" required class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('content', $announcement->content) }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    {{ __('messages.publish_now') }}
                </label>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>