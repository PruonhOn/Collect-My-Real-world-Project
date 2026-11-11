@extends('layouts.admin')
@section('title', 'Create User')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create User</h1>
    @if ($errors->any())
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Password</label>
                <input type="password" name="password" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Role</label>
                <select name="role" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                    <option value="">Select Role</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="status" value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>
        </div>
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Create</button>
            <a href="{{ route('users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">Cancel</a>
        </div>
    </form>
</div>
@endsection
