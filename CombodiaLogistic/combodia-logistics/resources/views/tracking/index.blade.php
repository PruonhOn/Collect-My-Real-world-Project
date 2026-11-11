@extends('layouts.admin')

@section('title', 'Live Tracking')
@section('subtitle', 'Track shipments in real-time')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Live Tracking</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Tracking</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-primary" onclick="openGPSModal()">
        <i class="bi bi-geo-alt me-1"></i> Update GPS Location
    </button>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="bg-light rounded mb-4" style="height: 400px; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center text-muted">
                        <i class="bi bi-map" style="font-size: 3rem;"></i>
                        <p class="mt-2 mb-0">Interactive Map</p>
                        <small>Map integration placeholder</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Active Shipments</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    @forelse($trackings as $tracking)
                    <a href="#" class="list-group-item list-group-item-action py-3" onclick="focusShipment({{ $tracking->shipment->id }})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">{{ $tracking->shipment->shipment_number }}</h6>
                                <small class="text-muted">{{ $tracking->shipment->recipient_city }}</small>
                            </div>
                            <small class="text-muted">{{ $tracking->updated_at->diffForHumans() }}</small>
                        </div>
                        <div class="mt-1">
                            <small><i class="bi bi-geo-alt me-1"></i>{{ $tracking->location ?? 'Unknown' }}</small>
                        </div>
                    </a>
                    @empty
                    <div class="p-3 text-center text-muted">No active shipments</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Shipment #</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Driver</th>
                        <th>Last Updated</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trackings as $tracking)
                    <tr>
                        <td><a href="{{ route('shipments.show', $tracking->shipment) }}" class="fw-semibold text-decoration-none">{{ $tracking->shipment->shipment_number ?? 'N/A' }}</a></td>
                        <td>
                            @switch($tracking->status)
                                @case('in_transit')
                                    <span class="badge bg-primary">In Transit</span>
                                    @break
                                @case('delivered')
                                    <span class="badge bg-success">Delivered</span>
                                    @break
                                @case('at_warehouse')
                                    <span class="badge bg-info">At Warehouse</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $tracking->status)) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $tracking->location ?? 'N/A' }}</td>
                        <td>{{ $tracking->driver->name ?? 'N/A' }}</td>
                        <td>{{ $tracking->updated_at->format('d M Y, h:i A') }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="focusShipment({{ $tracking->shipment->id }})">
                                <i class="bi bi-map me-1"></i> View on Map
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="openGPSModalFor({{ $tracking->id }}, '{{ $tracking->shipment->shipment_number }}')">
                                <i class="bi bi-gps me-1"></i> Update GPS
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2">No tracking data found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- GPS Update Modal -->
<div class="modal fade" id="gpsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update GPS Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Shipment</label>
                    <input type="text" class="form-control" id="gpsShipmentName" readonly>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Latitude</label>
                        <input type="number" class="form-control" id="gpsLat" step="0.000001" placeholder="e.g., 11.5564">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude</label>
                        <input type="number" class="form-control" id="gpsLng" step="0.000001" placeholder="e.g., 104.9282">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Location Name</label>
                    <input type="text" class="form-control" id="gpsLocation" placeholder="e.g., Phnom Penh Warehouse">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateGPS()">Update Location</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table th { font-weight: 600; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[4, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });

    function focusShipment(id) {
        Swal.fire('Map Focus', 'Focusing on shipment #' + id + ' on the map.', 'info');
    }

    let currentTrackingId = null;

    function openGPSModal() {
        currentTrackingId = null;
        $('#gpsShipmentName').val('All Shipments');
        $('#gpsLat').val('');
        $('#gpsLng').val('');
        $('#gpsLocation').val('');
        new bootstrap.Modal(document.getElementById('gpsModal')).show();
    }

    function openGPSModalFor(id, name) {
        currentTrackingId = id;
        $('#gpsShipmentName').val(name);
        $('#gpsLat').val('');
        $('#gpsLng').val('');
        $('#gpsLocation').val('');
        new bootstrap.Modal(document.getElementById('gpsModal')).show();
    }

    function updateGPS() {
        const lat = $('#gpsLat').val();
        const lng = $('#gpsLng').val();
        const location = $('#gpsLocation').val();

        if (!lat || !lng) {
            Swal.fire('Warning', 'Please enter latitude and longitude.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Update Location?',
            text: 'GPS coordinates will be updated.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Success', 'GPS location updated successfully.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('gpsModal')).hide();
            }
        });
    }
</script>
@endpush
