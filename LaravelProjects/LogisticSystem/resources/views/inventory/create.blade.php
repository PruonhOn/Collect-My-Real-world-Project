@extends('layouts.admin')

@section('title', 'Add Inventory Item - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add Item</li>
@endsection

@section('page-title', 'Add Inventory Item')
@section('page-subtitle', 'Create a new stock item')

@section('content')
    <div class="row">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('inventory.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" id="sku" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}" placeholder="e.g. SKU-001" required>
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Logistics Box (Large)" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Optional description...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="category" class="form-label">Category</label>
                                <select id="category" name="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="">Select Category...</option>
                                    <option value="Packaging" {{ old('category') == 'Packaging' ? 'selected' : '' }}>Packaging</option>
                                    <option value="Office Supplies" {{ old('category') == 'Office Supplies' ? 'selected' : '' }}>Office Supplies</option>
                                    <option value="Warehouse Equipment" {{ old('category') == 'Warehouse Equipment' ? 'selected' : '' }}>Warehouse Equipment</option>
                                    <option value="Vehicle Parts" {{ old('category') == 'Vehicle Parts' ? 'selected' : '' }}>Vehicle Parts</option>
                                    <option value="Safety Gear" {{ old('category') == 'Safety Gear' ? 'selected' : '' }}>Safety Gear</option>
                                    <option value="Fuel" {{ old('category') == 'Fuel' ? 'selected' : '' }}>Fuel & Lubricants</option>
                                    <option value="Other" {{ old('category') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" id="quantity" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 0) }}" min="0" required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="min_quantity" class="form-label">Min Quantity</label>
                                <input type="number" id="min_quantity" name="min_quantity" class="form-control @error('min_quantity') is-invalid @enderror" value="{{ old('min_quantity', 0) }}" min="0">
                                @error('min_quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="max_quantity" class="form-label">Max Quantity</label>
                                <input type="number" id="max_quantity" name="max_quantity" class="form-control @error('max_quantity') is-invalid @enderror" value="{{ old('max_quantity') }}" min="0">
                                @error('max_quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="unit" class="form-label">Unit</label>
                                <select id="unit" name="unit" class="form-select @error('unit') is-invalid @enderror">
                                    <option value="">Select Unit...</option>
                                    <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                                    <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                                    <option value="g" {{ old('unit') == 'g' ? 'selected' : '' }}>Grams (g)</option>
                                    <option value="l" {{ old('unit') == 'l' ? 'selected' : '' }}>Liters (l)</option>
                                    <option value="m" {{ old('unit') == 'm' ? 'selected' : '' }}>Meters (m)</option>
                                    <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>Box</option>
                                    <option value="pallet" {{ old('unit') == 'pallet' ? 'selected' : '' }}>Pallet</option>
                                </select>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="weight_per_unit" class="form-label">Weight per Unit (kg)</label>
                                <input type="number" step="0.01" id="weight_per_unit" name="weight_per_unit" class="form-control @error('weight_per_unit') is-invalid @enderror" value="{{ old('weight_per_unit') }}" min="0" placeholder="e.g. 1.5">
                                @error('weight_per_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="volume_per_unit" class="form-label">Volume per Unit (m³)</label>
                                <input type="number" step="0.001" id="volume_per_unit" name="volume_per_unit" class="form-control @error('volume_per_unit') is-invalid @enderror" value="{{ old('volume_per_unit') }}" min="0" placeholder="e.g. 0.05">
                                @error('volume_per_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="unit_price" class="form-label">Unit Price ($)</label>
                                <input type="number" step="0.01" id="unit_price" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price', '0.00') }}" min="0">
                                @error('unit_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="warehouse_id" class="form-label">Warehouse <span class="text-danger">*</span></label>
                                <select id="warehouse_id" name="warehouse_id" class="form-select select2 @error('warehouse_id') is-invalid @enderror" required>
                                    <option value="">Select Warehouse...</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }} ({{ $warehouse->code ?? $warehouse->city }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="location_id" class="form-label">Location (Bin)</label>
                                <select id="location_id" name="location_id" class="form-select select2 @error('location_id') is-invalid @enderror">
                                    <option value="">Select Location...</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->code ?? $location->name }} - {{ $location->warehouse?->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Create Item
                            </button>
                            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
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
