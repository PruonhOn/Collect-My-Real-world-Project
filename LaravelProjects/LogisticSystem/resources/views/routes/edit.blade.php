@extends('layouts.admin')

@section('title', 'Edit Route - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('page-title', 'Edit Route: ' . $route->name)
@section('page-subtitle', 'Update route information')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('routes.update', $route) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $route->code) }}" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="name" class="form-label">Route Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $route->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="origin_city" class="form-label">Origin City <span class="text-danger">*</span></label>
                                <input type="text" id="origin_city" name="origin_city" class="form-control @error('origin_city') is-invalid @enderror" value="{{ old('origin_city', $route->origin_city) }}" required>
                                @error('origin_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="destination_city" class="form-label">Destination City <span class="text-danger">*</span></label>
                                <input type="text" id="destination_city" name="destination_city" class="form-control @error('destination_city') is-invalid @enderror" value="{{ old('destination_city', $route->destination_city) }}" required>
                                @error('destination_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="distance_km" class="form-label">Distance (km) <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" id="distance_km" name="distance_km" class="form-control @error('distance_km') is-invalid @enderror" value="{{ old('distance_km', $route->distance_km) }}" required>
                                @error('distance_km')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="estimated_hours" class="form-label">Estimated Hours <span class="text-danger">*</span></label>
                                <input type="number" step="0.1" id="estimated_hours" name="estimated_hours" class="form-control @error('estimated_hours') is-invalid @enderror" value="{{ old('estimated_hours', $route->estimated_hours) }}" required>
                                @error('estimated_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $route->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', $route->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Route
                            </button>
                            <a href="{{ route('routes.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
