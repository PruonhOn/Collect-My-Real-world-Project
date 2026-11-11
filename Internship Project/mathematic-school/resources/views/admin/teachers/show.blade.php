<x-app-layout :title="__('messages.details') . ' ' . __('messages.teachers')" :active="'admin.teachers'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $teacher->full_name }}</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="px-4 py-2 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.edit') }}</a>
            <a href="{{ route('admin.teachers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card>
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.personal_info') }}</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.full_name') }}</span><span>{{ $teacher->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.phone') }}</span><span>{{ $teacher->phone }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.email') }}</span><span>{{ $teacher->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.address') }}</span><span>{{ $teacher->address }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">ជំនាញ</span><span>{{ $teacher->specialization }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">វិញ្ញាបនបត្រ</span><span>{{ $teacher->qualification }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('messages.status') }}</span>
                    <x-badge :color="$teacher->status === 'active' ? 'green' : 'red'">
                        {{ $teacher->status === 'active' ? __('messages.active') : __('messages.inactive') }}
                    </x-badge>
                </div>
            </div>
        </x-card>

        <x-card class="lg:col-span-2">
            <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.courses') }}</h3>
            @forelse ($teacher->courses as $course)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl mb-2">
                    <div>
                        <div class="font-medium text-sm">{{ $course->title }}</div>
                        <div class="text-xs text-gray-500">{{ $course->course_code }}</div>
                    </div>
                    <x-badge :color="$course->status === 'published' ? 'green' : ($course->status === 'draft' ? 'yellow' : 'gray')">
                        {{ course_status_label($course->status) }}
                    </x-badge>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('messages.no_data') }}</p>
            @endforelse
        </x-card>
    </div>
</x-app-layout>
