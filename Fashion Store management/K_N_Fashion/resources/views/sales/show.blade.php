@extends('layouts.admin')
@section('title', 'Sale Details')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sale Details</h1>
        <a href="{{ route('sales.invoice', $sale) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Print Invoice</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Invoice No</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $sale->invoice_no }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Date</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $sale->created_at->format('d M Y h:i A') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Customer</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
            @if ($sale->customer && $sale->customer->phone)
                <p class="text-sm text-gray-500">{{ $sale->customer->phone }}</p>
            @endif
        </div>
    </div>
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Items</h2>
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                <tr class="border-b dark:border-gray-700">
                    <td class="px-4 py-3">{{ $item->product ? $item->product->name : $item->product_name }}</td>
                    <td class="px-4 py-3">{{ $item->quantity }}</td>
                    <td class="px-4 py-3">{{ number_format($item->price, 2) }}</td>
                    <td class="px-4 py-3">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex justify-end">
        <div class="w-64 space-y-2">
            <div class="flex justify-between text-gray-700 dark:text-gray-300">
                <span>Subtotal</span>
                <span>{{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if ($sale->discount > 0)
            <div class="flex justify-between text-gray-700 dark:text-gray-300">
                <span>Discount</span>
                <span>-{{ number_format($sale->discount, 2) }}</span>
            </div>
            @endif
            @if ($sale->tax > 0)
            <div class="flex justify-between text-gray-700 dark:text-gray-300">
                <span>Tax</span>
                <span>{{ number_format($sale->tax, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white border-t dark:border-gray-700 pt-2">
                <span>Grand Total</span>
                <span>{{ number_format($sale->grand_total, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Payment Method</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($sale->payment_method) }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Payment Status</p>
            <p class="font-semibold">
                @if ($sale->payment_status == 'paid')
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Paid</span>
                @elseif ($sale->payment_status == 'pending')
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Pending</span>
                @else
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">{{ ucfirst($sale->payment_status) }}</span>
                @endif
            </p>
        </div>
    </div>
</div>
@endsection
