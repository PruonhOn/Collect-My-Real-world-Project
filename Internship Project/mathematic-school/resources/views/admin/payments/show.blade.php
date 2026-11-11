<x-app-layout :title="__('messages.payment')" :active="'admin.payments'">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.payment') }} #{{ $payment->id }}</h1>
        <a href="{{ route('admin.payments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('messages.back') }}</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-800">{{ __('messages.payment_details') }}</h3>
                    @php
                        $color = match ($payment->status) {
                            'paid' => 'green',
                            'pending' => 'yellow',
                            'refunded' => 'blue',
                            default => 'red',
                        };
                    @endphp
                    <x-badge :color="$color">{{ payment_status_label($payment->status) }}</x-badge>
                </div>
                <dl class="space-y-4 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.student') }}</dt><dd class="font-medium">{{ $payment->student?->full_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.course') }}</dt><dd class="font-medium">{{ $payment->course?->title }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.parent') }}</dt><dd class="font-medium">{{ $payment->parentGuardian?->full_name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.amount') }}</dt><dd class="font-semibold text-lg">${{ number_format($payment->amount, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.method') }}</dt><dd class="font-medium">{{ $payment->method ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.transaction_id') }}</dt><dd class="font-mono text-xs">{{ $payment->transaction_id ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ __('messages.paid_date') }}</dt><dd class="font-medium">{{ $payment->paid_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    @if ($payment->notes)
                        <div class="border-t border-gray-100 pt-4"><dt class="text-gray-500 mb-1">{{ __('messages.notes') }}</dt><dd class="text-gray-700">{{ $payment->notes }}</dd></div>
                    @endif
                </dl>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card>
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('messages.update_status') }}</h3>
                <form method="POST" action="{{ route('admin.payments.status', $payment) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <x-select name="status" :label="__('messages.status')">
                        <option value="pending" @selected($payment->status === 'pending')>{{ __('messages.pending') }}</option>
                        <option value="paid" @selected($payment->status === 'paid')>{{ __('messages.paid') }}</option>
                        <option value="failed" @selected($payment->status === 'failed')>{{ __('messages.failed') }}</option>
                        <option value="refunded" @selected($payment->status === 'refunded')>{{ __('messages.refunded') }}</option>
                    </x-select>
                    <x-button type="primary" :submit="true">{{ __('messages.save') }}</x-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>