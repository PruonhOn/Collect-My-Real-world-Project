{{-- resources/views/users/index.blade.php --}}
@extends('layouts.app')
@section('title','Users')
@section('page-title','Users')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="text-sm text-gray-500 mt-1">Manage system users and roles</p>
    </div>
    @can('manage users')
    <a href="{{ route('users.create') }}" class="btn-primary">+ Add User</a>
    @endcan
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Work Orders</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-600/20 text-purple-300 flex items-center justify-center text-xs font-semibold">
                            {{ $user->initials }}
                        </div>
                        <span class="font-medium text-sm">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="text-gray-400 text-sm">{{ $user->email }}</td>
                <td>
                    @foreach ($user->roles as $role)
                    <span class="badge-{{ match($role->name){'admin'=>'red','technician'=>'blue',default=>'gray'} }}">
                        {{ ucfirst($role->name) }}
                    </span>
                    @endforeach
                </td>
                <td>
                    <span class="badge-{{ $user->is_active ? 'green' : 'gray' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-gray-400 text-sm">{{ $user->workOrders->count() }}</td>
                <td>
                    <div class="flex gap-1">
                        <a href="{{ route('users.show',$user) }}" class="text-xs px-2 py-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition">View</a>
                        @can('manage users')
                        <a href="{{ route('users.edit',$user) }}" class="text-xs px-2 py-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition">Edit</a>
                        <form action="{{ route('users.toggle-active',$user) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-xs px-2 py-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition">
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-10 text-gray-600">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
    <div class="px-5 py-3 border-t border-white/[0.06]">{{ $users->links() }}</div>
    @endif
</div>
@endsection


{{-- resources/views/users/create.blade.php --}}
@extends('layouts.app')
@section('title','Add User')
@section('page-title','Add User')

@section('content')
<div class="max-w-md">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
        <a href="{{ route('users.index') }}" class="hover:text-gray-300">Users</a>
        <span>/</span><span class="text-gray-300">New</span>
    </div>
    <div class="card p-6">
        <h2 class="text-base font-semibold mb-5">Add New User</h2>
        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Full Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="input w-full" required>
                @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" class="input w-full" required>
                @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Password *</label>
                <input type="password" name="password" class="input w-full" required minlength="8">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Confirm Password *</label>
                <input type="password" name="password_confirmation" class="input w-full" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Role *</label>
                <select name="role" class="input w-full" required>
                    @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role')===$role->name)>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection
