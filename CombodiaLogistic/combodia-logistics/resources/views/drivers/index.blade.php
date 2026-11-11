@extends('layouts.admin')

@section('title', 'Drivers')
@section('subtitle', 'Manage fleet drivers')

@section('content')
<x-breadcrumb :items="[['label' => 'Drivers']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Drivers</h4>
        <p class="text-muted mb-0">Manage all drivers and their license information</p>
    </div>
    <a href="{{ route('admin.drivers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Driver
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="driversTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>License #</th>
                        <th>License Expiry</th>
                        <th>Vehicle</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($drivers as $driver)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $driver->code }}</span></td>
                        <td class="fw-medium">{{ $driver->name }}</td>
                        <td>{{ $driver->email ?? '-' }}</td>
                        <td>{{ $driver->phone ?? '-' }}</td>
                        <td>{{ $driver->license_number ?? '-' }}</td>
                        <td>
                            @if($driver->license_expiry && \Carbon\Carbon::parse($driver->license_expiry)->isPast())
                                <span class="text-danger fw-medium">{{ \Carbon\Carbon::parse($driver->license_expiry)->format('d M Y') }}</span>
                                <i class="bi bi-exclamation-triangle text-danger ms-1" style="font-size: 0.75rem;"></i>
                            @elseif($driver->license_expiry)
                                <span>{{ \Carbon\Carbon::parse($driver->license_expiry)->format('d M Y') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $driver->vehicle->plate_number ?? '-' }}</td>
                        <td>{{ $driver->branch->name ?? '-' }}</td>
                        <td>
                            @if($driver->is_active ?? true)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.drivers.edit', $driver->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#driversTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search drivers...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ drivers',
                emptyTable: 'No drivers found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This driver will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush
