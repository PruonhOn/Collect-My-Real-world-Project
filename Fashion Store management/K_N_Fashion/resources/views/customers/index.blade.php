@extends('layouts.admin')
@section('title', 'Customers')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Customers</h1>
        <a href="{{ route('customers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">+ New Customer</a>
    </div>
    <form method="GET" action="{{ route('customers.index') }}" class="mb-4">
        <input type="text" name="search" placeholder="Search customers..." value="{{ request('search') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">City</th>
                    <th class="px-4 py-3">Loyalty Points</th>
                    <th class="px-4 py-3">Total Purchases</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $customer->name }}</td>
                    <td class="px-4 py-3">{{ $customer->email }}</td>
                    <td class="px-4 py-3">{{ $customer->phone ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $customer->city ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $customer->loyalty_points ?? 0 }}</td>
                    <td class="px-4 py-3">{{ $customer->total_purchases ? number_format($customer->total_purchases, 2) : '0.00' }}</td>
                    <td class="px-4 py-3 flex gap-2">
                        <a href="{{ route('customers.edit', $customer) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Edit</a>
                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No customers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>
@endsection
