<x-app-layout :title="__('messages.grades')" :active="'student.grades'">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ __('messages.grades') }}</h1>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.courses') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.type') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.title') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.score') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.grade') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grades as $grade)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $grade->course?->title ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$grade->type === 'quiz' ? 'blue' : 'yellow'">
                                    {{ $grade->type === 'quiz' ? __('messages.quiz') : __('messages.assignment') }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $grade->type === 'quiz' ? ($grade->quiz?->title ?? '—') : ($grade->assignment?->title ?? '—') }}
                            </td>
                            <td class="px-4 py-3">{{ $grade->score }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold {{ $grade->percentage >= 50 ? 'text-green-700' : 'text-red-700' }}">{{ $grade->percentage }}%</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $grade->created_at?->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-6">
        {{ $grades->links() }}
    </div>
</x-app-layout>