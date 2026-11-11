@extends('layouts.admin')

@section('title', 'Customer Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Customer Details</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </a>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <x-breadcrumb :items="[
        ['label' => 'Customers', 'url' => route('admin.customers.index')],
        ['label' => 'Customer Details']
    ]" />

    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                        <p class="fw-medium mb-0">{{ $customer->code }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Name</label>
                        <p class="fw-medium mb-0">{{ $customer->name }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Company Name</label>
                        <p class="fw-medium mb-0">{{ $customer->company_name ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Email</label>
                        <p class="fw-medium mb-0">{{ $customer->email ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Phone</label>
                        <p class="fw-medium mb-0">{{ $customer->phone ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Type</label>
                        <p class="fw-medium mb-0">
                            <span class="badge bg-info">{{ ucfirst($customer->type) }}</span>
                        </p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Balance</label>
                        <p class="fw-medium mb-0">${{ number_format($customer->balance ?? 0, 2) }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                        <p class="mb-0">
                            @if($customer->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Addresses Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Addresses</h6>
                    <span class="badge bg-primary">{{ $customerAddresses->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th>Province</th>
                                    <th>Country</th>
                                    <th>Default</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customerAddresses as $address)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ ucfirst($address->type ?? '-') }}</td>
                                        <td>{{ $address->address ?? '-' }}</td>
                                        <td>{{ $address->city ?? '-' }}</td>
                                        <td>{{ $address->province ?? '-' }}</td>
                                        <td>{{ $address->country ?? '-' }}</td>
                                        <td>
                                            @if($address->is_default ?? false)
                                                <span class="badge bg-success">Default</span>
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No addresses found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Shipments Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Shipments</h6>
                    <span class="badge bg-primary">{{ $shipments->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Weight (kg)</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments as $shipment)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $shipment->code }}</td>
                                        <td>{{ $shipment->origin ?? '-' }}</td>
                                        <td>{{ $shipment->destination ?? '-' }}</td>
                                        <td>{{ $shipment->weight ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $shipment->status === 'delivered' ? 'success' : ($shipment->status === 'in_transit' ? 'info' : ($shipment->status === 'pending' ? 'warning' : 'secondary')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $shipment->created_at?->format('d M Y') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No shipments found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Invoices Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Invoices</h6>
                    <span class="badge bg-primary">{{ $invoices->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $invoice->code }}</td>
                                        <td>${{ number_format($invoice->amount ?? 0, 2) }}</td>
                                        <td>${{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                                        <td>${{ number_format($invoice->balance ?? 0, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $invoice->created_at?->format('d M Y') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No invoices found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
