<x-app-layout :title="__('messages.add') . ' ' . __('messages.assignment')" :active="'teacher.assignments'">
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
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.add') }} {{ __('messages.assignment') }}</h1>
        <a href="{{ route('teacher.assignments.index', $course) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <form method="POST" action="{{ route('teacher.assignments.store', $course) }}">
        @csrf
        <x-card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-input name="title" :label="__('messages.title')" :value="old('title')" required />
                </div>
                <x-select name="lesson_id" :label="__('messages.lesson')">
                    <option value="">{{ __('messages.none') }}</option>
                    @foreach ($lessons as $lesson)
                        <option value="{{ $lesson->id }}" @selected(old('lesson_id') == $lesson->id)>{{ $lesson->title }}</option>
                    @endforeach
                </x-select>
                <x-input name="due_date" :label="__('messages.due_date')" type="date" :value="old('due_date')" />
                <x-input name="total_marks" :label="__('messages.total_marks')" type="number" step="0.5" min="0" :value="old('total_marks')" />
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="4" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500">{{ old('description') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        {{ __('messages.publish_now') }}
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                <a href="{{ route('teacher.assignments.index', $course) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition">{{ __('messages.cancel') }}</a>
            </div>
        </x-card>
    </form>
</x-app-layout>