<x-app-layout :title="__('messages.enrollment')" :active="'admin.enrollments'">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.enrollment') }} #{{ $enrollment->id }}</h1>
        <a href="{{ route('admin.enrollments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card :padding="false">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.lesson_progress') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.lesson') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.time_spent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($enrollment->lessonProgress as $progress)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium">{{ $progress->lesson?->title }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :color="$progress->is_completed ? 'green' : 'gray'">{{ $progress->is_completed ? __('messages.completed') : __('messages.in_progress') }}</x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ floor($progress->learning_time_seconds / 60) }} {{ __('messages.minutes') }}</td>
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
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.student') }}</dt><dd class="font-medium">{{ $enrollment->student?->full_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.course') }}</dt><dd class="font-medium">{{ $enrollment->course?->title }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.enrollment_date') }}</dt><dd class="font-medium">{{ $enrollment->enrolled_at?->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.status') }}</dt><dd><x-badge color="gray">{{ enrollment_status_label($enrollment->status) }}</x-badge></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.progress_percent') }}</dt><dd class="font-medium text-primary-600 font-semibold">{{ round($enrollment->completion_percentage) }}%</dd></div>
                </dl>
            </x-card>

            <x-card>
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.payment') }}</h3>
                @if ($enrollment->payment)
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.amount') }}</dt><dd class="font-medium">${{ number_format($enrollment->payment->amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.status') }}</dt><dd><x-badge :color="$enrollment->payment->status === 'paid' ? 'green' : 'yellow'">{{ payment_status_label($enrollment->payment->status) }}</x-badge></dd></div>
                    </dl>
                @else
                    <p class="text-sm text-gray-500">{{ __('messages.no_payment') }}</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>