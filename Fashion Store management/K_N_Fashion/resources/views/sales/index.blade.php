@extends('layouts.admin')
@section('title', 'Sales')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales</h1>
        <a href="{{ route('pos.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">+ New Sale</a>
    </div>
    <form method="GET" action="{{ route('sales.index') }}" class="mb-4">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2">
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Filter</button>
            <a href="{{ route('sales.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Reset</a>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Invoice No</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Payment Method</th>
                    <th class="px-4 py-3">Payment Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $sale->invoice_no }}</td>
                    <td class="px-4 py-3">{{ $sale->customer ? $sale->customer->name : 'Walk-in' }}</td>
                    <td class="px-4 py-3">{{ $sale->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ number_format($sale->grand_total, 2) }}</td>
                    <td class="px-4 py-3">{{ ucfirst($sale->payment_method) }}</td>
                    <td class="px-4 py-3">
                        @if ($sale->payment_status == 'paid')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Paid</span>
                        @elseif ($sale->payment_status == 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">{{ ucfirst($sale->payment_status) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 flex gap-2">
                        <a href="{{ route('sales.show', $sale) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">View</a>
                        <a href="{{ route('sales.invoice', $sale) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Invoice</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No sales found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $sales->links() }}
    </div>
</div>
@endsection
