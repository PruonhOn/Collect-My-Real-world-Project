<x-app-layout :title="__('messages.payments')" :active="'parent.children'">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('parent.children.show', $student) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('messages.back') }}</a>
            <h1 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h1>
            <span class="text-sm text-gray-500">· {{ __('messages.payments') }}</span>
        </div>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.courses') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.amount') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.method') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.transaction_id') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $payment->course?->title ?? '—' }}</td>
                            <td class="px-4 py-3">${{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->method ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->transaction_id ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $color = match ($payment->status) {
                                        'paid' => 'green',
                                        'pending' => 'yellow',
                                        'refunded' => 'blue',
                                        default => 'red',
                                    };
                                @endphp
                                <x-badge :color="$color">{{ payment_status_label($payment->status) }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->paid_date?->format('d/m/Y') ?? $payment->created_at?->format('d/m/Y') }}</td>
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
        {{ $payments->links() }}
    </div>
</x-app-layout>