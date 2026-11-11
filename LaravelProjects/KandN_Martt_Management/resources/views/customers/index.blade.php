@extends('layouts.app')
@section('title', 'Customers')
@section('page-title', 'Customers')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>All Customers</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Customer
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="customersTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Membership No</th>
                        <th>Points</th>
                        <th>Total Purchases</th>
                        <th>Last Purchase</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers ?? [] as $customer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><span class="fw-semibold">{{ $customer->name }}</span></td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->email }}</td>
                        <td><code>{{ $customer->membership_no ?? '—' }}</code></td>
                        <td><span class="badge bg-warning text-dark">{{ number_format($customer->points ?? 0) }}</span></td>
                        <td>{{ $customer->total_purchases ? number_format($customer->total_purchases, 2) : '0.00' }}</td>
                        <td>{{ $customer->last_purchase_date ? $customer->last_purchase_date->format('Y-m-d') : '—' }}</td>
                        <td>{!! $customer->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-success" title="Add Points" onclick="addPoints({{ $customer->id }}, '{{ $customer->name }}', {{ $customer->points ?? 0 }})">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editCustomer({{ $customer->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $customer->id }}, '{{ $customer->name }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="customerForm" method="POST" action="{{ route('customers.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="customerId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-people me-2"></i>Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="phone" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Membership No</label>
                        <input type="text" name="membership_no" id="membershipNo" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="city" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" name="state" id="state" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal_code" id="postalCode" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="pointsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form id="pointsForm" method="POST" action="" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-success"></i>Add Points</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong id="pointsCustomerName"></strong></p>
                <p class="mb-3">Current Points: <span id="currentPoints" class="badge bg-warning text-dark fs-6">0</span></p>
                <label class="form-label">Points to Add <span class="text-danger">*</span></label>
                <input type="number" name="points" class="form-control" min="1" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-3">
                <p class="mb-0">Delete <strong id="deleteItemName"></strong>?</p>
                <small class="text-muted">This action cannot be undone.</small>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let table;
    $(document).ready(function () {
        table = $('#customersTable').DataTable({ pageLength: 25, order: [[1, 'asc']] });
    });

    function resetForm() {
        $('#customerForm')[0].reset();
        $('#methodField').val('POST');
        $('#customerForm').attr('action', '{{ route("customers.store") }}');
        $('#modalTitle').html('<i class="bi bi-people me-2"></i>Add Customer');
        $('#customerId').val('');
    }

    function editCustomer(id) {
        $.get('{{ url("customers") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Customer');
            $('#methodField').val('PUT');
            $('#customerForm').attr('action', '{{ url("customers") }}/' + id);
            $('#customerId').val(data.id);
            $('#name').val(data.name);
            $('#email').val(data.email);
            $('#phone').val(data.phone);
            $('#membershipNo').val(data.membership_no);
            $('#address').val(data.address);
            $('#city').val(data.city);
            $('#state').val(data.state);
            $('#postalCode').val(data.postal_code);
            $('#status').val(data.status);
            $('#customerModal').modal('show');
        });
    }

    function addPoints(id, name, points) {
        $('#pointsCustomerName').text(name);
        $('#currentPoints').text(points.toLocaleString());
        $('#pointsForm').attr('action', '{{ url("customers") }}/' + id + '/points');
        $('#pointsModal').modal('show');
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("customers") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
