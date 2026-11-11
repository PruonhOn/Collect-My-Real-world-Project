@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-tags me-2"></i>Categories</h4>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Category
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Products Count</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td class="fw-bold">{{ $category->name }}</td>
                        <td>{{ $category->description ?? 'N/A' }}</td>
                        <td><span class="badge bg-info">{{ $category->products_count }}</span></td>
                        <td>{{ $category->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form id="delete-cat-{{ $category->id }}" action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                    onclick="confirmDelete('delete-cat-{{ $category->id }}', '{{ $category->name }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-tags fa-3x mb-3 d-block"></i>
                            No categories found. <a href="{{ route('categories.create') }}">Add your first category</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection
