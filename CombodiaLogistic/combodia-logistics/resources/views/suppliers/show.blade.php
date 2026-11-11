@extends('layouts.admin')

@section('title', 'Supplier Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Supplier Details</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit
            </a>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <x-breadcrumb :items="[
        ['label' => 'Suppliers', 'url' => route('admin.suppliers.index')],
        ['label' => 'Supplier Details']
    ]" />

    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Supplier Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Code</label>
                        <p class="fw-medium mb-0">{{ $supplier->code }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Name</label>
                        <p class="fw-medium mb-0">{{ $supplier->name }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Company Name</label>
                        <p class="fw-medium mb-0">{{ $supplier->company_name ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Email</label>
                        <p class="fw-medium mb-0">{{ $supplier->email ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Phone</label>
                        <p class="fw-medium mb-0">{{ $supplier->phone ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Address</label>
                        <p class="fw-medium mb-0">{{ $supplier->address ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">City</label>
                        <p class="fw-medium mb-0">{{ $supplier->city ?? '-' }}</p>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Country</label>
                        <p class="fw-medium mb-0">{{ $supplier->country ?? '-' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted mb-1" style="font-size: 0.8rem;">Status</label>
                        <p class="mb-0">
                            @if($supplier->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Inventories Table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Inventories</h6>
                    <span class="badge bg-primary">{{ optional($inventories)->count() ?? 0 }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover DataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories ?? [] as $inventory)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $inventory->code }}</td>
                                        <td>{{ $inventory->name }}</td>
                                        <td>{{ $inventory->category ?? '-' }}</td>
                                        <td>{{ $inventory->quantity ?? 0 }}</td>
                                        <td>${{ number_format($inventory->unit_price ?? 0, 2) }}</td>
                                        <td>
                                            @if($inventory->is_active ?? false)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No inventories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
