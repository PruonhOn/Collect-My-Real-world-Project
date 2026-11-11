@extends('layouts.admin')

@section('title', 'Invoices - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Invoices</li>
@endsection

@section('page-title', 'Invoices')
@section('page-subtitle', 'Manage customer invoices')

@section('page-actions')
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Invoice
    </a>
@endsection

@section('content')
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="invoicesTable">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ route('invoices.show', $invoice) }}" class="text-decoration-none fw-medium">
                                    #{{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td>
                                @if($invoice->customer)
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="bi bi-person text-primary"></i>
                                        </div>
                                        {{ $invoice->customer->name }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <i class="bi bi-calendar3 me-1 text-muted"></i>
                                {{ $invoice->invoice_date->format('d M Y') }}
                            </td>
                            <td class="fw-medium">${{ number_format($invoice->total, 2) }}</td>
                            <td class="text-success">${{ number_format($invoice->paid, 2) }}</td>
                            <td class="{{ $invoice->balance > 0 ? 'text-danger fw-medium' : 'text-muted' }}">
                                ${{ number_format($invoice->balance, 2) }}
                            </td>
                            <td>
                                @php
                                    $statusClass = match($invoice->status) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        'overdue' => 'danger',
                                        'cancelled' => 'secondary',
                                        default => 'info'
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('invoices.download-pdf', $invoice) }}" class="btn btn-outline-secondary" title="Download PDF" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete({{ $invoice->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $invoice->id }}" action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                                <p class="mb-0">No invoices found</p>
                                <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-primary mt-2">Create Invoice</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($invoices, 'links'))
            <div class="d-flex justify-content-end mt-2">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this invoice?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
    </script>
@endsection
