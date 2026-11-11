@extends('layouts.admin')

@section('title', 'Edit Return')
@section('subtitle', 'Update return record')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Return</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('returns.index') }}">Returns</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('returns.update', $return) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-return-left me-2 text-primary"></i>Return Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shipment <span class="text-danger">*</span></label>
                            <select class="form-select @error('shipment_id') is-invalid @enderror" name="shipment_id" required>
                                <option value="">Select Shipment</option>
                                @foreach($shipments as $shipment)
                                    <option value="{{ $shipment->id }}" {{ old('shipment_id', $return->shipment_id) == $shipment->id ? 'selected' : '' }}>{{ $shipment->shipment_number }}</option>
                                @endforeach
                            </select>
                            @error('shipment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id', $return->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="2" required>{{ old('reason', $return->reason) }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                <option value="pending" {{ old('status', $return->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ old('status', $return->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ old('status', $return->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="completed" {{ old('status', $return->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $return->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Return Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Return ID</label>
                        <p class="fw-medium mb-0">#{{ $return->id }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Current Status</label>
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
                    <div class="mb-0">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Created</label>
                        <p class="fw-medium mb-0">{{ $return->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Return
                </button>
                <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection
