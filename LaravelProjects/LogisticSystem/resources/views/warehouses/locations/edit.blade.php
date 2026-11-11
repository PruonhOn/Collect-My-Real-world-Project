@extends('layouts.admin')

@section('title', 'Edit Location - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.show', $warehouse) }}">{{ $warehouse->code }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouse-locations.index', ['warehouse_id' => $warehouse->id]) }}">Locations</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $location->code }}</li>
@endsection

@section('page-title', 'Edit Location')
@section('page-subtitle', $location->code . ' @ ' . $warehouse->name)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('warehouse-locations.update', [$warehouse, $location]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $location->code) }}" required>
                                <small class="text-muted">e.g. WH-A1-R2-S3-B4</small>
                                @error('code') <x-form-error name="code" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $location->name) }}" required>
                                @error('name') <x-form-error name="name" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="zone" class="form-label">Zone</label>
                                <input type="text" id="zone" name="zone"
                                       class="form-control @error('zone') is-invalid @enderror"
                                       value="{{ old('zone', $location->zone) }}">
                                @error('zone') <x-form-error name="zone" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="aisle" class="form-label">Aisle</label>
                                <input type="text" id="aisle" name="aisle"
                                       class="form-control @error('aisle') is-invalid @enderror"
                                       value="{{ old('aisle', $location->aisle) }}">
                                @error('aisle') <x-form-error name="aisle" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="rack" class="form-label">Rack</label>
                                <input type="text" id="rack" name="rack"
                                       class="form-control @error('rack') is-invalid @enderror"
                                       value="{{ old('rack', $location->rack) }}">
                                @error('rack') <x-form-error name="rack" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="shelf" class="form-label">Shelf</label>
                                <input type="text" id="shelf" name="shelf"
                                       class="form-control @error('shelf') is-invalid @enderror"
                                       value="{{ old('shelf', $location->shelf) }}">
                                @error('shelf') <x-form-error name="shelf" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="bin" class="form-label">Bin</label>
                                <input type="text" id="bin" name="bin"
                                       class="form-control @error('bin') is-invalid @enderror"
                                       value="{{ old('bin', $location->bin) }}">
                                @error('bin') <x-form-error name="bin" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="max_weight" class="form-label">Max Weight (kg)</label>
                                <input type="number" step="0.01" id="max_weight" name="max_weight"
                                       class="form-control @error('max_weight') is-invalid @enderror"
                                       value="{{ old('max_weight', $location->max_weight) }}">
                                @error('max_weight') <x-form-error name="max_weight" /> @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Location
                            </button>
                            <a href="{{ route('warehouse-locations.index', ['warehouse_id' => $warehouse->id]) }}"
                               class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
