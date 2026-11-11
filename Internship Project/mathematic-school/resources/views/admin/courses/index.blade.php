<x-app-layout :title="__('messages.courses')" :active="'admin.courses'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.courses') }}</h1>
        <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            {{ __('messages.add') }} {{ __('messages.course') }}
        </a>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.courses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <select name="category_id" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.status') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('messages.draft') }}</option>
                    <option value="pending_review" @selected(request('status') === 'pending_review')>{{ __('messages.pending_review') }}</option>
                    <option value="published" @selected(request('status') === 'published')>{{ __('messages.published') }}</option>
                    <option value="archived" @selected(request('status') === 'archived')>{{ __('messages.archived') }}</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                    <a href="{{ route('admin.courses.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course_code') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course_title') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.category') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.age_group') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.price') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.students') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $course->course_code }}</td>
                            <td class="px-4 py-3 font-medium">{{ $course->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $course->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3"><x-badge color="gray">{{ $course->age_group }}</x-badge></td>
                            <td class="px-4 py-3">{{ $course->price > 0 ? '$' . number_format($course->price, 2) : __('messages.free') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $course->enrollments_count }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $color = match ($course->status) {
                                        'published' => 'green',
                                        'pending_review' => 'yellow',
                                        'archived' => 'gray',
                                        default => 'red',
                                    };
                                @endphp
                                <x-badge :color="$color">{{ course_status_label($course->status) }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.courses.show', $course) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                                    <a href="{{ route('admin.courses.edit', $course) }}" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('admin.courses.publish', $course) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs font-medium">{{ $course->status === 'published' ? __('messages.unpublish') : __('messages.publish') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $courses->links() }}
        </div>
    </x-card>
</x-app-layout>