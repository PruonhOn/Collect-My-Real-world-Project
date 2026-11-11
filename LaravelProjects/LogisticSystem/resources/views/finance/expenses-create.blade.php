@extends('layouts.admin')

@section('title', 'Record Expense - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finance.index') }}">Finance</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="breadcrumb-item active" aria-current="page">Record</li>
@endsection

@section('page-title', 'Record Expense')
@section('page-subtitle', 'Add a new company expense')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="expense_number" class="form-label">Expense Number</label>
                                <input type="text" id="expense_number" name="expense_number" class="form-control @error('expense_number') is-invalid @enderror"
                                    value="{{ old('expense_number', $expenseNumber ?? 'EXP-' . date('Ymd') . '-001') }}" readonly>
                                @error('expense_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                    value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                                    <option value="">Select Category...</option>
                                    <option value="Fuel" {{ old('category') == 'Fuel' ? 'selected' : '' }}>Fuel</option>
                                    <option value="Maintenance" {{ old('category') == 'Maintenance' ? 'selected' : '' }}>Maintenance & Repairs</option>
                                    <option value="Salaries" {{ old('category') == 'Salaries' ? 'selected' : '' }}>Salaries & Wages</option>
                                    <option value="Utilities" {{ old('category') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                                    <option value="Rent" {{ old('category') == 'Rent' ? 'selected' : '' }}>Rent</option>
                                    <option value="Insurance" {{ old('category') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
                                    <option value="Office Supplies" {{ old('category') == 'Office Supplies' ? 'selected' : '' }}>Office Supplies</option>
                                    <option value="Travel" {{ old('category') == 'Travel' ? 'selected' : '' }}>Travel & Accommodation</option>
                                    <option value="Food" {{ old('category') == 'Food' ? 'selected' : '' }}>Food & Beverage</option>
                                    <option value="Vehicle Parts" {{ old('category') == 'Vehicle Parts' ? 'selected' : '' }}>Vehicle Parts</option>
                                    <option value="Toll & Parking" {{ old('category') == 'Toll & Parking' ? 'selected' : '' }}>Toll & Parking</option>
                                    <option value="Taxes" {{ old('category') == 'Taxes' ? 'selected' : '' }}>Taxes & Permits</option>
                                    <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" min="0.01" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="KHR" {{ old('currency') == 'KHR' ? 'selected' : '' }}>KHR (៛)</option>
                                    <option value="THB" {{ old('currency') == 'THB' ? 'selected' : '' }}>THB (฿)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="vendor" class="form-label">Vendor / Payee</label>
                                <input type="text" id="vendor" name="vendor" class="form-control @error('vendor') is-invalid @enderror"
                                    value="{{ old('vendor') }}" placeholder="Name of vendor or recipient">
                                @error('vendor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select id="branch_id" name="branch_id" class="form-select select2 @error('branch_id') is-invalid @enderror">
                                    <option value="">Select Branch...</option>
                                    @foreach($branches ?? [] as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="vehicle_id" class="form-label">Vehicle (Optional)</label>
                                <select id="vehicle_id" name="vehicle_id" class="form-select select2 @error('vehicle_id') is-invalid @enderror">
                                    <option value="">No Vehicle</option>
                                    @foreach($vehicles ?? [] as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->plate_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                    placeholder="Describe the expense..." required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="receipt" class="form-label">Receipt (PDF, Image)</label>
                                <input type="file" id="receipt" name="receipt" class="form-control @error('receipt') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.gif">
                                <small class="text-muted">Upload a receipt or invoice document (max 10MB)</small>
                                @error('receipt')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Record Expense
                            </button>
                            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
    </script>
@endsection
