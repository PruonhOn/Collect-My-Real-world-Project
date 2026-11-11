@extends('layouts.admin')

@section('title', 'Return Details')
@section('subtitle', 'Return #' . $return->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Return #{{ $return->id }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('returns.index') }}">Returns</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('returns.edit', $return) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-return-left me-2 text-primary"></i>Return Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Shipment Number</label>
                    <p class="fw-medium mb-0">{{ $return->shipment->shipment_number ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Customer</label>
                    <p class="fw-medium mb-0">{{ $return->customer->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Reason</label>
                    <p class="fw-medium mb-0">{{ $return->reason ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="mb-0">
                        @switch($return->status)
                            @case('pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                                @break
                            @case('approved')
                                <span class="badge bg-success">Approved</span>
                                @break
                            @case('rejected')
                                <span class="badge bg-danger">Rejected</span>
                                @break
                            @case('completed')
                                <span class="badge bg-primary">Completed</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ ucfirst($return->status) }}</span>
                        @endswitch
                    </p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Notes</label>
                    <p class="fw-medium mb-0">{{ $return->notes ?? '-' }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Created</label>
                    <p class="fw-medium mb-0">{{ $return->created_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-box-arrow-in-down me-2"></i>Return Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($return->items ?? [] as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->name ?? $item->product_name ?? '-' }}</td>
                                <td>{{ $item->quantity ?? '-' }}</td>
                                <td>{{ $item->reason ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2 mb-0">No return items</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Return Timeline</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center pb-3 mb-3 border-bottom">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-arrow-return-left text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Return Created</h6>
                        <small class="text-muted">{{ $return->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @if($return->updated_at && $return->updated_at != $return->created_at)
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-fill text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Return Updated</h6>
                        <small class="text-muted">{{ $return->updated_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
