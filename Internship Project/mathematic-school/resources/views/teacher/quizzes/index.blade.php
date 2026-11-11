<x-app-layout :title="__('messages.quizzes')" :active="'teacher.quizzes'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $course->title }}</h1>
            <p class="text-gray-500 text-sm">{{ __('messages.quizzes') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('teacher.quizzes.create', $course) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                {{ __('messages.add') }} {{ __('messages.quiz') }}
            </a>
            <a href="{{ route('teacher.courses.show', $course) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.questions') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.total_marks') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.passing_score') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quizzes as $quiz)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $quiz->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $quiz->questions_count }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $quiz->total_marks }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $quiz->passing_score }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$quiz->is_published ? 'green' : 'yellow'">{{ $quiz->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('teacher.quizzes.results', $quiz) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.results') }}</a>
                                    <a href="{{ route('teacher.quizzes.edit', [$course, $quiz]) }}" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('teacher.quizzes.destroy', [$course, $quiz]) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>