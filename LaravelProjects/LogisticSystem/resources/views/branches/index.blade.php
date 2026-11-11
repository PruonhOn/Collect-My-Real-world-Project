<x-admin-layout>
    @section('title', 'Branches - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Branches</li>
    @endsection

    @section('page-title', 'Branches')
    @section('page-subtitle', 'Manage all company branches')

    @section('page-actions')
        <a href="{{ route('branches.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create Branch
        </a>
    @endsection

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable" id="branchesTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>City</th>
                        <th>Province</th>
                        <th>Phone</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                            <td>
                                <a href="{{ route('branches.show', $branch) }}" class="text-decoration-none fw-medium">
                                    {{ $branch->name }}
                                </a>
                            </td>
                            <td>{{ $branch->city }}</td>
                            <td>{{ $branch->province }}</td>
                            <td>{{ $branch->phone }}</td>
                            <td>{{ $branch->manager_name ?? '—' }}</td>
                            <td>
                                @if($branch->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('branches.show', $branch) }}" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this branch?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-building fs-2 d-block mb-2"></i>
                                <p class="mb-0">No branches found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
