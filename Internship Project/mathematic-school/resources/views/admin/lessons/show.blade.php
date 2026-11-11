<x-app-layout :title="__('messages.lesson')" :active="'admin.lessons'">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-800">{{ $lesson->title }}</h1>
            <x-badge :color="$lesson->is_published ? 'green' : 'yellow'">{{ $lesson->is_published ? __('messages.published') : __('messages.draft') }}</x-badge>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.lessons.edit', $lesson) }}" class="px-4 py-2 rounded-xl text-sm font-semibold bg-yellow-50 text-yellow-700 border border-yellow-200 hover:bg-yellow-100 transition">{{ __('messages.edit') }}</a>
            <a href="{{ route('admin.lessons.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('messages.course') }}</h3>
                <p class="text-primary-600 font-medium">{{ $lesson->course?->title }}</p>
                @if ($lesson->description)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.description') }}</h4>
                    <p class="text-gray-600 leading-relaxed">{{ $lesson->description }}</p>
                @endif
                @if ($lesson->video_url)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.video') }}</h4>
                    <a href="{{ $lesson->video_url }}" target="_blank" class="text-primary-600 text-sm hover:underline">{{ $lesson->video_url }}</a>
                @endif
                @if ($lesson->video_path)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.video_file') }}</h4>
                    <video controls class="w-full rounded-xl border border-gray-200">
                        <source src="{{ asset('storage/' . $lesson->video_path) }}" type="video/mp4">
                    </video>
                @endif
                @if ($lesson->pdf_path)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.pdf_file') }}</h4>
                    <a href="{{ asset('storage/' . $lesson->pdf_path) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 text-red-700 border border-red-200 text-sm font-semibold hover:bg-red-100 transition">
                        {{ __('messages.download') }}
                    </a>
                @endif
                @if ($lesson->external_resources)
                    <h4 class="font-semibold text-gray-800 mt-6 mb-2">{{ __('messages.external_resources') }}</h4>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ $lesson->external_resources }}</p>
                @endif
            </x-card>

            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.materials') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.type') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.download') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lesson->materials as $material)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium">{{ $material->title }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $material->type }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" class="text-primary-600 text-xs font-medium">{{ __('messages.download') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.order') }}</dt><dd class="font-medium">{{ $lesson->order }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.duration') }}</dt><dd class="font-medium">{{ $lesson->duration }} {{ __('messages.minutes') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.price') }}</dt><dd class="font-medium">{{ $lesson->is_free ? __('messages.free') : __('messages.paid') }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>