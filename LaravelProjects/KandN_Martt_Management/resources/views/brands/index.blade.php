@extends('layouts.app')
@section('title', 'Brands')
@section('page-title', 'Brands')

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-bookmark me-2 text-primary"></i>All Brands</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#brandModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Brand
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="brandsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Logo</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands ?? [] as $brand)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $brand->name }}</td>
                        <td><code>{{ $brand->slug }}</code></td>
                        <td>
                            @if($brand->logo)
                            <img src="{{ asset('storage/' . $brand->logo) }}" alt="Logo" height="32" class="rounded">
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{!! $brand->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editBrand({{ $brand->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $brand->id }}, '{{ $brand->name }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No brands found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="brandForm" method="POST" action="{{ route('brands.store') }}" class="modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="brandId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-bookmark me-2"></i>Add Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                        <div id="logoPreview" class="mt-2 d-none">
                            <img src="" alt="Preview" height="48" class="rounded border">
                        </div>
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
        table = $('#brandsTable').DataTable({ pageLength: 25, order: [[1, 'asc']] });
        $('#name').on('keyup', function () {
            $('#slug').val($(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''));
        });
        $('#logo').on('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (ev) {
                    $('#logoPreview img').attr('src', ev.target.result);
                    $('#logoPreview').removeClass('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    });

    function resetForm() {
        $('#brandForm')[0].reset();
        $('#methodField').val('POST');
        $('#brandForm').attr('action', '{{ route("brands.store") }}');
        $('#modalTitle').html('<i class="bi bi-bookmark me-2"></i>Add Brand');
        $('#brandId').val('');
        $('#logoPreview').addClass('d-none');
    }

    function editBrand(id) {
        $.get('{{ url("brands") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Brand');
            $('#methodField').val('PUT');
            $('#brandForm').attr('action', '{{ url("brands") }}/' + id);
            $('#brandId').val(data.id);
            $('#name').val(data.name);
            $('#slug').val(data.slug);
            $('#description').val(data.description);
            $('#status').val(data.status);
            if (data.logo) {
                $('#logoPreview img').attr('src', '{{ asset("storage") }}/' + data.logo);
                $('#logoPreview').removeClass('d-none');
            }
            $('#brandModal').modal('show');
        });
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("brands") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
