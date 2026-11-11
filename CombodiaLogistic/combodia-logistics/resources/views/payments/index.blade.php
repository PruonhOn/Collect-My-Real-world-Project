@extends('layouts.admin')

@section('title', 'Payments')
@section('subtitle', 'Manage all payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Payments</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Payments</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('payments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Record Payment
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Payment #</th>
                        <th>Customer</th>
                        <th>Invoice #</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Received By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td><span class="fw-semibold">{{ $payment->payment_number }}</span></td>
                        <td>{{ $payment->customer->name ?? 'N/A' }}</td>
                        <td>
                            @if($payment->invoice)
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">{{ $payment->invoice->invoice_number }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="fw-semibold">${{ number_format($payment->amount, 2) }}</td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span></td>
                        <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '-' }}</td>
                        <td>
                            @switch($payment->status)
                                @case('completed')
                                    <span class="badge bg-success">Completed</span>
                                    @break
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                                @case('failed')
                                    <span class="badge bg-danger">Failed</span>
                                    @break
                                @case('refunded')
                                    <span class="badge bg-info">Refunded</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $payment->receivedBy->name ?? 'N/A' }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('payments.show', $payment) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('payments.print-receipt', $payment) }}" target="_blank"><i class="bi bi-printer me-2"></i>Print Receipt</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No payments found</p>
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
