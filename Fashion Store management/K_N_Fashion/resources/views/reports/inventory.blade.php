@extends('layouts.admin')
@section('title', 'Inventory Report')
@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Inventory Report</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
            <p class="text-sm text-blue-600 dark:text-blue-300">Total Products</p>
            <p class="text-2xl font-bold text-blue-800 dark:text-blue-100">{{ $totalProducts ?? 0 }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
            <p class="text-sm text-green-600 dark:text-green-300">Total Stock Value</p>
            <p class="text-2xl font-bold text-green-800 dark:text-green-100">{{ number_format($totalStockValue ?? 0, 2) }}</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
            <p class="text-sm text-yellow-600 dark:text-yellow-300">Low Stock Count</p>
            <p class="text-2xl font-bold text-yellow-800 dark:text-yellow-100">{{ $lowStockCount ?? 0 }}</p>
        </div>
    </div>
    @if (isset($products) && count($products) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Product Name</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Total Stock</th>
                        <th class="px-4 py-3">Stock Value</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                    @php
                        $totalStock = $product->variants->sum('quantity');
                    @endphp
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3">{{ $product->name }}</td>
                        <td class="px-4 py-3">{{ $product->sku }}</td>
                        <td class="px-4 py-3">{{ $product->category ? $product->category->name : '-' }}</td>
                        <td class="px-4 py-3">{{ $totalStock }}</td>
                        <td class="px-4 py-3">{{ number_format($totalStock * ($product->cost_price ?? 0), 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($totalStock > 10)
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">In Stock</span>
                            @elseif ($totalStock > 0)
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Low Stock</span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Out of Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No products found in inventory.</p>
    @endif
</div>
@endsection
