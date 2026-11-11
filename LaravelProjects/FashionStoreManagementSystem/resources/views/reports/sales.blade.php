@extends('layouts.app')

@section('title', 'Sales Report')
@section('heading', 'Sales Report')
@section('subheading', 'Analyse revenue and orders over any period')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="GET" action="{{ route('reports.sales') }}">
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Cashier</label>
                    <select name="cashier_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" @selected($cashierId == $cashier->id)>{{ $cashier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Customer</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($customerId == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Run Report</button>
                </div>
                <div class="col-md-3 col-lg-2">
                    <a href="{{ route('reports.sales.export', request()->query()) }}" class="btn btn-sm btn-outline-success w-100"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-primary"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-value">{{ money($netTotal) }}</div>
                    <div class="stat-label">Net Sales</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-gold"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="stat-value">{{ $orders->count() }}</div>
                    <div class="stat-label">Orders</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-green"><i class="bi bi-bag-check"></i></div>
                <div>
                    <div class="stat-value">{{ $itemCount }}</div>
                    <div class="stat-label">Items Sold</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon stat-rose"><i class="bi bi-arrow-counterclockwise"></i></div>
                <div>
                    <div class="stat-value">{{ money($refundedTotal) }}</div>
                    <div class="stat-label">Refunded</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Daily Revenue</h6></div>
                <div class="card-body">
                    <canvas id="salesChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Payment Methods</h6></div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="paymentChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-transparent"><h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Orders ({{ $orders->count() }})</h6></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Customer</th>
                        <th class="text-center">Items</th>
                        <th>Payment</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Refunded</th>
                        <th class="text-end">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}"><strong>{{ $order->order_number }}</strong></a></td>
                            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $order->user?->name ?? '—' }}</td>
                            <td>{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td class="text-center">{{ $order->item_count }}</td>
                            <td>{{ $order->payment_method?->label() }}</td>
                            <td class="text-end">{{ money($order->total) }}</td>
                            <td class="text-end text-danger">{{ (float) $order->refunded_amount > 0 ? '− '.money($order->refunded_amount) : '—' }}</td>
                            <td class="text-end fw-semibold">{{ money((float) $order->total - (float) $order->refunded_amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state"><i class="bi bi-inbox"></i><p>No orders in this period.</p></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $salesChart = $daily->map(fn ($d) => [
            'day' => \Carbon\Carbon::parse($d->day)->format('M d'),
            'revenue' => round((float) $d->revenue, 2),
        ])->values();

        $paymentChart = $orders->groupBy(fn ($o) => $o->payment_method?->label() ?? 'Other')
            ->map(fn ($group) => round((float) $group->sum('total') - (float) $group->sum('refunded_amount'), 2))
            ->all();
    @endphp
    <script>
        window.SalesChartData = @json($salesChart);
        window.PaymentChartData = @json($paymentChart);
    </script>
    @vite('resources/js/reports.js')
@endpush
