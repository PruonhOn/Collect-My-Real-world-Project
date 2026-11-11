@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Welcome back, {{ Auth::user()->name }}!</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Products</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalProducts ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sales</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalSales ?? 0 }}</p>
                        <p class="text-xs text-gray-400">Total Sales Count</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">${{ number_format($totalRevenue ?? 0, 2) }}</p>
                        <p class="text-xs text-gray-400">Total Revenue</p>


                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalCustomers ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Sales Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Sales Chart - Last 7 Days</h2>
                <div class="relative" style="height: 250px;">
                    <canvas id="salesChart" class="w-full h-full"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p>Chart will render with Chart.js</p>
                            @if(isset($chartData))
                                <p class="text-xs mt-1">Data available for rendering</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Sales --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Recent Sales</h2>
                    <a href="{{ route('sales.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-3 font-medium">Invoice</th>
                                <th class="pb-3 font-medium">Customer</th>
                                <th class="pb-3 font-medium">Amount</th>
                                <th class="pb-3 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales ?? [] as $sale)
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="py-3 text-gray-800 dark:text-gray-200">{{ $sale->invoice_no }}</td>
                                    <td class="py-3 text-gray-600 dark:text-gray-400">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                    <td class="py-3 text-gray-800 dark:text-gray-200">${{ number_format($sale->grand_total, 2) }}</td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ $sale->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400 dark:text-gray-500">No sales yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Low Stock Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Low Stock Products</h2>
                <a href="{{ route('products.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-3 font-medium">Product</th>
                            <th class="pb-3 font-medium">Category</th>
                            <th class="pb-3 font-medium">Stock</th>
                            <th class="pb-3 font-medium">Price</th>
                            <th class="pb-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockProducts ?? [] as $product)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-3">
                                    <div class="flex items-center space-x-3">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-8 h-8 rounded object-cover">
                                        @else
                                            <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ $product->category->name ?? 'N/A' }}</td>
                                <td class="py-3">
                                    <span class="font-medium {{ $product->stock <= 5 ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400' }}">{{ $product->stock }}</span>
                                </td>
                                <td class="py-3 text-gray-800 dark:text-gray-200">${{ number_format($product->price, 2) }}</td>
                                <td class="py-3">
                                    @if($product->stock <= 5)
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">Critical</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">Low</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400 dark:text-gray-500">All products are well stocked</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('salesChart');
        if (canvas && typeof Chart !== 'undefined') {
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!},
                    datasets: [{
                        label: 'Sales',
                        data: {!! json_encode($chartData ?? [0, 0, 0, 0, 0, 0, 0]) !!},
                        backgroundColor: 'rgba(99, 102, 241, 0.5)',
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 2,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
