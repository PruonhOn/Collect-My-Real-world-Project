@extends('layouts.admin')

@section('title', 'Edit Warehouse - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.show', $warehouse) }}">{{ $warehouse->code }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('page-title', 'Edit Warehouse')
@section('page-subtitle', $warehouse->name)

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('warehouses.update', $warehouse) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $warehouse->code) }}" required>
                                @error('code') <x-form-error name="code" /> @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $warehouse->name) }}" required>
                                @error('name') <x-form-error name="name" /> @enderror
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" rows="2"
                                          class="form-control @error('address') is-invalid @enderror">{{ old('address', $warehouse->address) }}</textarea>
                                @error('address') <x-form-error name="address" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" id="city" name="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city', $warehouse->city) }}" required>
                                @error('city') <x-form-error name="city" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="province" class="form-label">Province</label>
                                <input type="text" id="province" name="province"
                                       class="form-control @error('province') is-invalid @enderror"
                                       value="{{ old('province', $warehouse->province) }}">
                                @error('province') <x-form-error name="province" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $warehouse->phone) }}">
                                @error('phone') <x-form-error name="phone" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $warehouse->email) }}">
                                @error('email') <x-form-error name="email" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="manager_name" class="form-label">Manager Name</label>
                                <input type="text" id="manager_name" name="manager_name"
                                       class="form-control @error('manager_name') is-invalid @enderror"
                                       value="{{ old('manager_name', $warehouse->manager_name) }}">
                                @error('manager_name') <x-form-error name="manager_name" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select id="branch_id" name="branch_id"
                                        class="form-select select2 @error('branch_id') is-invalid @enderror">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id', $warehouse->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id') <x-form-error name="branch_id" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="total_capacity" class="form-label">Total Capacity (m³)</label>
                                <input type="number" step="0.01" id="total_capacity" name="total_capacity"
                                       class="form-control @error('total_capacity') is-invalid @enderror"
                                       value="{{ old('total_capacity', $warehouse->total_capacity) }}">
                                @error('total_capacity') <x-form-error name="total_capacity" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="total_area" class="form-label">Total Area (m²)</label>
                                <input type="number" step="0.01" id="total_area" name="total_area"
                                       class="form-control @error('total_area') is-invalid @enderror"
                                       value="{{ old('total_area', $warehouse->total_area) }}">
                                @error('total_area') <x-form-error name="total_area" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="is_active" class="form-label">Status</label>
                                <select id="is_active" name="is_active"
                                        class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', $warehouse->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $warehouse->is_active) === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active') <x-form-error name="is_active" /> @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Warehouse
                            </button>
                            <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#branch_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Branch'
            });
        });
    </script>
@endpush
