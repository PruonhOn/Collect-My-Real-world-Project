@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Categories')

@push('styles')
<style>
    .category-tree { list-style: none; padding-left: 0; }
    .category-tree ul { list-style: none; padding-left: 1.5rem; }
    .category-tree li { padding: 0.25rem 0; }
</style>
@endpush

@section('content')
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0"><i class="bi bi-tags me-2 text-primary"></i>All Categories</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Category
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="categoriesTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories ?? [] as $category)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $category->name }}</td>
                        <td><code>{{ $category->slug }}</code></td>
                        <td>{{ $category->parent->name ?? '—' }}</td>
                        <td>{!! $category->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                        <td>{{ $category->creator->name ?? '—' }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Edit" onclick="editCategory({{ $category->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete({{ $category->id }}, '{{ $category->name }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="categoryForm" method="POST" action="{{ route('categories.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="POST">
            <input type="hidden" name="id" id="categoryId">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-tags me-2"></i>Add Category</h5>
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
                    <div class="col-md-6">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" id="parentId" class="form-select">
                            <option value="">— None —</option>
                            @foreach($parentCategories ?? [] as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
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
        table = $('#categoriesTable').DataTable({
            pageLength: 25,
            order: [[1, 'asc']]
        });

        $('#name').on('keyup', function () {
            $('#slug').val($(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''));
        });
    });

    function resetForm() {
        $('#categoryForm')[0].reset();
        $('#methodField').val('POST');
        $('#categoryForm').attr('action', '{{ route("categories.store") }}');
        $('#modalTitle').html('<i class="bi bi-tags me-2"></i>Add Category');
        $('#categoryId').val('');
    }

    function editCategory(id) {
        $.get('{{ url("categories") }}/' + id, function (data) {
            $('#modalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Category');
            $('#methodField').val('PUT');
            $('#categoryForm').attr('action', '{{ url("categories") }}/' + id);
            $('#categoryId').val(data.id);
            $('#name').val(data.name);
            $('#slug').val(data.slug);
            $('#parentId').val(data.parent_id || '');
            $('#status').val(data.status);
            $('#description').val(data.description);
            $('#categoryModal').modal('show');
        });
    }

    function confirmDelete(id, name) {
        $('#deleteItemName').text(name);
        $('#deleteForm').attr('action', '{{ url("categories") }}/' + id);
        $('#deleteModal').modal('show');
    }
</script>
@endpush
