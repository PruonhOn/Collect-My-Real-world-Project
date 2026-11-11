@extends('layouts.admin')

@section('title', 'Income Details')
@section('subtitle', 'View income record')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Income #{{ $income->id }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('income.index') }}">Income</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('income.edit', $income) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('income.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Income Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Category</label>
                    <p class="fw-medium mb-0">{{ $income->incomeCategory->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Amount</label>
                    <p class="fw-bold text-success mb-0" style="font-size: 1.2rem;">{{ number_format($income->amount, 2) }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Description</label>
                    <p class="fw-medium mb-0">{{ $income->description ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Income Date</label>
                    <p class="fw-medium mb-0">{{ $income->income_date ? \Carbon\Carbon::parse($income->income_date)->format('d M Y') : '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Reference Number</label>
                    <p class="fw-medium mb-0">{{ $income->reference_number ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="mb-0">
                        @switch($income->status)
                            @case('received')
                                <span class="badge bg-success">Received</span>
                                @break
                            @case('pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ ucfirst($income->status) }}</span>
                        @endswitch
                    </p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Notes</label>
                    <p class="fw-medium mb-0">{{ $income->notes ?? '-' }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Created</label>
                    <p class="fw-medium mb-0">{{ $income->created_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Income Timeline</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center pb-3 mb-3 border-bottom">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-plus-circle-fill text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Income Recorded</h6>
                        <small class="text-muted">{{ $income->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @if($income->updated_at && $income->updated_at != $income->created_at)
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-fill text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Income Updated</h6>
                        <small class="text-muted">{{ $income->updated_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
