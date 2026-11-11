<x-app-layout :title="__('messages.payments')" :active="'admin.payments'">
    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 mb-6">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('messages.payments') }}</h1>
        <a href="{{ route('admin.payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">
            {{ __('messages.add') }} {{ __('messages.payment') }}
        </a>
    </div>

    <x-card :padding="false">
        <div class="p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..." class="flex-1 rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm">
                <select name="status" class="rounded-xl border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm bg-white">
                    <option value="">{{ __('messages.all') }} {{ __('messages.status') }}</option>
                    <option value="pending" @selected(request('status') === 'pending')>{{ __('messages.pending') }}</option>
                    <option value="paid" @selected(request('status') === 'paid')>{{ __('messages.paid') }}</option>
                    <option value="failed" @selected(request('status') === 'failed')>{{ __('messages.failed') }}</option>
                    <option value="refunded" @selected(request('status') === 'refunded')>{{ __('messages.refunded') }}</option>
                </select>
                <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 transition">{{ __('messages.search') }}</button>
                <a href="{{ route('admin.payments.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition text-center">{{ __('messages.reset') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.student') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.course') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.amount') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.method') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.transaction_id') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.date') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $payment->student?->full_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->course?->title }}</td>
                            <td class="px-4 py-3 font-semibold">${{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->method ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $payment->transaction_id ?? '—' }}</td>
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
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">{{ __('messages.view') }}</a>
                                    <a href="{{ route('admin.payments.edit', $payment) }}" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">{{ __('messages.edit') }}</a>
                                    <form method="POST" action="{{ route('admin.payments.status', $payment) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="paid">
                                        @if ($payment->status !== 'paid')
                                            <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">{{ __('messages.mark_paid') }}</button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('messages.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $payments->links() }}
        </div>
    </x-card>
</x-app-layout>