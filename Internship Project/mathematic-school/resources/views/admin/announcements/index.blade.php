<x-app-layout :title="__('messages.announcements')" :active="'admin.announcements'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.announcements') }}</h1>
        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            {{ __('messages.add') }} {{ __('messages.announcements') }}
        </a>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.announcements.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="flex-1 rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <select name="audience" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.audience') }}</option>
                    <option value="all" @selected(request('audience') === 'all')>{{ __('messages.all') }}</option>
                    <option value="students" @selected(request('audience') === 'students')>{{ __('messages.students') }}</option>
                    <option value="parents" @selected(request('audience') === 'parents')>{{ __('messages.parents') }}</option>
                    <option value="teachers" @selected(request('audience') === 'teachers')>{{ __('messages.teachers') }}</option>
                </select>
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.audience') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.author') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $announcement->title }}</td>
                            <td class="px-4 py-3"><x-badge :color="$announcement->audience === 'all' ? 'primary' : 'gray'">{{ __('messages.' . $announcement->audience) }}</x-badge></td>
                            <td class="px-4 py-3 text-gray-500">{{ $announcement->user?->name }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$announcement->is_published ? 'green' : 'yellow'">{{ $announcement->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $announcement->published_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.announcements.show', $announcement) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
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

        <div class="p-4">
            {{ $announcements->links() }}
        </div>
    </x-card>
</x-app-layout>