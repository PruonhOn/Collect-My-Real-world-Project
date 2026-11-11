@extends('layouts.admin')

@section('title', 'Create Route - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-title', 'Create Route')
@section('page-subtitle', 'Add a new delivery route')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('routes.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. RTE-001" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="name" class="form-label">Route Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Phnom Penh → Siem Reap" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="origin_city" class="form-label">Origin City <span class="text-danger">*</span></label>
                                <input type="text" id="origin_city" name="origin_city" class="form-control @error('origin_city') is-invalid @enderror" value="{{ old('origin_city') }}" placeholder="e.g. Phnom Penh" required>
                                @error('origin_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="destination_city" class="form-label">Destination City <span class="text-danger">*</span></label>
                                <input type="text" id="destination_city" name="destination_city" class="form-control @error('destination_city') is-invalid @enderror" value="{{ old('destination_city') }}" placeholder="e.g. Siem Reap" required>
                                @error('destination_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="distance_km" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" id="distance_km" name="distance_km" class="form-control @error('distance_km') is-invalid @enderror" value="{{ old('distance_km') }}" placeholder="e.g. 314" required>
                                @error('distance_km')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="estimated_hours" class="form-label">Estimated Hours <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" id="estimated_hours" name="estimated_hours" class="form-control @error('estimated_hours') is-invalid @enderror" value="{{ old('estimated_hours') }}" placeholder="e.g. 5.5" required>
                                @error('estimated_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Optional route description...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Create Route
                            </button>
                            <a href="{{ route('routes.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
