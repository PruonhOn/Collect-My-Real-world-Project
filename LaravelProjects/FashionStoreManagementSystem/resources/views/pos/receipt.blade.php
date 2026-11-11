@extends('layouts.pos')

@section('title', 'Receipt '.$order->order_number)

@section('content')
    <div class="receipt-page">
        <div class="receipt-actions no-print mb-3 d-flex gap-2 justify-content-center">
            <button class="btn btn-primary rounded-pill px-4" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Receipt
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                <i class="bi bi-shop me-1"></i> New Sale
            </a>
            <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-receipt me-1"></i> View Order
            </a>
        </div>

        <div class="receipt" id="receipt">
            <div class="receipt-store text-center">
                @if (store_logo_url())
                    <img src="{{ store_logo_url() }}" class="receipt-logo mb-1" alt="">
                @else
                    <div class="receipt-icon"><i class="bi bi-bag-heart-fill"></i></div>
                @endif
                <h1 class="receipt-store-name">{{ store_name() }}</h1>
                @if ($appSettings['store_address'])
                    <div>{{ $appSettings['store_address'] }}</div>
                @endif
                @if ($appSettings['store_phone'])
                    <div>{{ $appSettings['store_phone'] }}</div>
                @endif
            </div>

            <div class="receipt-meta">
                <div class="d-flex justify-content-between"><span>Order</span><strong>{{ $order->order_number }}</strong></div>
                <div class="d-flex justify-content-between"><span>Date</span><span>{{ $order->created_at->format('d M Y, H:i') }}</span></div>
                <div class="d-flex justify-content-between"><span>Cashier</span><span>{{ $order->user?->name ?? '—' }}</span></div>
                <div class="d-flex justify-content-between"><span>Customer</span><span>{{ $order->customer?->name ?? 'Walk-in' }}</span></div>
                <div class="d-flex justify-content-between"><span>Payment</span><span>{{ $order->payment_method?->label() }}</span></div>
            </div>

            <hr class="receipt-divider">

            <div class="receipt-items">
                @foreach ($order->items as $item)
                    <div class="receipt-item">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $item->product_name }}</strong>
                            <span>{{ money($item->line_total) }}</span>
                        </div>
                        <div class="receipt-item-sub">
                            {{ $item->variant_label ?: 'Default' }} · {{ $item->sku }}
                            · {{ $item->quantity }} × {{ money($item->unit_price) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <hr class="receipt-divider">

            <div class="receipt-totals">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                @if ((float) $order->discount_amount > 0)
                    <div class="d-flex justify-content-between"><span>Discount</span><span>− {{ money($order->discount_amount) }}</span></div>
                @endif
                <div class="d-flex justify-content-between"><span>Tax ({{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%)</span><span>{{ money($order->tax_amount) }}</span></div>
                <div class="d-flex justify-content-between receipt-total"><span>Total</span><strong>{{ money($order->total) }}</strong></div>
                @if ($order->amount_tendered !== null)
                    <div class="d-flex justify-content-between"><span>Tendered</span><span>{{ money($order->amount_tendered) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Change</span><span>{{ money($order->change_due) }}</span></div>
                @endif
                @if ((float) $order->refunded_amount > 0)
                    <div class="d-flex justify-content-between text-danger"><span>Refunded</span><span>{{ money($order->refunded_amount) }}</span></div>
                @endif
            </div>

            <div class="receipt-footer text-center">
                {{ $appSettings['receipt_footer'] ?? 'Thank you for shopping with us!' }}
            </div>
        </div>
    </div>
@endsection
