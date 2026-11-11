<x-admin-layout>
    @section('title', 'Shipments - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Shipments</li>
    @endsection

    @section('page-title', 'Shipments')
    @section('page-subtitle', 'Manage all shipments')

    @section('page-actions')
        <a href="{{ route('shipments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create Shipment
        </a>
    @endsection

    @php
        // Normalize to a plain collection whether $shipments is paginated or not
        $shipmentCollection = $shipments instanceof \Illuminate\Contracts\Pagination\Paginator
            || $shipments instanceof \Illuminate\Pagination\AbstractPaginator
                ? $shipments->getCollection()
                : $shipments;

        $statusColorMap = [
            'pending' => '#ffc107',
            'booked' => '#0dcaf0',
            'in_transit' => '#0d6efd',
            'arrived' => '#6c757d',
            'out_for_delivery' => '#212529',
            'delivered' => '#198754',
            'cancelled' => '#dc3545',
        ];

        $statusCounts = $shipmentCollection->groupBy('status')->map->count();
        $statusLabels = $statusCounts->keys()->map(fn($s) => str_replace('_', ' ', ucfirst($s)))->values();
        $statusData = $statusCounts->values();
        $statusBg = $statusCounts->keys()->map(fn($s) => $statusColorMap[$s] ?? '#adb5bd')->values();

        $typeCounts = $shipmentCollection->groupBy('type')->map->count();
        $typeLabels = $typeCounts->keys()->map(fn($t) => ucfirst($t))->values();
        $typeData = $typeCounts->values();
    @endphp

    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Shipments by Status</h6>
                </div>
                <div class="card-body">
                    @if($statusCounts->isEmpty())
                        <p class="text-muted text-center my-4 mb-0">No data to display</p>
                    @else
                        <canvas id="statusChart" height="220"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Shipments by Type</h6>
                </div>
                <div class="card-body">
                    @if($typeCounts->isEmpty())
                        <p class="text-muted text-center my-4 mb-0">No data to display</p>
                    @else
                        <canvas id="typeChart" height="220"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('shipments.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label for="tracking_number" class="form-label">Tracking #</label>
                    <input type="text" id="tracking_number" name="tracking_number" class="form-control form-control-sm" value="{{ request('tracking_number') }}" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select id="type" name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="import" {{ request('type') === 'import' ? 'selected' : '' }}>Import</option>
                        <option value="export" {{ request('type') === 'export' ? 'selected' : '' }}>Export</option>
                        <option value="domestic" {{ request('type') === 'domestic' ? 'selected' : '' }}>Domestic</option>
                        <option value="transit" {{ request('type') === 'transit' ? 'selected' : '' }}>Transit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="booked" {{ request('status') === 'booked' ? 'selected' : '' }}>Booked</option>
                        <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="arrived" {{ request('status') === 'arrived' ? 'selected' : '' }}>Arrived</option>
                        <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="customer_id" class="form-label">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-select form-select-sm select2">
                        <option value="">All Customers</option>
                        @foreach($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable" id="shipmentsTable">
                <thead class="table-light">
                    <tr>
                        <th>Tracking #</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Status</th>
                        <th>Shipment Date</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments as $shipment)
                        <tr>
                            <td>
                                <a href="{{ route('shipments.show', $shipment) }}" class="text-decoration-none fw-medium">
                                    {{ $shipment->tracking_number }}
                                </a>
                            </td>
                            <td><span class="badge bg-info">{{ ucfirst($shipment->type) }}</span></td>
                            <td>{{ $shipment->customer->name ?? '—' }}</td>
                            <td>{{ $shipment->origin_city ?? '—' }}</td>
                            <td>{{ $shipment->destination_city ?? '—' }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'booked' => 'info',
                                        'in_transit' => 'primary',
                                        'arrived' => 'secondary',
                                        'out_for_delivery' => 'dark',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                    $color = $statusColors[$shipment->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ str_replace('_', ' ', ucfirst($shipment->status)) }}
                                </span>
                            </td>
                            <td>{{ $shipment->shipment_date ? $shipment->shipment_date->format('d M Y') : '—' }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('shipments.show', $shipment) }}" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('shipments.edit', $shipment) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('shipments.destroy', $shipment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shipment?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                                <p class="mb-0">No shipments found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            });

            @if($statusCounts->isNotEmpty())
            const statusChart = new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: {!! $statusLabels->toJson() !!},
                    datasets: [{
                        data: {!! $statusData->toJson() !!},
                        backgroundColor: {!! $statusBg->toJson() !!},
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
            @endif

            @if($typeCounts->isNotEmpty())
            const typeChart = new Chart(document.getElementById('typeChart'), {
                type: 'bar',
                data: {
                    labels: {!! $typeLabels->toJson() !!},
                    datasets: [{
                        label: 'Shipments',
                        data: {!! $typeData->toJson() !!},
                        backgroundColor: '#0d6efd',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
            @endif
        </script>
    @endpush
</x-admin-layout>