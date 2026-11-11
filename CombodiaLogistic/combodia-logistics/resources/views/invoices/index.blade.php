@extends('layouts.admin')

@section('title', 'Invoices')
@section('subtitle', 'Manage all invoices')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Invoices</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Invoices</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Create Invoice
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Shipment #</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold text-decoration-none">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                        <td><a href="{{ route('shipments.show', $invoice->shipment) }}" class="text-decoration-none">{{ $invoice->shipment->shipment_number ?? 'N/A' }}</a></td>
                        <td>${{ number_format($invoice->total_amount, 2) }}</td>
                        <td>${{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                        <td>
                            @php $balance = $invoice->total_amount - ($invoice->paid_amount ?? 0); @endphp
                            <span class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">${{ number_format($balance, 2) }}</span>
                        </td>
                        <td>
                            @switch($invoice->status)
                                @case('draft')
                                    <span class="badge bg-secondary">Draft</span>
                                    @break
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('paid')
                                    <span class="badge bg-success">Paid</span>
                                    @break
                                @case('overdue')
                                    <span class="badge bg-danger">Overdue</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                    @break
                            @endswitch
                        </td>
                        <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : '-' }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('invoices.show', $invoice) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    @if($invoice->status === 'draft')
                                    <li><a class="dropdown-item" href="{{ route('invoices.edit', $invoice) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endif
                                    <li><a class="dropdown-item" href="{{ route('invoices.print', $invoice) }}" target="_blank"><i class="bi bi-printer me-2"></i>Print</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No invoices found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });
</script>
@endpush
