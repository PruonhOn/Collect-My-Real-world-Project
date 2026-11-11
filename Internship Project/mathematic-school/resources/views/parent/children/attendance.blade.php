<x-app-layout :title="__('messages.attendance')" :active="'parent.children'">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('parent.children.show', $student) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <span class="text-sm text-gray-500">· {{ __('messages.attendance') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.total') }}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $total }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.present') }}</div>
            <div class="text-2xl font-bold text-green-700 mt-1">{{ $present }}</div>
        </x-card>
        <x-card>
            <div class="text-sm text-gray-500">{{ __('messages.absent') }}</div>
            <div class="text-2xl font-bold text-red-700 mt-1">{{ $absent }}</div>
        </x-card>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.courses') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.lesson') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendance as $record)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $record->course?->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->lesson?->title ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$record->status === 'present' ? 'green' : 'red'">
                                    {{ $record->status === 'present' ? __('messages.present') : __('messages.absent') }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $attendance->links() }}
    </div>
</x-app-layout>