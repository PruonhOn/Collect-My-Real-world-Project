<x-app-layout :title="__('messages.my_courses')" :active="'teacher.courses'">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('messages.my_courses') }}</h2>
            <a href="{{ route('teacher.courses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700">
                + {{ __('messages.add') }}
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <x-card class="mb-6">
        <form method="GET" action="{{ route('teacher.courses.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-input name="search" :label="__('messages.search')" :value="request('search')" placeholder="{{ __('messages.course_title') }}" />
            <x-select name="status" :label="__('messages.status')" :value="request('status')">
                <option value="">{{ __('messages.all') }}</option>
                <option value="draft" @selected(request('status') === 'draft')>{{ __('messages.draft') }}</option>
                <option value="pending_review" @selected(request('status') === 'pending_review')>{{ __('messages.pending_review') }}</option>
                <option value="published" @selected(request('status') === 'published')>{{ __('messages.published') }}</option>
                <option value="archived" @selected(request('status') === 'archived')>{{ __('messages.archived') }}</option>
            </x-select>
            <x-select name="category_id" :label="__('messages.category')" :value="request('category_id')">
                <option value="">{{ __('messages.all') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-select>
            <div class="flex items-end gap-2">
                <x-button type="primary" :submit="true">{{ __('messages.search') }}</x-button>
                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold hover:bg-gray-50">{{ __('messages.reset') }}</a>
            </div>
        </form>
    </x-card>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.course_title') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.category') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.students') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($courses as $course)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-sm">{{ $course->title }}</div>
                                <div class="text-xs text-gray-500">{{ $course->course_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $course->category?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $course->enrollments_count }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $badge = match ($course->status) {
                                        'published' => 'green',
                                        'pending_review' => 'yellow',
                                        'archived' => 'gray',
                                        default => 'red',
                                    };
                                @endphp
                                <x-badge :color="$badge">{{ course_status_label($course->status) }}</x-badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2 text-sm">
                                    <a href="{{ route('teacher.courses.show', $course) }}" class="text-primary-600 hover:underline">{{ __('messages.view') }}</a>
                                    <a href="{{ route('teacher.courses.edit', $course) }}" class="text-blue-600 hover:underline">{{ __('messages.edit') }}</a>
                                    @if ($course->status === 'published')
                                        <form method="POST" action="{{ route('teacher.courses.publish', $course) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-yellow-600 hover:underline">{{ __('messages.unpublish') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('teacher.courses.publish', $course) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-600 hover:underline">{{ __('messages.publish') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('teacher.courses.destroy', $course) }}" onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $courses->links() }}
    </div>
</x-app-layout>
