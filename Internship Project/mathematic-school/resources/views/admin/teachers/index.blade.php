<x-app-layout :title="__('messages.teachers')" :active="'admin.teachers'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.teachers') }}</h1>
        <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.add') }} {{ __('messages.teachers') }}
        </a>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.teachers.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="flex-1 rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <select name="status" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('messages.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.inactive') }}</option>
                    <option value="on_leave" @selected(request('status') === 'on_leave')">{{ __('messages.pending') }}</option>
                </select>
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.full_name') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.phone') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.email') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">ជំនាញ</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.courses') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $teacher->full_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $teacher->phone }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $teacher->email }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $teacher->specialization }}</td>
                            <td class="px-4 py-3">{{ $teacher->courses->count() }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$teacher->status === 'active' ? 'green' : 'red'">
                                    {{ $teacher->status === 'active' ? __('messages.active') : ($teacher->status === 'inactive' ? __('messages.inactive') : __('messages.pending')) }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('admin.teachers.toggle', $teacher) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            {{ $teacher->status === 'active' ? __('messages.inactive') : __('messages.active') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $teachers->links() }}</div>
    </x-card>
</x-app-layout>
