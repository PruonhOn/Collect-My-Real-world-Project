@extends('layouts.admin')

@section('title', 'Warehouse Reports - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">Warehouse Reports</li>
@endsection

@section('page-title', 'Warehouse Reports')
@section('page-subtitle', 'Warehouse utilization and inventory reports')

@section('content')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-building me-2"></i>Warehouse Overview</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Warehouse reports and analytics will be displayed here.</p>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-bar-chart fs-1 d-block mb-2"></i>
                        <p class="mb-0">Warehouse data visualization coming soon</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-box me-2"></i>Inventory Summary</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">Summary of inventory across all warehouses.</p>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                        <p class="mb-0">Inventory summary coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
