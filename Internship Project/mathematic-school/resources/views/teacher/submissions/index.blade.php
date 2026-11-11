<x-app-layout :title="__('messages.submissions')" :active="'teacher.submissions'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.submissions') }}</h1>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('teacher.submissions.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="flex-1 rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <select name="status" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.status') }}</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>{{ __('messages.submitted') }}</option>
                    <option value="graded" @selected(request('status') === 'graded')>{{ __('messages.graded') }}</option>
                </select>
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                <a href="{{ route('teacher.submissions.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.assignment') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.submitted_at') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.grade') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $submission)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $submission->student?->full_name }}</td>
                            <td class="px-4 py-3">{{ $submission->assignment?->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $submission->assignment?->course?->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $submission->submitted_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                @if ($submission->grade !== null)
                                    <span class="font-semibold text-green-700">{{ $submission->grade }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('teacher.submissions.grade', $submission) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ $submission->status === 'graded' ? __('messages.view') : __('messages.grade') }}</a>
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

        <div class="p-4">
            {{ $submissions->links() }}
        </div>
    </x-card>
</x-app-layout>