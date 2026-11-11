@extends('layouts.admin')

@section('title', 'Add Driver - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('drivers.index') }}">Drivers</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add Driver</li>
@endsection

@section('page-title', 'Add Driver')
@section('page-subtitle', 'Register a new driver')

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('drivers.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">Personal Information</h6>

                            <div class="col-md-3">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" name="code"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code') }}" required>
                                @error('code') <x-form-error name="code" /> @enderror
                            </div>

                            <div class="col-md-5">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required>
                                @error('name') <x-form-error name="name" /> @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       class="form-control @error('date_of_birth') is-invalid @enderror"
                                       value="{{ old('date_of_birth') }}">
                                @error('date_of_birth') <x-form-error name="date_of_birth" /> @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="national_id" class="form-label">National ID</label>
                                <input type="text" id="national_id" name="national_id"
                                       class="form-control @error('national_id') is-invalid @enderror"
                                       value="{{ old('national_id') }}">
                                @error('national_id') <x-form-error name="national_id" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" required>
                                @error('phone') <x-form-error name="phone" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}">
                                @error('email') <x-form-error name="email" /> @enderror
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" rows="2"
                                          class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                                @error('address') <x-form-error name="address" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                                <input type="text" id="emergency_contact" name="emergency_contact"
                                       class="form-control @error('emergency_contact') is-invalid @enderror"
                                       value="{{ old('emergency_contact') }}">
                                @error('emergency_contact') <x-form-error name="emergency_contact" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="emergency_phone" class="form-label">Emergency Phone</label>
                                <input type="text" id="emergency_phone" name="emergency_phone"
                                       class="form-control @error('emergency_phone') is-invalid @enderror"
                                       value="{{ old('emergency_phone') }}">
                                @error('emergency_phone') <x-form-error name="emergency_phone" /> @enderror
                            </div>

                            <h6 class="fw-bold text-primary border-bottom pb-2 mt-2">License Information</h6>

                            <div class="col-md-4">
                                <label for="license_number" class="form-label">License #</label>
                                <input type="text" id="license_number" name="license_number"
                                       class="form-control @error('license_number') is-invalid @enderror"
                                       value="{{ old('license_number') }}">
                                @error('license_number') <x-form-error name="license_number" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="license_type" class="form-label">License Type</label>
                                <select id="license_type" name="license_type"
                                        class="form-select @error('license_type') is-invalid @enderror">
                                    <option value="">Select</option>
                                    <option value="A" {{ old('license_type') == 'A' ? 'selected' : '' }}>A (Motorcycle)</option>
                                    <option value="B" {{ old('license_type') == 'B' ? 'selected' : '' }}>B (Light Vehicle)</option>
                                    <option value="C" {{ old('license_type') == 'C' ? 'selected' : '' }}>C (Medium Truck)</option>
                                    <option value="D" {{ old('license_type') == 'D' ? 'selected' : '' }}>D (Heavy Truck)</option>
                                    <option value="E" {{ old('license_type') == 'E' ? 'selected' : '' }}>E (Trailer)</option>
                                </select>
                                @error('license_type') <x-form-error name="license_type" /> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="license_expiry" class="form-label">License Expiry</label>
                                <input type="date" id="license_expiry" name="license_expiry"
                                       class="form-control @error('license_expiry') is-invalid @enderror"
                                       value="{{ old('license_expiry') }}">
                                @error('license_expiry') <x-form-error name="license_expiry" /> @enderror
                            </div>

                            <h6 class="fw-bold text-primary border-bottom pb-2 mt-2">Employment Details</h6>

                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status"
                                        class="form-select @error('status') is-invalid @enderror">
                                    <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                                    <option value="on-delivery" {{ old('status') == 'on-delivery' ? 'selected' : '' }}>On Delivery</option>
                                    <option value="off-duty" {{ old('status') == 'off-duty' ? 'selected' : '' }}>Off Duty</option>
                                    <option value="on-leave" {{ old('status') == 'on-leave' ? 'selected' : '' }}>On Leave</option>
                                    <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                </select>
                                @error('status') <x-form-error name="status" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="hire_date" class="form-label">Hire Date</label>
                                <input type="date" id="hire_date" name="hire_date"
                                       class="form-control @error('hire_date') is-invalid @enderror"
                                       value="{{ old('hire_date', date('Y-m-d')) }}">
                                @error('hire_date') <x-form-error name="hire_date" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="salary" class="form-label">Salary ($)</label>
                                <input type="number" step="0.01" id="salary" name="salary"
                                       class="form-control @error('salary') is-invalid @enderror"
                                       value="{{ old('salary') }}">
                                @error('salary') <x-form-error name="salary" /> @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select id="branch_id" name="branch_id"
                                        class="form-select select2 @error('branch_id') is-invalid @enderror">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id') <x-form-error name="branch_id" /> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="photo" class="form-label">Photo</label>
                                <input type="file" id="photo" name="photo"
                                       class="form-control @error('photo') is-invalid @enderror"
                                       accept="image/*">
                                @error('photo') <x-form-error name="photo" /> @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Driver
                            </button>
                            <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
