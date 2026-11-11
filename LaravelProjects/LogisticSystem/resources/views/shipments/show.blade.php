<x-admin-layout>
    @section('title', $shipment->tracking_number . ' - Shipments - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('shipments.index') }}">Shipments</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $shipment->tracking_number }}</li>
    @endsection

    @section('page-title', $shipment->tracking_number)
    @section('page-subtitle', $shipment->customer->name ?? '—')

    @section('page-actions')
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-gear me-1"></i> Actions
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="bi bi-arrow-up-circle me-2"></i>Update Status
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignDriverModal">
                        <i class="bi bi-person-badge me-2"></i>Assign Driver
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a href="{{ route('shipments.edit', $shipment) }}" class="dropdown-item">
                        <i class="bi bi-pencil me-2"></i>Edit Shipment
                    </a>
                </li>
                <li>
                    <a href="#" class="dropdown-item" onclick="window.print(); return false;">
                        <i class="bi bi-printer me-2"></i>Print Label
                    </a>
                </li>
            </ul>
        </div>
    @endsection

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Tracking Progress</h5>
                    @php
                        $progressSteps = ['pending', 'booked', 'in_transit', 'arrived', 'out_for_delivery', 'delivered'];
                        $currentIndex = array_search($shipment->status, $progressSteps);
                        $progress = $currentIndex !== false ? (($currentIndex + 1) / count($progressSteps)) * 100 : 0;
                    @endphp
                    <span class="badge bg-{{ ['warning', 'info', 'primary', 'secondary', 'dark', 'success'][$currentIndex] ?? 'secondary' }} fs-6">
                        {{ str_replace('_', ' ', ucfirst($shipment->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span class="{{ $currentIndex >= 0 ? 'text-success fw-medium' : '' }}">Pending</span>
                        <span class="{{ $currentIndex >= 1 ? 'text-success fw-medium' : '' }}">Booked</span>
                        <span class="{{ $currentIndex >= 2 ? 'text-success fw-medium' : '' }}">In Transit</span>
                        <span class="{{ $currentIndex >= 3 ? 'text-success fw-medium' : '' }}">Arrived</span>
                        <span class="{{ $currentIndex >= 4 ? 'text-success fw-medium' : '' }}">Out for Delivery</span>
                        <span class="{{ $currentIndex >= 5 ? 'text-success fw-medium' : '' }}">Delivered</span>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Shipment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Shipment Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">Tracking #</td>
                                    <td class="fw-medium">{{ $shipment->tracking_number }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Type</td>
                                    <td><span class="badge bg-info">{{ ucfirst($shipment->type) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Shipment Date</td>
                                    <td>{{ $shipment->shipment_date ? $shipment->shipment_date->format('d M Y') : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status</td>
                                    <td>
                                        @php
                                            $statusColors = ['pending' => 'warning', 'booked' => 'info', 'in_transit' => 'primary', 'arrived' => 'secondary', 'out_for_delivery' => 'dark', 'delivered' => 'success', 'cancelled' => 'danger'];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$shipment->status] ?? 'secondary' }}">
                                            {{ str_replace('_', ' ', ucfirst($shipment->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Customer</td>
                                    <td>{{ $shipment->customer->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Express</td>
                                    <td>
                                        @if($shipment->is_express)
                                            <span class="badge bg-warning"><i class="bi bi-lightning me-1"></i>Express</span>
                                        @else
                                            <span class="badge bg-secondary">Standard</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Insured</td>
                                    <td>
                                        @if($shipment->is_insured)
                                            <span class="badge bg-info"><i class="bi bi-shield-check me-1"></i>Insured (${{ number_format($shipment->insurance_amount ?? 0, 2) }})</span>
                                        @else
                                            <span class="badge bg-secondary">Not Insured</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Cargo Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">Description</td>
                                    <td class="fw-medium">{{ $shipment->description ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Weight</td>
                                    <td>{{ $shipment->weight ? number_format($shipment->weight, 2) . ' kg' : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Volume</td>
                                    <td>{{ $shipment->volume ? number_format($shipment->volume, 2) . ' m³' : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pieces</td>
                                    <td>{{ $shipment->pieces ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Declared Value</td>
                                    <td class="fw-medium">{{ $shipment->currency ?? 'USD' }} {{ number_format($shipment->declared_value ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Origin</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Branch</td>
                                    <td class="fw-medium">{{ $shipment->originBranch->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Address</td>
                                    <td>{{ $shipment->origin_address ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">City</td>
                                    <td>{{ $shipment->origin_city ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Province</td>
                                    <td>{{ $shipment->origin_province ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Destination</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Branch</td>
                                    <td class="fw-medium">{{ $shipment->destinationBranch->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Address</td>
                                    <td>{{ $shipment->destination_address ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">City</td>
                                    <td>{{ $shipment->destination_city ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Province</td>
                                    <td>{{ $shipment->destination_province ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Shipper</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Name</td>
                                    <td class="fw-medium">{{ $shipment->shipper_name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td>{{ $shipment->shipper_phone ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email</td>
                                    <td>{{ $shipment->shipper_email ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Consignee</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 100px;">Name</td>
                                    <td class="fw-medium">{{ $shipment->consignee_name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td>{{ $shipment->consignee_phone ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email</td>
                                    <td>{{ $shipment->consignee_email ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Status Timeline</h5>
                </div>
                <div class="card-body">
                    @include('shipments.partials.status-timeline', ['statuses' => $shipment->statuses ?? collect()])
                </div>
            </div>

            @if($shipment->delivery)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 fw-bold">Delivery Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted" style="width: 130px;">Assigned To</td>
                                        <td class="fw-medium">{{ $shipment->delivery->driver->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Vehicle</td>
                                        <td>{{ $shipment->delivery->vehicle->plate_number ?? '—' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted" style="width: 130px;">Route</td>
                                        <td class="fw-medium">{{ $shipment->delivery->route->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Delivery Date</td>
                                        <td>{{ $shipment->delivery->delivery_date ? $shipment->delivery->delivery_date->format('d M Y') : '—' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Shipment Items</h5>
                    <span class="badge bg-primary">{{ $shipment->items->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Weight (kg)</th>
                                    <th>Dimensions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipment->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-medium">{{ $item->description }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->weight ? number_format($item->weight, 2) : '—' }}</td>
                                        <td>{{ $item->dimensions ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No items added</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Documents</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($shipment->documents as $document)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark-text me-2 text-muted"></i>
                                    <span>{{ $document->name }}</span>
                                </div>
                                <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-4">
                                <i class="bi bi-file-earmark fs-2 d-block mb-2"></i>
                                <p class="mb-0 small">No documents uploaded</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Quick Info</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <span class="fw-medium small">{{ $shipment->created_at->format('d M Y H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Updated</span>
                        <span class="fw-medium small">{{ $shipment->updated_at->format('d M Y H:i') }}</span>
                    </li>
                    @if($shipment->createdBy)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Created By</span>
                            <span class="fw-medium small">{{ $shipment->createdBy->name }}</span>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Origin Branch</span>
                        <span class="fw-medium small">{{ $shipment->originBranch->code ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Dest. Branch</span>
                        <span class="fw-medium small">{{ $shipment->destinationBranch->code ?? '—' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('shipments.update-status', $shipment) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Update Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $shipment->status === 'pending' ? 'disabled' : '' }}>Pending</option>
                                <option value="booked" {{ $shipment->status === 'booked' ? 'disabled' : '' }}>Booked</option>
                                <option value="in_transit" {{ $shipment->status === 'in_transit' ? 'disabled' : '' }}>In Transit</option>
                                <option value="arrived" {{ $shipment->status === 'arrived' ? 'disabled' : '' }}>Arrived</option>
                                <option value="out_for_delivery" {{ $shipment->status === 'out_for_delivery' ? 'disabled' : '' }}>Out for Delivery</option>
                                <option value="delivered" {{ $shipment->status === 'delivered' ? 'disabled' : '' }}>Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status_note" class="form-label">Note</label>
                            <textarea id="status_note" name="note" class="form-control" rows="2" placeholder="Optional note about this status change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('shipments.assign-driver', $shipment) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Assign Driver</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="driver_id" class="form-label">Driver <span class="text-danger">*</span></label>
                            <select id="driver_id" name="driver_id" class="form-select select2-modal" required>
                                <option value="">Select Driver</option>
                                @foreach($drivers ?? [] as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->phone ?? '—' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_id" class="form-label">Vehicle <span class="text-danger">*</span></label>
                            <select id="vehicle_id" name="vehicle_id" class="form-select select2-modal" required>
                                <option value="">Select Vehicle</option>
                                @foreach($vehicles ?? [] as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} — {{ $vehicle->type ?? '—' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="delivery_date" class="form-label">Delivery Date</label>
                            <input type="date" id="delivery_date" name="delivery_date" class="form-control" value="{{ old('delivery_date', date('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2-modal').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#assignDriverModal')
                });
            });
        </script>
    @endpush
</x-admin-layout>
