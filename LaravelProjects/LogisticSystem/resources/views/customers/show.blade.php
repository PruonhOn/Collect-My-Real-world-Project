<x-admin-layout>
    @section('title', $customer->name . ' - Customers - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $customer->name }}</li>
    @endsection

    @section('page-title', $customer->name)
    @section('page-subtitle', $customer->code . ' · ' . ($customer->company ?? 'Individual'))

    @section('page-actions')
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Customer
        </a>
    @endsection

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-person text-primary fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                    <span class="badge bg-secondary mb-2">{{ $customer->code }}</span>
                    @if($customer->company)
                        <p class="text-muted small mb-2">{{ $customer->company }}</p>
                    @endif
                    <div>
                        @if($customer->is_active ?? true)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Phone</span>
                        <span class="fw-medium">{{ $customer->phone }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Email</span>
                        <span class="fw-medium">{{ $customer->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Tax Number</span>
                        <span class="fw-medium">{{ $customer->tax_number ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Contact Person</span>
                        <span class="fw-medium">{{ $customer->contact_person ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Contact Phone</span>
                        <span class="fw-medium">{{ $customer->contact_phone ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Address</span>
                        <span class="fw-medium text-end" style="max-width: 180px;">{{ $customer->address ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">City / Province</span>
                        <span class="fw-medium">{{ $customer->city ?? '—' }}{{ $customer->province ? ', ' . $customer->province : '' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Country</span>
                        <span class="fw-medium">{{ $customer->country ?? 'Cambodia' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Credit Limit</span>
                        <span class="fw-medium">${{ number_format($customer->credit_limit ?? 0, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between bg-light">
                        <span class="fw-bold">Outstanding Balance</span>
                        <span class="fw-bold {{ ($customer->outstanding_balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                            ${{ number_format($customer->outstanding_balance ?? 0, 2) }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Shipment History</h5>
                            <span class="badge bg-primary">{{ $customer->shipments->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tracking #</th>
                                            <th>Type</th>
                                            <th>Origin</th>
                                            <th>Destination</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->shipments as $shipment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('shipments.show', $shipment) }}" class="text-decoration-none fw-medium">{{ $shipment->tracking_number }}</a>
                                                </td>
                                                <td>{{ ucfirst($shipment->type) }}</td>
                                                <td>{{ $shipment->origin_city ?? '—' }}</td>
                                                <td>{{ $shipment->destination_city ?? '—' }}</td>
                                                <td>
                                                    @php
                                                        $statusColors = ['pending' => 'warning', 'in_transit' => 'info', 'delivered' => 'success', 'cancelled' => 'danger'];
                                                        $color = $statusColors[$shipment->status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $color }}">{{ str_replace('_', ' ', ucfirst($shipment->status)) }}</span>
                                                </td>
                                                <td>{{ $shipment->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">No shipment history</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Invoice History</h5>
                            <span class="badge bg-primary">{{ $customer->invoices->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->invoices as $invoice)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none">{{ $invoice->invoice_number }}</a>
                                                </td>
                                                <td>${{ number_format($invoice->total ?? $invoice->amount, 2) }}</td>
                                                <td>
                                                    @php
                                                        $colors = ['paid' => 'success', 'pending' => 'warning', 'overdue' => 'danger', 'cancelled' => 'secondary'];
                                                        $color = $colors[$invoice->status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $color }}">{{ ucfirst($invoice->status) }}</span>
                                                </td>
                                                <td>{{ $invoice->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No invoices</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Payment History</h5>
                            <span class="badge bg-primary">{{ $customer->payments->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Reference</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customer->payments as $payment)
                                            <tr>
                                                <td class="fw-medium">{{ $payment->reference ?? '—' }}</td>
                                                <td class="text-success fw-medium">${{ number_format($payment->amount, 2) }}</td>
                                                <td>{{ ucfirst($payment->method ?? '—') }}</td>
                                                <td>{{ $payment->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No payments recorded</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
