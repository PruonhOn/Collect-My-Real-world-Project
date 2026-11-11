@extends('layouts.app')

@section('title', 'Orders & Sales')
@section('heading', 'Orders & Sales')
@section('subheading', auth()->user()->isAdmin() || auth()->user()->isManager() ? 'All sales across the store' : 'Your sales history')

@section('content')
    <div class="card">
        <div class="card-header bg-transparent">
            <form class="row g-2 align-items-end" method="GET" action="{{ route('orders.index') }}">
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Order #</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="POS-...">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') == $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small mb-1">Cashier</label>
                        <select name="cashier_id" class="form-select form-select-sm">
                            <option value="">All cashiers</option>
                            @foreach ($cashiers as $cashier)
                                <option value="{{ $cashier->id }}" @selected(request('cashier_id') == $cashier->id)>{{ $cashier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small mb-1">Customer</label>
                        <select name="customer_id" class="form-select form-select-sm">
                            <option value="">All customers</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-lg-1">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i></button>
                </div>
                @if (request()->hasAny(['q', 'status', 'cashier_id', 'customer_id', 'date_from', 'date_to']))
                    <div class="col-md-3 col-lg-1">
                        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
                    </div>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Customer</th>
                        @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                            <th>Cashier</th>
                        @endif
                        <th>Payment</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ $order->created_at->format('d M Y, H:i') }}</td>
                            <td>{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            @if (auth()->user()->isAdmin() || auth()->user()->isManager())
                                <td>{{ $order->user?->name ?? '—' }}</td>
                            @endif
                            <td>
                                @if ($order->payment_method?->value === 'cash')
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-cash me-1"></i>Cash</span>
                                @elseif ($order->payment_method?->value === 'card')
                                    <span class="badge bg-info-subtle text-info"><i class="bi bi-credit-card me-1"></i>Card</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-qr-code me-1"></i>QR</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $order->item_count }}</td>
                            <td class="text-end fw-semibold">{{ money($order->total) }}</td>
                            <td>
                                <span class="badge {{ $order->status->value === 'refunded' ? 'bg-danger-subtle text-danger' : ($order->status->value === 'partially_refunded' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success') }}">
                                    {{ $order->status->label() }}
                                </span>
                                @if ((float) $order->refunded_amount > 0)
                                    <div class="small text-danger mt-1">refunded {{ money($order->refunded_amount) }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-soft"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('pos.receipt', $order) }}" target="_blank" class="btn btn-sm btn-soft" title="Receipt"><i class="bi bi-receipt"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="bi bi-receipt"></i>
                                    <p>No orders found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $orders->links() }}</div>
    </div>
@endsection
