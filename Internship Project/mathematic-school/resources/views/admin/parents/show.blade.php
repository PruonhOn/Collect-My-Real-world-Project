<x-app-layout :title="__('messages.details') . ' ' . __('messages.parents')" :active="'admin.parents'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $parent->full_name }}</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.parents.edit', $parent) }}" class="px-4 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.edit') }}</a>
            <a href="{{ route('admin.parents.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card>
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.personal_info') }}</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.full_name') }}</span><span>{{ $parent->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.phone') }}</span><span>{{ $parent->phone }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.email') }}</span><span>{{ $parent->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.address') }}</span><span>{{ $parent->address }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">មុខរបរ</span><span>{{ $parent->occupation }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.status') }}</span>
                    <x-badge :color="$parent->is_active ? 'green' : 'red'">
                        {{ $parent->is_active ? __('messages.active') : __('messages.inactive') }}
                    </x-badge>
                </div>
            </div>
        </x-card>

        <x-card class="lg:col-span-2">
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.children') }}</h3>
            @forelse ($parent->children as $child)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl mb-2">
                    <div>
                        <div class="font-medium text-sm">{{ $child->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $child->student_id }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        @foreach ($child->courses as $course)
                            <x-badge color="blue">{{ $course->title }}</x-badge>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('messages.no_data') }}</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
