@extends('layouts.app')
@section('title', 'Units')
@section('page-title', 'Units')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-rulers me-2 text-primary"></i>All Units</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#unitModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Unit
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="unitsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Short Name</th>
                        <th>Operator</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units ?? [] as $unit)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $unit->name }}</td>
                        <td><span class="badge bg-secondary">{{ $unit->short_name }}</span></td>
                        <td><code>{{ $unit->operator }}</code></td>
                        <td>{{ $unit->value }}</td>
                        <td>{!! $unit->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editUnit({{ $unit->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $unit->id }}, '{{ $unit->name }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No units found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="unitForm" method="POST" action="{{ route('units.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="unitId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-rulers me-2"></i>Add Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Short Name <span class="text-danger">*</span></label>
                        <input type="text" name="short_name" id="shortName" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Operator <span class="text-danger">*</span></label>
                        <select name="operator" id="operator" class="form-select" required>
                            <option value="*">Multiply (*)</option>
                            <option value="/">Divide (/)</option>
                            <option value="+">Add (+)</option>
                            <option value="-">Subtract (-)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Value <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="value" id="value" class="form-control" required>
                    </div>
                    <div class="col-md-4">
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
        table = $('#unitsTable').DataTable({ pageLength: 25, order: [[1, 'asc']] });
    });

    function resetForm() {
        $('#unitForm')[0].reset();
        $('#methodField').val('POST');
        $('#unitForm').attr('action', '{{ route("units.store") }}');
        $('#modalTitle').html('<i class="bi bi-rulers me-2"></i>Add Unit');
        $('#unitId').val('');
    }

    function editUnit(id) {
        $.get('{{ url("units") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Unit');
            $('#methodField').val('PUT');
            $('#unitForm').attr('action', '{{ url("units") }}/' + id);
            $('#unitId').val(data.id);
            $('#name').val(data.name);
            $('#shortName').val(data.short_name);
            $('#operator').val(data.operator);
            $('#value').val(data.value);
            $('#status').val(data.status);
            $('#unitModal').modal('show');
        });
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("units") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
