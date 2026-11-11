@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>All Employees</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Employee
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="employeesTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Employee Code</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees ?? [] as $employee)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ $employee->employee_code }}</code></td>
                        <td><span class="fw-semibold">{{ $employee->full_name }}</span></td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->phone }}</td>
                        <td>{{ $employee->position ?? '—' }}</td>
                        <td>{{ $employee->department ?? '—' }}</td>
                        <td>{{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '—' }}</td>
                        <td>{!! $employee->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editEmployee({{ $employee->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $employee->id }}, '{{ $employee->full_name }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="employeeForm" method="POST" action="{{ route('employees.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="employeeId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-person-badge me-2"></i>Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                        <input type="text" name="employee_code" id="employeeCode" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="fullName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="phone" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" id="position" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="department" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" id="hireDate" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="2"></textarea>
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
        table = $('#employeesTable').DataTable({ pageLength: 25, order: [[2, 'asc']] });
    });

    function resetForm() {
        $('#employeeForm')[0].reset();
        $('#methodField').val('POST');
        $('#employeeForm').attr('action', '{{ route("employees.store") }}');
        $('#modalTitle').html('<i class="bi bi-person-badge me-2"></i>Add Employee');
        $('#employeeId').val('');
    }

    function editEmployee(id) {
        $.get('{{ url("employees") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Employee');
            $('#methodField').val('PUT');
            $('#employeeForm').attr('action', '{{ url("employees") }}/' + id);
            $('#employeeId').val(data.id);
            $('#employeeCode').val(data.employee_code);
            $('#fullName').val(data.full_name);
            $('#email').val(data.email);
            $('#phone').val(data.phone);
            $('#position').val(data.position);
            $('#department').val(data.department);
            $('#hireDate').val(data.hire_date ? data.hire_date.substring(0, 10) : '');
            $('#address').val(data.address);
            $('#status').val(data.status);
            $('#employeeModal').modal('show');
        });
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("employees") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
