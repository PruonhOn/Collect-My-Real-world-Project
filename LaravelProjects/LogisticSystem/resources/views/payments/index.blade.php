@extends('layouts.admin')

@section('title', 'Payments - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active" aria-current="page">Payments</li>
@endsection

@section('page-title', 'Payments')
@section('page-subtitle', 'All payment records')

@section('page-actions')
    <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Record Payment
    </a>
@endsection

@section('content')
    <div class="table-container">
        <table class="table table-hover datatable" id="paymentsTable">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $payment->payment_number ?? $payment->id }}</span></td>
                        <td>
                            <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">
                                {{ $payment->invoice?->invoice_number ?? '—' }}
                            </a>
                        </td>
                        <td>{{ $payment->customer?->name ?? $payment->invoice?->customer?->name ?? '—' }}</td>
                        <td class="fw-medium">${{ number_format($payment->amount ?? 0, 2) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method ?? '')) }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-cash-coin fs-2 d-block mb-2"></i>
                            <p class="mb-0">No payments recorded yet</p>
                            <a href="{{ route('payments.create') }}" class="btn btn-sm btn-primary mt-2">Record Payment</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#paymentsTable').DataTable({
                order: [[5, 'desc']],
                pageLength: 25,
                responsive: true,
                language: { search: '', searchPlaceholder: 'Search payments...' }
            });
        });
    </script>
@endpush
