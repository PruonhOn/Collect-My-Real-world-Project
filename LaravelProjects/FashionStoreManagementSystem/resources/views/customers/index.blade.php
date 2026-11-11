@extends('layouts.app')

@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'Manage your customer database and loyalty points')

@section('content')
    <div class="card">
        <div class="card-header bg-transparent">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <form class="d-flex gap-2" method="GET" action="{{ route('customers.index') }}">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:280px" placeholder="Search name, phone, email...">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                    @if (request()->has('q'))
                        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-person-plus me-1"></i>Add Customer</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-center">Orders</th>
                        <th class="text-center">Loyalty Points</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                                    <strong>{{ $customer->name }}</strong>
                                </div>
                            </td>
                            <td>{{ $customer->phone ?? '—' }}</td>
                            <td>{{ $customer->email ?? '—' }}</td>
                            <td class="text-center"><span class="badge text-bg-light border">{{ $customer->order_count }}</span></td>
                            <td class="text-center">
                                @if ($customer->loyalty_points > 0)
                                    <span class="badge bg-gold-subtle text-warning">{{ $customer->loyalty_points }} pts</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-soft"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-soft"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline" onsubmit="return confirm('Delete customer &quot;{{ $customer->name }}&quot;?')">
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
                                    <i class="bi bi-people"></i>
                                    <p>No customers found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $customers->links() }}</div>
    </div>
@endsection
