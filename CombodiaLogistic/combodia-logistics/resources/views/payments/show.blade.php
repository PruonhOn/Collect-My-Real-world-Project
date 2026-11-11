@extends('layouts.admin')

@section('title', 'Payment Details')
@section('subtitle', 'Payment #' . $payment->payment_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Payment #{{ $payment->payment_number }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('payments.edit', $payment) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card me-2 text-primary"></i>Payment Info</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Payment Number</label>
                    <p class="fw-medium mb-0">{{ $payment->payment_number }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Customer</label>
                    <p class="fw-medium mb-0">{{ $payment->customer->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Invoice Number</label>
                    <p class="fw-medium mb-0">{{ $payment->invoice->invoice_number ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Payment Method</label>
                    <p class="fw-medium mb-0">{{ $payment->paymentMethod->name ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Amount</label>
                    <p class="fw-bold text-success mb-0" style="font-size: 1.2rem;">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Reference Number</label>
                    <p class="fw-medium mb-0">{{ $payment->reference_number ?? '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Payment Date</label>
                    <p class="fw-medium mb-0">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '-' }}</p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                    <p class="mb-0">
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
                    </p>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Notes</label>
                    <p class="fw-medium mb-0">{{ $payment->notes ?? '-' }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted mb-1" style="font-size: 0.8rem;">Received By</label>
                    <p class="fw-medium mb-0">{{ $payment->receivedBy->name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Payment Timeline</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center pb-3 mb-3 border-bottom">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Payment {{ ucfirst($payment->status) }}</h6>
                        <small class="text-muted">{{ $payment->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @if($payment->updated_at && $payment->updated_at != $payment->created_at)
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil-fill text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Payment Updated</h6>
                        <small class="text-muted">{{ $payment->updated_at->format('d M Y, h:i A') }}</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
