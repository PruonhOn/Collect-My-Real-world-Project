@extends('layouts.admin')
@section('title', 'Purchase Details')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Purchase Details</h1>
        <a href="{{ route('purchases.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">Back to Purchases</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Reference No</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $purchase->reference_no }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Supplier</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $purchase->supplier ? $purchase->supplier->name : '-' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Date</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $purchase->created_at->format('d M Y h:i A') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
            <p class="font-semibold">
                @if ($purchase->status == 'pending')
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                @elseif ($purchase->status == 'ordered')
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">Ordered</span>
                @elseif ($purchase->status == 'received')
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Received</span>
                @elseif ($purchase->status == 'cancelled')
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Cancelled</span>
                @endif
            </p>
        </div>
    </div>
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Items</h2>
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Unit Cost</th>
                    <th class="px-4 py-3">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $index => $item)
                <tr class="border-b dark:border-gray-700">
                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                    <td class="px-4 py-3">{{ $item->product ? $item->product->name : $item->product_name }}</td>
                    <td class="px-4 py-3">{{ $item->quantity }}</td>
                    <td class="px-4 py-3">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="px-4 py-3">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex justify-end">
        <div class="w-64">
            <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white border-t dark:border-gray-700 pt-2">
                <span>Total</span>
                <span>{{ number_format($purchase->total, 2) }}</span>
            </div>
        </div>
    </div>
    @if ($purchase->notes)
    <div class="mt-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
        <p class="text-gray-700 dark:text-gray-300">{{ $purchase->notes }}</p>
    </div>
    @endif
</div>
@endsection
