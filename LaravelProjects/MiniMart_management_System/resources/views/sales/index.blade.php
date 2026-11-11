@extends('layouts.app')

@section('title', 'Sales History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-receipt me-2"></i>Sales History</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search invoice..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('sales.index') }}" class="btn btn-secondary w-100"><i class="fas fa-redo me-1"></i>Reset</a>
            </div>
            <div class="col-md-1">
                <a href="{{ route('sales.pos') }}" class="btn btn-success w-100"><i class="fas fa-plus"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Change</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td class="fw-bold">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                        <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                        <td>{{ $sale->items->sum('quantity') }}</td>
                        <td class="fw-bold">${{ number_format($sale->total_amount, 2) }}</td>
                        <td>${{ number_format($sale->paid_amount, 2) }}</td>
                        <td>${{ number_format($sale->change_amount, 2) }}</td>
                        <td><span class="badge bg-info">{{ ucfirst($sale->payment_method) }}</span></td>
                        <td>
                            <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('sales.receipt', $sale->id) }}" class="btn btn-sm btn-secondary" title="Print Receipt" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-receipt fa-3x mb-3 d-block"></i>
                            No sales found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            {{ $sales->links() }}
        </div>
    </div>
</div>
@endsection
