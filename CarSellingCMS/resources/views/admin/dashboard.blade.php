@extends('layouts.admin')
@section('title', 'Dashboard - K & N AutoCar')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6" x-data="dashboard()" x-init="init()">
    {{-- KPI CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <div class="group relative bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 dark:bg-blue-400/5 rounded-bl-full -z-0"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M16 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M3 11l2-5h14l2 5M3 11h18v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">{{ $stats['total_cars'] }} total</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['available_cars'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Available Cars</p>
                <div class="flex items-center gap-3 mt-3 text-xs">
                    <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ $stats['available_cars'] }} Available</span>
                    <span class="flex items-center gap-1 text-red-600 dark:text-red-400"><span class="w-2 h-2 rounded-full bg-red-500"></span>{{ $stats['sold_cars'] }} Sold</span>
                </div>
            </div>
        </div>

        <div class="group relative bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 dark:bg-emerald-400/5 rounded-bl-full -z-0"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center gap-1">
                        @if($stats['revenue_change']['direction'] === 'up')
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        @else
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        @endif
                        {{ $stats['revenue_change']['value'] }}%
                    </span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_revenue']) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Total Revenue</p>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $stats['completed_transactions'] }} completed transactions</div>
            </div>
        </div>

        <div class="group relative bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 dark:bg-amber-400/5 rounded-bl-full -z-0"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-full">{{ $stats['unread_contacts'] }} unread</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_contacts'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Messages</p>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $stats['total_contacts'] > 0 ? round(($stats['unread_contacts'] / $stats['total_contacts']) * 100) : 0 }}% response rate needed</div>
            </div>
        </div>

        <div class="group relative bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/5 dark:bg-purple-400/5 rounded-bl-full -z-0"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-0.5 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-full">{{ $stats['pending_transactions'] }} pending</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_transactions'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Transactions</p>
                <div class="flex items-center gap-3 mt-3 text-xs">
                    <span class="text-emerald-600">{{ $stats['completed_transactions'] }} completed</span>
                    @if($stats['failed_transactions'] > 0)
                        <span class="text-red-600">{{ $stats['failed_transactions'] }} failed</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Revenue Overview</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Monthly revenue for the last 6 months</p>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">$ USD</span>
            </div>
            <div class="relative h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Inventory Status</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">By condition</p>
                </div>
            </div>
            <div class="relative h-64 flex items-center justify-center">
                <canvas id="inventoryChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs" id="inventoryLegend"></div>
        </div>
    </div>

    {{-- TABLES ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                <a href="{{ route('admin.transactions.index') }}" class="text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-700 transition-colors">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                            <th class="pb-3 font-medium">ID</th>
                            <th class="pb-3 font-medium">Customer</th>
                            <th class="pb-3 font-medium">Amount</th>
                            <th class="pb-3 font-medium">Status</th>
                            <th class="pb-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['recent_transactions'] as $t)
                            <tr class="border-b border-gray-50 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="py-3 font-mono text-xs text-gray-600 dark:text-gray-400">#{{ substr($t->transaction_id, 0, 8) }}</td>
                                <td class="py-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $t->customer_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $t->car->name ?? 'N/A' }}</p>
                                    </div>
                                </td>
                                <td class="py-3 font-semibold text-gray-900 dark:text-white">${{ number_format($t->amount) }}</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium
                                        {{ $t->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : '' }}
                                        {{ $t->status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300' : '' }}
                                        {{ $t->status === 'failed' ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : '' }}
                                        {{ $t->status === 'processing' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <span class="w-1.5 h-1.5 rounded-full
                                            {{ $t->status === 'completed' ? 'bg-emerald-500' : '' }}
                                            {{ $t->status === 'pending' ? 'bg-amber-500' : '' }}
                                            {{ $t->status === 'failed' ? 'bg-red-500' : '' }}
                                            {{ $t->status === 'processing' ? 'bg-blue-500' : '' }}"></span>
                                        {{ ucfirst($t->status) }}
                                    </span>
                                </td>
                                <td class="py-3 text-xs text-gray-500 dark:text-gray-400">{{ $t->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No transactions yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Recent Messages</h3>
                <a href="{{ route('admin.contacts.index') }}" class="text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-700 transition-colors">View All</a>
            </div>
            <div class="space-y-1">
                @forelse($stats['recent_contacts'] as $c)
                    <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors {{ !$c->is_read ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                        <div class="w-8 h-8 mt-0.5 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 flex-shrink-0">
                            {{ strtoupper(substr($c->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate {{ !$c->is_read ? 'font-semibold' : '' }}">{{ $c->name }}</p>
                                <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $c->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $c->subject ?? 'No subject' }}</p>
                        </div>
                        @if(!$c->is_read)
                            <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></span>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No messages yet</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- RECENT CARS --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white">Recently Added Cars</h3>
            <a href="{{ route('admin.cars.index') }}" class="text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-700 transition-colors">View All</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @forelse($stats['recent_cars'] as $car)
                <div class="group relative bg-gray-50 dark:bg-gray-800/50 rounded-xl overflow-hidden hover:shadow-md transition-all duration-200">
                    <div class="aspect-[4/3] bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                        @if($car->image)
                            <img src="{{ asset('storage/' . $car->image) }}" alt="{{ $car->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M16 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M3 11l2-5h14l2 5M3 11h18v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z"/></svg>
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $car->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $car->year }} &middot; {{ $car->category->name ?? 'N/A' }}</p>
                        <p class="text-sm font-bold text-red-600 dark:text-red-400 mt-1">${{ number_format($car->sale_price ?? $car->price) }}</p>
                        <span class="inline-block mt-1.5 text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $car->status === 'available' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : '' }}
                            {{ $car->status === 'sold' ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' : '' }}
                            {{ $car->status === 'reserved' ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400' : '' }}">
                            {{ ucfirst($car->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="lg:col-span-5 text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M16 17v2a1 1 0 001 1h1a1 1 0 001-1v-2M3 11l2-5h14l2 5M3 11h18v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5z"/></svg>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">No cars added yet</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    function dashboard() {
        return {
            init() {
                this.initRevenueChart();
                this.initInventoryChart();
            },
            initRevenueChart() {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: @json($stats['monthly_labels']),
                        datasets: [{
                            label: 'Revenue',
                            data: @json($stats['monthly_revenue']),
                            backgroundColor: 'rgba(220, 38, 38, 0.15)',
                            borderColor: '#dc2626',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1f2937',
                                titleColor: '#f3f4f6',
                                bodyColor: '#e5e7eb',
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: ctx => '$' + ctx.raw.toLocaleString()
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(156, 163, 175, 0.1)' },
                                ticks: {
                                    callback: v => '$' + v.toLocaleString(),
                                    color: '#9ca3af'
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#9ca3af' }
                            }
                        }
                    }
                });
            },
            initInventoryChart() {
                const ctx = document.getElementById('inventoryChart').getContext('2d');
                const data = {
                    labels: ['Available', 'Sold', 'Reserved'],
                    values: [{{ $stats['available_cars'] }}, {{ $stats['sold_cars'] }}, {{ $stats['reserved_cars'] }}],
                    colors: ['#10b981', '#ef4444', '#f59e0b']
                };
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: data.colors,
                            borderWidth: 3,
                            borderColor: 'transparent',
                            hoverOffset: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1f2937',
                                titleColor: '#f3f4f6',
                                bodyColor: '#e5e7eb',
                                padding: 12,
                                cornerRadius: 8,
                            }
                        }
                    },
                    plugins: [{
                        afterDraw: chart => {
                            const { ctx: c, chartArea: { top, left, width, height } } = chart;
                            const meta = chart.getDatasetMeta(0);
                            if (meta.total > 0) {
                                c.save();
                                c.font = 'bold 20px Inter, sans-serif';
                                c.fillStyle = document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827';
                                c.textAlign = 'center';
                                c.textBaseline = 'middle';
                                c.fillText(meta.total, left + width / 2, top + height / 2 - 6);
                                c.font = '11px Inter, sans-serif';
                                c.fillStyle = '#9ca3af';
                                c.fillText('Total', left + width / 2, top + height / 2 + 16);
                                c.restore();
                            }
                        }
                    }]
                });
                // Render legend
                const legend = document.getElementById('inventoryLegend');
                legend.innerHTML = data.labels.map((label, i) =>
                    `<div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800/50">
                        <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="w-2.5 h-2.5 rounded-full" style="background:${data.colors[i]}"></span>
                            ${label}
                        </span>
                        <span class="font-semibold text-gray-900 dark:text-white">${data.values[i]}</span>
                    </div>`
                ).join('');
            }
        }
    }
</script>
@endpush
@endsection
