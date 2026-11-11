<x-admin-layout>
    @section('title', 'Customers - ' . config('app.name'))

    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Customers</li>
    @endsection

    @section('page-title', 'Customers')
    @section('page-subtitle', 'Manage all customers')

    @section('page-actions')
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create Customer
        </a>
    @endsection

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable" id="customersTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>City</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $customer->code }}</span></td>
                            <td>
                                <a href="{{ route('customers.show', $customer) }}" class="text-decoration-none fw-medium">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td>{{ $customer->company ?? '—' }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->city ?? '—' }}</td>
                            <td>
                                @php
                                    $outstanding = $customer->outstanding_balance ?? 0;
                                @endphp
                                <span class="@if($outstanding > 0) text-danger fw-medium @else text-muted @endif">
                                    ${{ number_format($outstanding, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($customer->is_active ?? true)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?')">
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
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-people fs-2 d-block mb-2"></i>
                                <p class="mb-0">No customers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
