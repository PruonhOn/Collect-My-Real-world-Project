@extends('layouts.admin')

@section('title', 'Edit Route')
@section('subtitle', 'Edit route ' . $route->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Edit Route</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('routes.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('routes.update', $route) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-signpost-2 me-2 text-primary"></i>Route Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Route Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{ old('code', $route->code) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Route Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $route->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $route->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" {{ old('status', $route->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $route->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $route->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="start_location" value="{{ old('start_location', $route->start_location) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="end_location" value="{{ old('end_location', $route->end_location) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Distance (km)</label>
                            <input type="number" class="form-control" name="distance" step="0.1" value="{{ old('distance', $route->distance) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimated Duration</label>
                            <input type="text" class="form-control" name="duration" value="{{ old('duration', $route->duration) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-geo-alt me-2 text-success"></i>Route Stops</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addStop">
                        <i class="bi bi-plus-circle me-1"></i> Add Stop
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="stopsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Stop Name</th>
                                    <th>Address</th>
                                    <th>Order</th>
                                    <th>ETA</th>
                                    <th>ETD</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="stopsBody">
                                @forelse($route->stops as $idx => $stop)
                                <tr class="stop-row">
                                    <td>{{ $idx + 1 }}</td>
                                    <td><input type="text" class="form-control form-control-sm" name="stops[{{ $idx }}][name]" value="{{ $stop->name }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="stops[{{ $idx }}][address]" value="{{ $stop->address }}"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="stops[{{ $idx }}][order]" value="{{ $stop->order }}" min="1"></td>
                                    <td><input type="time" class="form-control form-control-sm" name="stops[{{ $idx }}][eta]" value="{{ $stop->eta }}"></td>
                                    <td><input type="time" class="form-control form-control-sm" name="stops[{{ $idx }}][etd]" value="{{ $stop->etd }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-stop"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr class="stop-row">
                                    <td>1</td>
                                    <td><input type="text" class="form-control form-control-sm" name="stops[0][name]"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="stops[0][address]"></td>
                                    <td><input type="number" class="form-control form-control-sm" name="stops[0][order]" value="1" min="1"></td>
                                    <td><input type="time" class="form-control form-control-sm" name="stops[0][eta]"></td>
                                    <td><input type="time" class="form-control form-control-sm" name="stops[0][etd]"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-stop"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <i class="bi bi-signpost-2 text-primary" style="font-size: 3rem;"></i>
                    <h6 class="mt-3">{{ $route->code }}</h6>
                    <p class="text-muted small mb-0">{{ $route->start_location }} to {{ $route->end_location }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('routes.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Update Route
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let stopIndex = {{ count($route->stops) ?: 1 }};

    $('#addStop').on('click', function() {
        const row = `
            <tr class="stop-row">
                <td>${stopIndex + 1}</td>
                <td><input type="text" class="form-control form-control-sm" name="stops[${stopIndex}][name]"></td>
                <td><input type="text" class="form-control form-control-sm" name="stops[${stopIndex}][address]"></td>
                <td><input type="number" class="form-control form-control-sm" name="stops[${stopIndex}][order]" value="${stopIndex + 1}" min="1"></td>
                <td><input type="time" class="form-control form-control-sm" name="stops[${stopIndex}][eta]"></td>
                <td><input type="time" class="form-control form-control-sm" name="stops[${stopIndex}][etd]"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-stop"><i class="bi bi-trash"></i></button></td>
            </tr>`;
        $('#stopsBody').append(row);
        stopIndex++;
    });

    $(document).on('click', '.remove-stop', function() {
        if ($('.stop-row').length > 1) {
            $(this).closest('tr').remove();
        }
    });
</script>
@endpush
