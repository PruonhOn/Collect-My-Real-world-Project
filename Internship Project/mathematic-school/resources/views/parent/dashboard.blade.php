<x-app-layout :title="__('messages.dashboard')" :active="'parent.dashboard'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center gap-4 mb-6">
        <div class="w-14 h-14 rounded-2xl bg-primary-600 text-white flex items-center justify-center text-xl font-bold">
            {{ strtoupper(mb_substr($parent->full_name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.welcome_back') }}, {{ $parent->full_name }}</h1>
            <p class="text-sm text-gray-500">{{ __('messages.parents') }} · {{ $children->count() }} {{ __('messages.children') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($children as $index => $child)
            <x-card>
                <div class="text-sm text-gray-500 mb-1">{{ __('messages.child') }} {{ $index + 1 }}</div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold">
                        {{ strtoupper(mb_substr($child->full_name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-800 truncate">{{ $child->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $child->student_id }} · {{ $child->age }} {{ __('messages.age') }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="text-gray-500">{{ __('messages.progress') }}</span>
                    <span class="font-semibold text-primary-600">{{ $child->overall_progress }}%</span>
                </div>
                <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden mb-4">
                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $child->overall_progress }}%"></div>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-gray-500">{{ $child->enrollments->count() }} {{ __('messages.my_courses') }}</span>
                    <a href="{{ route('parent.children.show', $child) }}" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold bg-primary-600 text-white hover:bg-primary-700">
                        {{ __('messages.view') }}
                    </a>
                </div>
            </x-card>
        @empty
            <div class="col-span-full">
                <x-card>
                    <div class="text-center py-12 text-gray-500">{{ __('messages.no_children') }}</div>
                </x-card>
            </div>
        @endforelse
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
        <x-card>
            <h3 class="font-semibold text-lg mb-4">{{ __('messages.my_children') }}</h3>
            <div class="space-y-3">
                @forelse ($children as $child)
                    <a href="{{ route('parent.children.show', $child) }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition">
                        <span class="text-sm font-medium">{{ $child->full_name }}</span>
                        <span class="text-xs text-gray-500">{{ __('messages.view') }} →</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('messages.no_children') }}</p>
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h3 class="font-semibold text-lg mb-4">{{ __('messages.notifications') }}</h3>
            <div class="space-y-3">
                @forelse (auth()->user()->notifications->take(6) as $notification)
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="text-sm font-medium text-gray-800">{{ $notification->data['title'] ?? '' }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $notification->data['message'] ?? '' }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('messages.no_notifications') }}</p>
                @endforelse
                <a href="{{ route('notifications.index') }}" class="inline-block text-sm text-primary-600 font-semibold hover:underline">{{ __('messages.view_all') }}</a>
            </div>
        </x-card>
    </div>
</x-app-layout>