@extends('layouts.app')

@section('title', 'Staff')
@section('heading', 'Staff Accounts')
@section('subheading', 'Create and manage store staff accounts and roles')

@section('content')
    <div class="card">
        <div class="card-header bg-transparent">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <form class="d-flex gap-2" method="GET" action="{{ route('staff.index') }}">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Search name or email...">
                    <select name="role" class="form-select form-select-sm" style="max-width:160px">
                        <option value="">All roles</option>
                        <option value="admin" @selected(request('role') == 'admin')>Admin</option>
                        <option value="manager" @selected(request('role') == 'manager')>Manager</option>
                        <option value="cashier" @selected(request('role') == 'cashier')>Cashier</option>
                    </select>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
                    @if (request()->hasAny(['q', 'role']))
                        <a href="{{ route('staff.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
                <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-person-plus me-1"></i>Add Staff</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Staff</th>
                        <th>Role</th>
                        <th class="text-center">Sales Made</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $member->name }}</strong>
                                        <div class="small text-muted">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $member->role->value === 'admin' ? 'bg-primary-subtle text-primary' : ($member->role->value === 'manager' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary') }}">
                                    {{ $member->roleLabel() }}
                                </span>
                            </td>
                            <td class="text-center"><span class="badge text-bg-light border">{{ $member->orders_count }}</span></td>
                            <td>{{ $member->created_at->format('d M Y') }}</td>
                            <td>
                                @if ($member->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Deactivated</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-soft"><i class="bi bi-pencil"></i></a>
                                @if ($member->id !== auth()->id())
                                    <form method="POST" action="{{ route('staff.toggle', $member) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-soft {{ $member->is_active ? 'text-danger' : 'text-success' }}"
                                                onclick="return confirm('{{ $member->is_active ? 'Deactivate' : 'Activate' }} {{ $member->name }}?')"
                                                title="{{ $member->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi {{ $member->is_active ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state"><i class="bi bi-person-badge"></i><p>No staff found.</p></div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">{{ $staff->links() }}</div>
    </div>
@endsection
