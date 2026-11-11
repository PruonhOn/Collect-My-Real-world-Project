@extends('layouts.admin')
@section('title', 'Suppliers')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Suppliers</h1>
        <a href="{{ route('suppliers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">+ New Supplier</a>
    </div>
    <form method="GET" action="{{ route('suppliers.index') }}" class="mb-4">
        <input type="text" name="search" placeholder="Search suppliers..." value="{{ request('search') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Contact Person</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">City</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $supplier->name }}</td>
                    <td class="px-4 py-3">{{ $supplier->contact_person ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $supplier->email }}</td>
                    <td class="px-4 py-3">{{ $supplier->phone }}</td>
                    <td class="px-4 py-3">{{ $supplier->city ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if ($supplier->status == 'active')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Active</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 flex gap-2">
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Edit</a>
                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No suppliers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $suppliers->links() }}
    </div>
</div>
@endsection
