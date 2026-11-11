@extends('layouts.admin')

@section('title', 'Shipment Details')
@section('subtitle', 'Shipment #' . $shipment->shipment_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Shipment #{{ $shipment->shipment_number }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shipments.index') }}">Shipments</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        @if($shipment->status === 'pending')
        <a href="{{ route('shipments.approve', $shipment) }}" class="btn btn-success" onclick="event.preventDefault(); document.getElementById('approveForm').submit();">
            <i class="bi bi-check-circle me-1"></i> Approve
        </a>
        <form id="approveForm" action="{{ route('shipments.approve', $shipment) }}" method="POST" class="d-none">@csrf</form>
        @endif
        @if(!in_array($shipment->status, ['cancelled', 'delivered']))
        <a href="#" class="btn btn-danger" onclick="cancelShipment({{ $shipment->id }})">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </a>
        @endif
        <button type="button" class="btn btn-outline-primary" onclick="printLabel()">
            <i class="bi bi-printer me-1"></i> Print Label
        </button>
        <button type="button" class="btn btn-outline-info" onclick="generateBarcode()">
            <i class="bi bi-upc-scan me-1"></i> Barcode
        </button>
        <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <ul class="nav nav-tabs" id="shipmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#info" type="button">
                            <i class="bi bi-info-circle me-1"></i> Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#items" type="button">
                            <i class="bi bi-box me-1"></i> Items
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#packages" type="button">
                            <i class="bi bi-boxes me-1"></i> Packages
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tracking" type="button">
                            <i class="bi bi-geo-alt me-1"></i> Tracking
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#financial" type="button">
                            <i class="bi bi-cash me-1"></i> Financial
                        </button>
                    </li>
                </ul>
                <div class="tab-content p-4">
                    <div class="tab-pane fade show active" id="info">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Sender Information</h6>
                                <p class="mb-1"><strong>{{ $shipment->sender_name }}</strong></p>
                                <p class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $shipment->sender_phone }}</p>
                                <p class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $shipment->sender_address }}</p>
                                <p class="mb-0"><i class="bi bi-building me-2"></i>{{ $shipment->sender_city }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Recipient Information</h6>
                                <p class="mb-1"><strong>{{ $shipment->recipient_name }}</strong></p>
                                <p class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $shipment->recipient_phone }}</p>
                                <p class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $shipment->recipient_address }}</p>
                                <p class="mb-0"><i class="bi bi-building me-2"></i>{{ $shipment->recipient_city }}</p>
                            </div>
                            <div class="col-12"><hr></div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Type</small>
                                <span class="badge bg-light text-dark">{{ ucfirst($shipment->type) }}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Priority</small>
                                <span class="badge bg-light text-dark">{{ ucfirst($shipment->priority) }}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Weight</small>
                                <span>{{ number_format($shipment->total_weight ?? 0, 2) }} kg</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Customer</small>
                                <span>{{ $shipment->customer->name ?? 'N/A' }}</span>
                            </div>
                            @if($shipment->notes)
                            <div class="col-12">
                                <small class="text-muted d-block">Notes</small>
                                <span>{{ $shipment->notes }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="tab-pane fade" id="items">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Weight</th>
                                        <th>Dimensions</th>
                                        <th>Unit</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($shipment->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->weight, 2) }} kg</td>
                                        <td>{{ $item->length }}x{{ $item->width }}x{{ $item->height }} cm</td>
                                        <td>{{ $item->unit }}</td>
                                        <td>${{ number_format($item->declared_value, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="7" class="text-center text-muted py-3">No items</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="packages">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Package #</th>
                                        <th>Barcode</th>
                                        <th>Type</th>
                                        <th>Weight</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($shipment->packages as $package)
                                    <tr>
                                        <td>{{ $package->package_number }}</td>
                                        <td><code>{{ $package->barcode }}</code></td>
                                        <td>{{ ucfirst($package->type) }}</td>
                                        <td>{{ number_format($package->weight, 2) }} kg</td>
                                        <td><span class="badge bg-info">{{ ucfirst($package->status) }}</span></td>
                                        <td>{{ $package->warehouse_id ? $package->warehouse->name : '-' }} {{ $package->shelf ? '/ ' . $package->shelf : '' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No packages</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tracking">
                        <div class="tracking-timeline">
                            @forelse($shipment->trackingHistory as $track)
                            <div class="timeline-item">
                                <div class="timeline-dot bg-{{ $track->status === 'delivered' ? 'success' : ($track->status === 'in_transit' ? 'primary' : 'secondary') }}"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $track->status)) }}</h6>
                                        <small class="text-muted">{{ $track->created_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                    <p class="mb-0 text-muted">{{ $track->location ?? 'N/A' }}</p>
                                    @if($track->description)
                                    <p class="mb-0 small">{{ $track->description }}</p>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-geo-alt" style="font-size: 2rem;"></i>
                                <p class="mt-2">No tracking history</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="tab-pane fade" id="financial">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title">Invoice Details</h6>
                                        @if($shipment->invoice)
                                        <p class="mb-1">Invoice #: <strong>{{ $shipment->invoice->invoice_number }}</strong></p>
                                        <p class="mb-1">Status: <span class="badge bg-{{ $shipment->invoice->status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($shipment->invoice->status) }}</span></p>
                                        <p class="mb-0">Amount: <strong>${{ number_format($shipment->invoice->total_amount, 2) }}</strong></p>
                                        @else
                                        <p class="text-muted mb-0">No invoice generated yet.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title">Cost Breakdown</h6>
                                        <p class="mb-1">Freight: ${{ number_format($shipment->freight_charge ?? 0, 2) }}</p>
                                        <p class="mb-1">Insurance: ${{ number_format($shipment->insurance ?? 0, 2) }}</p>
                                        <p class="mb-1">Tax: ${{ number_format($shipment->tax_amount ?? 0, 2) }}</p>
                                        @if(($shipment->discount ?? 0) > 0)
                                        <p class="mb-1 text-danger">Discount: -${{ number_format($shipment->discount, 2) }}</p>
                                        @endif
                                        <hr class="my-2">
                                        <p class="mb-0 fw-bold">Total: ${{ number_format($shipment->total_amount ?? 0, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Status</h6>
            </div>
            <div class="card-body text-center">
                @switch($shipment->status)
                    @case('pending')
                        <span class="badge bg-warning text-dark fs-6">Pending</span>
                        @break
                    @case('approved')
                        <span class="badge bg-info fs-6">Approved</span>
                        @break
                    @case('in_transit')
                        <span class="badge bg-primary fs-6">In Transit</span>
                        @break
                    @case('delivered')
                        <span class="badge bg-success fs-6">Delivered</span>
                        @break
                    @case('cancelled')
                        <span class="badge bg-danger fs-6">Cancelled</span>
                        @break
                @endswitch
                <p class="text-muted mt-2 mb-0">Created: {{ $shipment->created_at->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">QR Code</h6>
            </div>
            <div class="card-body text-center">
                <div class="bg-light rounded p-3 d-inline-block">
                    <i class="bi bi-qr-code" style="font-size: 5rem; color: #333;"></i>
                </div>
                <p class="text-muted mt-2 mb-0 small">{{ $shipment->shipment_number }}</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-semibold">Quick Info</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Shipment #</span>
                        <span class="fw-semibold">{{ $shipment->shipment_number }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Type</span>
                        <span>{{ ucfirst($shipment->type) }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Items</span>
                        <span>{{ $shipment->items->count() }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Packages</span>
                        <span>{{ $shipment->packages->count() }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span class="text-muted">Total</span>
                        <span class="fw-bold text-primary">${{ number_format($shipment->total_amount ?? 0, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tracking-timeline { position: relative; padding-left: 2rem; }
    .tracking-timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
    .timeline-item { position: relative; padding-bottom: 1.5rem; }
    .timeline-dot { position: absolute; left: -2rem; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #dee2e6; }
    .nav-tabs .nav-link { font-size: 0.875rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function cancelShipment(id) {
        Swal.fire({
            title: 'Cancel Shipment?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/admin/shipments/${id}/cancel`;
            }
        });
    }

    function printLabel() {
        window.print();
    }

    function generateBarcode() {
        Swal.fire({
            title: 'Barcode',
            html: '<div class="text-center"><i class="bi bi-upc-scan" style="font-size: 4rem;"></i><p class="mt-2">{{ $shipment->shipment_number }}</p></div>',
            confirmButtonText: 'Close'
        });
    }
</script>
@endpush
