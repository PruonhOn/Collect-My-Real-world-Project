<x-app-layout :title="__('messages.my_children')" :active="'parent.children'">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ __('messages.my_children') }}</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($children as $index => $child)
            <x-card>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-lg font-bold">
                        {{ strtoupper(mb_substr($child->full_name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-semibold text-gray-800">{{ $child->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $child->student_id }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center mb-4">
                    <div class="p-2 rounded-xl bg-gray-50">
                        <div class="text-lg font-bold text-gray-800">{{ $child->age }}</div>
                        <div class="text-xs text-gray-500">{{ __('messages.age') }}</div>
                    </div>
                    <div class="p-2 rounded-xl bg-gray-50">
                        <div class="text-lg font-bold text-gray-800">{{ $child->enrollments->count() }}</div>
                        <div class="text-xs text-gray-500">{{ __('messages.my_courses') }}</div>
                    </div>
                    <div class="p-2 rounded-xl bg-green-50">
                        <div class="text-lg font-bold text-green-700">{{ $child->overall_progress }}%</div>
                        <div class="text-xs text-gray-500">{{ __('messages.progress') }}</div>
                    </div>
                </div>

                <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-5">
                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $child->overall_progress }}%"></div>
                </div>

                <a href="{{ route('parent.children.show', $child) }}" class="block text-center px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
                    {{ __('messages.view') }}
                </a>
            </x-card>
        @empty
            <div class="col-span-full">
                <x-card>
                    <div class="text-center py-12 text-gray-500">{{ __('messages.no_children') }}</div>
                </x-card>
            </div>
        @endforelse
    </div>
</x-app-layout>