@extends('layouts.admin')
@section('title', 'Purchases')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Purchases</h1>
        <a href="{{ route('purchases.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">+ New Purchase</a>
    </div>
    <form method="GET" action="{{ route('purchases.index') }}" class="mb-4">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="ordered" {{ request('status') == 'ordered' ? 'selected' : '' }}>Ordered</option>
                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Filter</button>
            <a href="{{ route('purchases.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Reset</a>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Reference No</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $purchase->reference_no }}</td>
                    <td class="px-4 py-3">{{ $purchase->supplier ? $purchase->supplier->name : '-' }}</td>
                    <td class="px-4 py-3">{{ $purchase->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ number_format($purchase->total, 2) }}</td>
                    <td class="px-4 py-3">
                        @if ($purchase->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                        @elseif ($purchase->status == 'ordered')
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">Ordered</span>
                        @elseif ($purchase->status == 'received')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Received</span>
                        @elseif ($purchase->status == 'cancelled')
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Cancelled</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('purchases.show', $purchase) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No purchases found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
</div>
@endsection
