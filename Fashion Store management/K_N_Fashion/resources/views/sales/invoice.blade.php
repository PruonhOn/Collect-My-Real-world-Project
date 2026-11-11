@extends('layouts.admin')
@section('title', 'Invoice')
@section('content')
<div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow p-8 print:shadow-none print:rounded-none" id="invoice">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $settings->store_name ?? 'K & N Fashion' }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $settings->store_address ?? '' }}</p>
        <p class="text-gray-600 dark:text-gray-400">Phone: {{ $settings->store_phone ?? '' }} | Email: {{ $settings->store_email ?? '' }}</p>
    </div>
    <hr class="mb-6 dark:border-gray-700">
    <div class="flex justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Invoice</h2>
            <p class="text-gray-600 dark:text-gray-400">Invoice No: <span class="font-semibold">{{ $sale->invoice_no }}</span></p>
            <p class="text-gray-600 dark:text-gray-400">Date: <span class="font-semibold">{{ $sale->created_at->format('d M Y h:i A') }}</span></p>
        </div>
        <div class="text-right">
            <h3 class="font-semibold text-gray-900 dark:text-white">Bill To:</h3>
            <p class="text-gray-600 dark:text-gray-400">{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
            @if ($sale->customer && $sale->customer->phone)
                <p class="text-gray-600 dark:text-gray-400">{{ $sale->customer->phone }}</p>
            @endif
            @if ($sale->customer && $sale->customer->address)
                <p class="text-gray-600 dark:text-gray-400">{{ $sale->customer->address }}</p>
            @endif
        </div>
    </div>
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300 mb-6">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3 text-center">Quantity</th>
                <th class="px-4 py-3 text-right">Price</th>
                <th class="px-4 py-3 text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $index => $item)
            <tr class="border-b dark:border-gray-700">
                <td class="px-4 py-3">{{ $index + 1 }}</td>
                <td class="px-4 py-3">{{ $item->product ? $item->product->name : $item->product_name }}</td>
                <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($item->price, 2) }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="flex justify-end mb-8">
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
    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 border-t dark:border-gray-700 pt-4">
        <p>Payment Method: {{ ucfirst($sale->payment_method) }}</p>
        <p>Payment Status: {{ ucfirst($sale->payment_status) }}</p>
    </div>
    <div class="text-center mt-8 text-sm text-gray-500 dark:text-gray-400">
        <p>Thank you for your purchase!</p>
    </div>
</div>
<div class="max-w-4xl mx-auto mt-4 text-center print:hidden">
    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">Print Invoice</button>
    <a href="{{ route('sales.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg ml-2">Back to Sales</a>
</div>
@endsection
@push('scripts')
<script>
    window.onload = function() {
        window.print();
    };
</script>
@endpush
