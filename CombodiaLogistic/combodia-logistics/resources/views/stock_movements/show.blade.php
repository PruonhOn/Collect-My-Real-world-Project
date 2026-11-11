@extends('layouts.admin')

@section('title', 'Movement Details')
@section('subtitle', 'Stock movement details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Stock Movement #{{ $stockMovement->id }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-movements.index') }}">Stock Movements</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('stock-movements.edit', $stockMovement) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Movement Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Product Name</label>
                    <p class="fw-medium mb-0">{{ $stockMovement->inventory->product_name ?? $stockMovement->inventory->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Type</label>
                    <p class="mb-0">
                        @switch($stockMovement->type)
                            @case('in')
                                <span class="badge bg-success">Stock In</span>
                                @break
                            @case('out')
                                <span class="badge bg-danger">Stock Out</span>
                                @break
                            @case('transfer')
                                <span class="badge bg-info">Transfer</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ ucfirst($stockMovement->type) }}</span>
                        @endswitch
                    </p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Quantity</label>
                    <p class="fw-bold mb-0" style="font-size: 1.2rem;">{{ number_format($stockMovement->quantity) }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Reference Number</label>
                    <p class="fw-medium mb-0">{{ $stockMovement->reference_number ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Notes</label>
                    <p class="fw-medium mb-0">{{ $stockMovement->notes ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Movement Date</label>
                    <p class="fw-medium mb-0">{{ $stockMovement->movement_date ? \Carbon\Carbon::parse($stockMovement->movement_date)->format('d M Y, h:i A') : $stockMovement->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Recorded By</label>
                    <p class="fw-medium mb-0">{{ $stockMovement->user->name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Movement Timeline</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center pb-3 mb-3 border-bottom">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-arrow-left-right text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Movement {{ ucfirst($stockMovement->type) }} Recorded</h6>
                        <small class="text-muted">{{ $stockMovement->created_at->format('d M Y, h:i A') }}</small>
                        @if($stockMovement->user)
                        <p class="mb-0 small text-muted">By {{ $stockMovement->user->name }}</p>
                        @endif
                    </div>
                </div>
                @if($stockMovement->updated_at && $stockMovement->updated_at != $stockMovement->created_at)
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-fill text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Movement Updated</h6>
                        <small class="text-muted">{{ $stockMovement->updated_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
