<x-app-layout :title="__('messages.notifications')" :active="'notifications'">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">{{ __('messages.notifications') }}</h2>
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <x-button type="secondary" submit>{{ __('messages.mark_all_read') }}</x-button>
            </form>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                <x-card :padding="false">
                    <div class="p-4 flex gap-4 {{ $notification->read_at ? '' : 'bg-primary-50' }}">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">{{ $notification->data['title'] ?? __('messages.notification') }}</div>
                            <div class="text-sm text-gray-600 mt-1">{{ $notification->data['message'] ?? '' }}</div>
                            <div class="text-xs text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-card>
                    <div class="text-center text-gray-500 py-8">{{ __('messages.no_notifications') }}</div>
                </x-card>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
