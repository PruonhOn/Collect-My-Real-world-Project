<x-app-layout :title="__('messages.roles')" :active="'admin.roles'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.roles') }}</h1>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse ($roles as $role)
            <x-card>
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm uppercase">{{ substr($role->name, 0, 2) }}</div>
                    <x-badge color="gray">{{ $role->permissions_count }} {{ __('messages.permissions') }}</x-badge>
                </div>
                <div class="text-lg font-bold text-gray-800 capitalize">{{ $role->name }}</div>
                <div class="flex items-center justify-between mt-4">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-sm text-primary-600 hover:underline font-medium">{{ __('messages.edit') }}</a>
                </div>
            </x-card>
        @empty
            <x-card class="col-span-full">
                <div class="text-center py-8 text-gray-500">{{ __('messages.no_records') }}</div>
            </x-card>
        @endforelse
    </div>
</x-app-layout>