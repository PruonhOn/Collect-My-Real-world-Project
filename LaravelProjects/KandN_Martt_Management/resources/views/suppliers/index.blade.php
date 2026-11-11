@extends('layouts.app')
@section('title', 'Suppliers')
@section('page-title', 'Suppliers')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-truck me-2 text-primary"></i>All Suppliers</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Supplier
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="suppliersTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Contact Person</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers ?? [] as $supplier)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><a href="#" class="text-decoration-none fw-semibold" onclick="viewSupplier({{ $supplier->id }})">{{ $supplier->name }}</a></td>
                        <td>{{ $supplier->phone }}</td>
                        <td>{{ $supplier->email }}</td>
                        <td>{{ $supplier->contact_person ?? '—' }}</td>
                        <td>{{ $supplier->city ?? '—' }}</td>
                        <td>{!! $supplier->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" title="View" onclick="viewSupplier({{ $supplier->id }})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editSupplier({{ $supplier->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $supplier->id }}, '{{ $supplier->name }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="supplierForm" method="POST" action="{{ route('suppliers.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="supplierId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-truck me-2"></i>Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
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
                        <label class="form-label">Tax ID</label>
                        <input type="text" name="tax_id" id="taxId" class="form-control">
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
                    <div class="col-md-2">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal_code" id="postalCode" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" id="country" class="form-control" value="Philippines">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="contactPerson" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" id="contactPhone" class="form-control">
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

<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Supplier Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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
        table = $('#suppliersTable').DataTable({ pageLength: 25, order: [[1, 'asc']] });
    });

    function resetForm() {
        $('#supplierForm')[0].reset();
        $('#methodField').val('POST');
        $('#supplierForm').attr('action', '{{ route("suppliers.store") }}');
        $('#modalTitle').html('<i class="bi bi-truck me-2"></i>Add Supplier');
        $('#supplierId').val('');
    }

    function editSupplier(id) {
        $.get('{{ url("suppliers") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Supplier');
            $('#methodField').val('PUT');
            $('#supplierForm').attr('action', '{{ url("suppliers") }}/' + id);
            $('#supplierId').val(data.id);
            $('#name').val(data.name);
            $('#email').val(data.email);
            $('#phone').val(data.phone);
            $('#taxId').val(data.tax_id);
            $('#address').val(data.address);
            $('#city').val(data.city);
            $('#state').val(data.state);
            $('#postalCode').val(data.postal_code);
            $('#country').val(data.country);
            $('#contactPerson').val(data.contact_person);
            $('#contactPhone').val(data.contact_phone);
            $('#status').val(data.status);
            $('#supplierModal').modal('show');
        });
    }

    function viewSupplier(id) {
        $('#viewContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $('#viewModal').modal('show');
        $.get('{{ url("suppliers") }}/' + id, function (data) {
            let html = `
            <div class="row g-3">
                <div class="col-md-6"><strong>Company:</strong><br>${data.name}</div>
                <div class="col-md-6"><strong>Email:</strong><br>${data.email || '—'}</div>
                <div class="col-md-6"><strong>Phone:</strong><br>${data.phone}</div>
                <div class="col-md-6"><strong>Tax ID:</strong><br>${data.tax_id || '—'}</div>
                <div class="col-12"><strong>Address:</strong><br>${data.address || '—'}</div>
                <div class="col-md-3"><strong>City:</strong><br>${data.city || '—'}</div>
                <div class="col-md-3"><strong>State:</strong><br>${data.state || '—'}</div>
                <div class="col-md-3"><strong>Postal:</strong><br>${data.postal_code || '—'}</div>
                <div class="col-md-3"><strong>Country:</strong><br>${data.country || '—'}</div>
                <div class="col-md-6"><strong>Contact Person:</strong><br>${data.contact_person || '—'}</div>
                <div class="col-md-6"><strong>Contact Phone:</strong><br>${data.contact_phone || '—'}</div>
                <div class="col-md-6"><strong>Status:</strong><br>${data.status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</div>
            </div>`;
            $('#viewContent').html(html);
        }).fail(function () {
            $('#viewContent').html('<div class="alert alert-danger mb-0">Failed to load supplier details.</div>');
        });
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("suppliers") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
