@extends('layouts.admin')

@section('title', 'Delivery #' . $delivery->delivery_number . ' - ' . config('app.name'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('deliveries.index') }}">Deliveries</a></li>
    <li class="breadcrumb-item active" aria-current="page">#{{ $delivery->delivery_number }}</li>
@endsection

@section('page-title', 'Delivery #' . $delivery->delivery_number)
@section('page-subtitle', 'Scheduled: ' . $delivery->scheduled_date->format('d M Y H:i'))

@section('page-actions')
    <div class="btn-group">
        @if($delivery->status == 'scheduled')
            <form action="{{ route('deliveries.mark-in-transit', $delivery) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-truck"></i> Start Delivery
                </button>
            </form>
        @endif
        @if($delivery->status == 'in_transit')
            <form action="{{ route('deliveries.mark-delivered', $delivery) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg"></i> Mark Delivered
                </button>
            </form>
        @endif
        <a href="{{ route('deliveries.edit', $delivery) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#assignDriverModal">
            <i class="bi bi-person"></i> Assign Driver
        </button>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#uploadProofModal">
            <i class="bi bi-upload"></i> Upload Proof
        </button>
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Delivery Information</h5>
                    @php
                        $statusClass = match($delivery->status) {
                            'delivered' => 'success',
                            'in_transit' => 'primary',
                            'scheduled' => 'warning',
                            'partial' => 'info',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                    @endphp
                    <span class="badge bg-{{ $statusClass }} fs-6">
                        @if($delivery->status == 'in_transit') In Transit
                        @else {{ ucfirst($delivery->status) }}
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Delivery Number</small>
                            <strong>#{{ $delivery->delivery_number }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Scheduled Date</small>
                            <strong><i class="bi bi-calendar3"></i> {{ $delivery->scheduled_date->format('d M Y H:i') }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Actual Delivery</small>
                            <strong>
                                @if($delivery->actual_delivery_date)
                                    <i class="bi bi-check-circle text-success"></i> {{ $delivery->actual_delivery_date->format('d M Y H:i') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Route</small>
                            @if($delivery->route)
                                <strong>{{ $delivery->route->name }} ({{ $delivery->route->code }})</strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Distance</small>
                            <strong>{{ $delivery->route ? number_format($delivery->route->distance_km, 1) . ' km' : '—' }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Estimated Duration</small>
                            <strong>{{ $delivery->route ? number_format($delivery->route->estimated_hours, 1) . ' hours' : '—' }}</strong>
                        </div>
                        @if($delivery->notes)
                            <div class="col-12 mb-3">
                                <small class="text-muted d-block">Notes</small>
                                <p class="mb-0">{{ $delivery->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Delivery Stops</h5>
                </div>
                <div class="card-body p-0">
                    @if($delivery->stops && $delivery->stops->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($delivery->stops->sortBy('order') as $stop)
                                @php
                                    $stopStatusClass = match($stop->status) {
                                        'completed' => 'success',
                                        'arrived' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $stopStatusClass }} bg-opacity-10"
                                             style="width: 36px; height: 36px;">
                                            @if($stop->type == 'pickup')
                                                <i class="bi bi-box-arrow-in-right text-{{ $stopStatusClass }}"></i>
                                            @elseif($stop->type == 'dropoff')
                                                <i class="bi bi-box-arrow-right text-{{ $stopStatusClass }}"></i>
                                            @else
                                                <i class="bi bi-geo-alt text-{{ $stopStatusClass }}"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong>{{ $stop->location }}</strong>
                                        <small class="d-block text-muted">
                                            {{ ucfirst($stop->type) }}
                                            @if($stop->scheduled_time)
                                                | {{ \Carbon\Carbon::parse($stop->scheduled_time)->format('d M Y H:i') }}
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge bg-{{ $stopStatusClass }}">
                                        {{ ucfirst($stop->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-geo-alt fs-2 d-block mb-2"></i>
                            <p class="mb-0">No stops defined for this delivery</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Proof of Delivery</h5>
                </div>
                <div class="card-body">
                    @if($delivery->proofOfDelivery)
                        <div class="row g-3">
                            @if($delivery->proofOfDelivery->signature_path)
                                <div class="col-md-4">
                                    <small class="text-muted d-block mb-1">Signature</small>
                                    <img src="{{ asset('storage/' . $delivery->proofOfDelivery->signature_path) }}" alt="Signature" class="img-fluid border rounded" style="max-height: 120px;">
                                </div>
                            @endif
                            @if($delivery->proofOfDelivery->photo_path)
                                <div class="col-md-4">
                                    <small class="text-muted d-block mb-1">Photo</small>
                                    <img src="{{ asset('storage/' . $delivery->proofOfDelivery->photo_path) }}" alt="Delivery Photo" class="img-fluid border rounded" style="max-height: 120px;">
                                </div>
                            @endif
                            @if($delivery->proofOfDelivery->document_path)
                                <div class="col-md-4">
                                    <small class="text-muted d-block mb-1">Document</small>
                                    <a href="{{ asset('storage/' . $delivery->proofOfDelivery->document_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf"></i> View Document
                                    </a>
                                </div>
                            @endif
                            <div class="col-12">
                                <small class="text-muted d-block">Received by: <strong>{{ $delivery->proofOfDelivery->received_by ?? '—' }}</strong></small>
                                <small class="text-muted d-block">
                                    Delivered at: {{ $delivery->proofOfDelivery->delivered_at ? \Carbon\Carbon::parse($delivery->proofOfDelivery->delivered_at)->format('d M Y H:i') : '—' }}
                                </small>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-file-earmark fs-2 d-block mb-2"></i>
                            <p class="mb-0">No proof of delivery uploaded yet</p>
                            <button type="button" class="btn btn-sm btn-outline-success mt-2" data-bs-toggle="modal" data-bs-target="#uploadProofModal">
                                <i class="bi bi-upload"></i> Upload Proof
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shipment</h5>
                </div>
                <div class="card-body">
                    @if($delivery->shipment)
                        <div class="mb-3">
                            <small class="text-muted d-block">Shipment #</small>
                            <a href="{{ route('shipments.show', $delivery->shipment) }}" class="fw-medium text-decoration-none">
                                #{{ $delivery->shipment->shipment_number }}
                            </a>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Customer</small>
                            <strong>{{ $delivery->shipment->customer?->name ?? '—' }}</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Origin → Destination</small>
                            <strong>{{ $delivery->shipment->origin }} → {{ $delivery->shipment->destination }}</strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-info">{{ ucfirst($delivery->shipment->status) }}</span>
                        </div>
                    @else
                        <p class="text-muted mb-0">No shipment linked</p>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Driver</h5>
                </div>
                <div class="card-body">
                    @if($delivery->driver)
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                <i class="bi bi-person fs-4 text-primary"></i>
                            </div>
                            <div>
                                <strong>{{ $delivery->driver->name }}</strong>
                                <small class="d-block text-muted">{{ $delivery->driver->phone }}</small>
                                <small class="d-block text-muted">{{ $delivery->driver->license_number }}</small>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-person fs-2 d-block mb-2"></i>
                            <p class="mb-0">No driver assigned</p>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#assignDriverModal">
                                <i class="bi bi-person-plus"></i> Assign Driver
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vehicle</h5>
                </div>
                <div class="card-body">
                    @if($delivery->vehicle)
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                <i class="bi bi-truck fs-4 text-info"></i>
                            </div>
                            <div>
                                <strong>{{ $delivery->vehicle->plate_number }}</strong>
                                <small class="d-block text-muted">{{ $delivery->vehicle->make }} {{ $delivery->vehicle->model }} ({{ $delivery->vehicle->year }})</small>
                                <small class="d-block text-muted">Capacity: {{ $delivery->vehicle->capacity ?? '—' }}</small>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No vehicle assigned</p>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Timeline</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <small class="text-muted">Created</small>
                            <strong class="d-block">{{ $delivery->created_at->format('d M Y H:i') }}</strong>
                        </div>
                        <div class="list-group-item">
                            <small class="text-muted">Last Updated</small>
                            <strong class="d-block">{{ $delivery->updated_at->format('d M Y H:i') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('deliveries.assign-driver', $delivery) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Driver</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="driver_id" class="form-label">Select Driver <span class="text-danger">*</span></label>
                            <select id="driver_id" name="driver_id" class="form-select select2-modal" required>
                                <option value="">Choose a driver...</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ $delivery->driver_id == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }} ({{ $driver->phone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadProofModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('deliveries.upload-proof', $delivery) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Proof of Delivery</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="signature" class="form-label">Signature Image</label>
                            <input type="file" id="signature" name="signature" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="photo" class="form-label">Delivery Photo</label>
                            <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="document" class="form-label">Document (PDF)</label>
                            <input type="file" id="document" name="document" class="form-control" accept=".pdf">
                        </div>
                        <div class="mb-3">
                            <label for="received_by" class="form-label">Received By</label>
                            <input type="text" id="received_by" name="received_by" class="form-control" placeholder="Recipient name">
                        </div>
                        <div class="mb-3">
                            <label for="delivered_at" class="form-label">Delivered At</label>
                            <input type="datetime-local" id="delivered_at" name="delivered_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Upload Proof</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2-modal').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#assignDriverModal')
            });
        });
    </script>
@endsection
