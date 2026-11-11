<x-app-layout :title="__('messages.lessons')" :active="'teacher.lessons'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $course->title }}</h1>
            <p class="text-gray-500 text-sm">{{ __('messages.lessons') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('teacher.lessons.create', $course) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                {{ __('messages.add') }} {{ __('messages.lesson') }}
            </a>
            <a href="{{ route('teacher.courses.show', $course) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.order') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.duration') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lessons as $lesson)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $lesson->order }}</td>
                            <td class="px-4 py-3 font-medium">{{ $lesson->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $lesson->duration }} {{ __('messages.minutes') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-badge :color="$lesson->is_published ? 'green' : 'yellow'">{{ $lesson->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
                                    @if ($lesson->is_free)
                                        <x-badge color="green">{{ __('messages.free') }}</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('teacher.lessons.edit', [$course, $lesson]) }}" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('teacher.lessons.destroy', [$course, $lesson]) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $lessons->links() }}
        </div>
    </x-card>
</x-app-layout>