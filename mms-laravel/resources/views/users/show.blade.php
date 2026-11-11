{{-- resources/views/users/show.blade.php --}}
@extends('layouts.app')
@section('title', $user->name)
@section('page-title', 'User Profile')

@section('content')
<div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
    <a href="{{ route('users.index') }}" class="hover:text-gray-300">Users</a>
    <span>/</span>
    <span class="text-gray-300">{{ $user->name }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Profile card --}}
    <div class="card p-6">
        <div class="flex flex-col items-center text-center mb-5">
            <div class="w-16 h-16 rounded-full bg-purple-600/20 text-purple-300 flex items-center justify-center text-xl font-bold mb-3">
                {{ $user->initials }}
            </div>
            <h2 class="text-base font-semibold">{{ $user->name }}</h2>
            <p class="text-sm text-gray-500">{{ $user->email }}</p>
            <div class="flex gap-2 mt-2">
                @foreach ($user->roles as $role)
                <span class="badge-{{ match($role->name) { 'admin' => 'red', 'technician' => 'blue', default => 'gray' } }}">
                    {{ ucfirst($role->name) }}
                </span>
                @endforeach
            </div>
            <span class="mt-2 badge-{{ $user->is_active ? 'green' : 'gray' }}">
                {{ $user->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        <div class="border-t border-white/[0.06] pt-4 space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Joined</span>
                <span class="text-gray-300">{{ $user->created_at->format('M d, Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Work Orders</span>
                <span class="text-gray-300">{{ $user->workOrders->count() }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Completed</span>
                <span class="text-green-400">{{ $user->workOrders->where('status','completed')->count() }}</span>
            </div>
        </div>

        @can('manage users')
        <div class="mt-5 pt-4 border-t border-white/[0.06] flex flex-col gap-2">
            <a href="{{ route('users.edit', $user) }}" class="btn-secondary text-sm text-center">Edit User</a>
            <form action="{{ route('users.toggle-active', $user) }}" method="POST">
                @csrf @method('PATCH')
                <button class="w-full btn-{{ $user->is_active ? 'danger' : 'secondary' }} text-sm">
                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
        @endcan
    </div>

    {{-- Work orders --}}
    <div class="lg:col-span-2 card p-5">
        <h3 class="text-sm font-semibold mb-4">Assigned Work Orders</h3>
        <table class="data-table">
            <thead>
                <tr><th>Code</th><th>Title</th><th>Priority</th><th>Status</th><th>Due</th></tr>
            </thead>
            <tbody>
                @forelse ($user->workOrders->take(15) as $wo)
                <tr>
                    <td class="font-mono text-xs text-gray-500">{{ $wo->code }}</td>
                    <td><a href="{{ route('work-orders.show', $wo) }}" class="hover:text-blue-400 transition">{{ Str::limit($wo->title, 40) }}</a></td>
                    <td>
                        <span class="badge-{{ match($wo->priority) { 'critical','high' => 'red', 'medium' => 'yellow', default => 'green' } }}">
                            {{ ucfirst($wo->priority) }}
                        </span>
                    </td>
                    <td>
                        <span class="status-{{ $wo->status }} text-xs px-2 py-1 rounded-full font-medium">
                            {{ ucwords(str_replace('_',' ',$wo->status)) }}
                        </span>
                    </td>
                    <td class="text-xs {{ $wo->is_overdue ? 'text-red-400' : 'text-gray-500' }}">
                        {{ $wo->due_at?->format('M d') ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-gray-600 py-8">No work orders assigned.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection


{{-- resources/views/users/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="max-w-md">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
        <a href="{{ route('users.index') }}" class="hover:text-gray-300">Users</a>
        <span>/</span>
        <a href="{{ route('users.show', $user) }}" class="hover:text-gray-300">{{ $user->name }}</a>
        <span>/</span>
        <span class="text-gray-300">Edit</span>
    </div>

    <div class="card p-6">
        <h2 class="text-base font-semibold mb-5">Edit User</h2>
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="input w-full" required>
                @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input w-full" required>
                @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Role *</label>
                <select name="role" class="input w-full" required>
                    @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-gray-600">Leave password blank to keep unchanged.</p>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">New Password</label>
                <input type="password" name="password" class="input w-full" minlength="8">
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <a href="{{ route('users.show', $user) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
