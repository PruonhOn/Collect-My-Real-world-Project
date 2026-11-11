<x-app-layout :title="__('messages.announcements')" :active="'admin.announcements'">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $announcement->title }}</h1>
        <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <x-card>
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
            <div class="flex-1">
                <p class="text-sm text-gray-600">{{ $announcement->user?->name }}</p>
                <p class="text-xs text-gray-400">{{ $announcement->published_at?->format('d/m/Y H:i') ?? $announcement->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <x-badge :color="$announcement->is_published ? 'green' : 'yellow'">{{ $announcement->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
            <x-badge :color="$announcement->audience === 'all' ? 'primary' : 'gray'">{{ __('messages.' . $announcement->audience) }}</x-badge>
        </div>
        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">{!! nl2br(e($announcement->content)) !!}</div>
        <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200 hover:bg-yellow-100 transition">{{ __('messages.edit') }}</a>
            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('{{ __('messages.are_you_sure') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition">{{ __('messages.delete') }}</button>
            </form>
        </div>
    </x-card>
</x-app-layout>