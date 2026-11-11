<x-app-layout :title="__('messages.students')" :active="'teacher.students'">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $course->title }}</h1>
            <p class="text-gray-500 text-sm">{{ __('messages.students') }}</p>
        </div>
        <a href="{{ route('teacher.courses.show', $course) }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student_id') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.full_name') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.enrollment_date') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.progress_percent') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.payment') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $enrollment->student?->student_id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $enrollment->student?->full_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $enrollment->enrolled_at?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ min(100, round($enrollment->completion_percentage)) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ round($enrollment->completion_percentage) }}%</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($enrollment->payment)
                                    <x-badge :color="$enrollment->payment->status === 'paid' ? 'green' : 'yellow'">{{ payment_status_label($enrollment->payment->status) }}</x-badge>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('teacher.students.show', $enrollment->student) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $enrollments->links() }}
        </div>
    </x-card>
</x-app-layout>