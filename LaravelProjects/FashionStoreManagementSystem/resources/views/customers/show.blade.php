@extends('layouts.app')

@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading', 'Customer profile & purchase history')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="avatar avatar-xl mx-auto mb-3">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                    <h5 class="mb-1">{{ $customer->name }}</h5>
                    <div class="text-muted small mb-3">
                        @if ($customer->email)<div><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</div>@endif
                        @if ($customer->phone)<div><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</div>@endif
                        @if ($customer->address)<div><i class="bi bi-geo-alt me-1"></i>{{ $customer->address }}</div>@endif
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="detail-stat"><span class="detail-label">Total Spent</span><strong>{{ money($totalSpent) }}</strong></div>
                        </div>
                        <div class="col-6">
                            <div class="detail-stat"><span class="detail-label">Loyalty Points</span><strong class="text-warning">{{ $customer->loyalty_points }}</strong></div>
                        </div>
                    </div>
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary rounded-pill mt-3"><i class="bi bi-pencil me-1"></i>Edit Profile</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Purchases</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Cashier</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customer->orders as $order)
                                <tr>
                                    <td><strong>{{ $order->order_number }}</strong></td>
                                    <td>{{ $order->created_at->format('d M Y, H:i') }}</td>
                                    <td>{{ $order->user?->name ?? '—' }}</td>
                                    <td class="text-end">{{ money($order->total) }}</td>
                                    <td>
                                        <span class="badge {{ $order->status->value === 'refunded' ? 'bg-danger-subtle text-danger' : ($order->status->value === 'partially_refunded' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success') }}">
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-soft"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="bi bi-bag"></i>
                                            <p>No purchases yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
