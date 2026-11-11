@extends('layouts.admin')

@section('title', 'Customers')
@section('subtitle', 'Manage customer accounts')

@section('content')
<x-breadcrumb :items="[['label' => 'Customers']]" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Customers</h4>
        <p class="text-muted mb-0">Manage all customer accounts and their details</p>
    </div>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Customer
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customersTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Credit Limit</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $customer->code }}</span></td>
                        <td class="fw-medium">{{ $customer->name }}</td>
                        <td>{{ $customer->company_name ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td class="text-end">${{ number_format($customer->credit_limit ?? 0, 2) }}</td>
                        <td class="text-end">
                            @php $balance = $customer->balance ?? 0; @endphp
                            <span class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format(abs($balance), 2) }}
                                @if($balance > 0) (Dr) @endif
                            </span>
                        </td>
                        <td>
                            @if($customer->is_active ?? true)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="d-inline delete-form">
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
        $('#customersTable').DataTable({
            responsive: true,
            order: [[1, 'asc']],
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search customers...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ customers',
                emptyTable: 'No customers found',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            }
        });

        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This customer will be permanently deleted.',
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
