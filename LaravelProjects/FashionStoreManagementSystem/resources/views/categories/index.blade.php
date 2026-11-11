@extends('layouts.app')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Organise products with main categories and subcategories')

@section('content')
    <div class="card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted small">{{ $categories->total() }} categories</span>
            </div>
            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Category</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Category</th>
                        <th>Parent</th>
                        <th>Description</th>
                        <th class="text-center">Subcategories</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong> <small class="text-muted">/{{ $category->slug }}</small></td>
                            <td>{{ $category->parent?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ \Illuminate\Support\Str::limit($category->description, 60) }}</td>
                            <td class="text-center">
                                @if ($category->children->count())
                                    <span class="badge text-bg-light border">{{ $category->children->count() }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-soft"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Delete category &quot;{{ $category->name }}&quot;? Its subcategories and products will also be affected.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-soft text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-tags"></i>
                                    <p>No categories yet. Create your first one!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $categories->links() }}</div>
    </div>
@endsection
