{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div
    x-data="{ loaded: false }"
    x-init="setTimeout(() => loaded = true, 100)"
    class="space-y-6"
>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $kpiCards = [
                ['label' => 'Open Work Orders',   'value' => $kpis['open_orders'],      'color' => 'blue',   'trend' => '+6 this week',   'icon' => '⚙',  'up' => true],
                ['label' => 'Overdue',            'value' => $kpis['overdue'],           'color' => 'red',    'trend' => 'need attention', 'icon' => '⚠',  'up' => false],
                ['label' => 'Completed (MTD)',    'value' => $kpis['completed_mtd'],     'color' => 'green',  'trend' => ($kpis['completed_change'] >= 0 ? '+' : '') . $kpis['completed_change'] . '% vs last month', 'icon' => '✓', 'up' => $kpis['completed_change'] >= 0],
                ['label' => 'Avg Resolve Time',  'value' => $kpis['avg_resolve_hours'].'h', 'color' => 'amber', 'trend' => 'this month', 'icon' => '◷', 'up' => true],
            ];
            $colorMap = [
                'blue'  => ['value' => 'text-blue-400',  'bar' => 'bg-blue-500',  'icon' => 'bg-blue-500/15 text-blue-400'],
                'red'   => ['value' => 'text-red-400',   'bar' => 'bg-red-500',   'icon' => 'bg-red-500/15 text-red-400'],
                'green' => ['value' => 'text-green-400', 'bar' => 'bg-green-500', 'icon' => 'bg-green-500/15 text-green-400'],
                'amber' => ['value' => 'text-amber-400', 'bar' => 'bg-amber-500', 'icon' => 'bg-amber-500/15 text-amber-400'],
            ];
        @endphp

        @foreach ($kpiCards as $i => $card)
        <div
            class="kpi-card p-5"
            x-show="loaded"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            :style="'transition-delay: {{ $i * 80 }}ms'"
        >
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $card['label'] }}</p>
                <span class="w-7 h-7 rounded-lg {{ $colorMap[$card['color']]['icon'] }} flex items-center justify-center text-sm">
                    {{ $card['icon'] }}
                </span>
            </div>
            <p class="text-3xl font-semibold tracking-tight {{ $colorMap[$card['color']]['value'] }} mb-1">
                {{ $card['value'] }}
            </p>
            <p class="text-xs {{ $card['up'] ? 'text-green-400' : 'text-red-400' }} flex items-center gap-1">
                {{ $card['up'] ? '↑' : '↑' }} {{ $card['trend'] }}
            </p>
            <div class="absolute bottom-0 left-0 right-0 h-0.5 {{ $colorMap[$card['color']]['bar'] }} rounded-b-xl opacity-60"></div>
        </div>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- 30-day Trend --}}
        <div class="card p-5 lg:col-span-2"
             x-show="loaded"
             x-transition:enter="transition ease-out duration-400 delay-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-100">30-Day Completion Trend</h3>
                <span class="badge-blue">Monthly view</span>
            </div>
            <canvas id="trendChart" height="100"></canvas>
        </div>

        {{-- Status Donut --}}
        <div class="card p-5"
             x-show="loaded"
             x-transition:enter="transition ease-out duration-400 delay-400"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-100">Status Breakdown</h3>
                <span class="badge-gray">This month</span>
            </div>
            <canvas id="statusChart" height="160"></canvas>
        </div>
    </div>

    {{-- Alerts + Recent Orders --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Active Alerts --}}
        <div class="card p-5"
             x-show="loaded"
             x-transition:enter="transition ease-out duration-400 delay-500"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-100">Active Alerts</h3>
                @if($alerts->count())
                    <span class="badge-red">{{ $alerts->count() }} unread</span>
                @endif
            </div>
            <div class="space-y-0 divide-y divide-white/[0.05]">
                @forelse ($alerts->take(5) as $alert)
                <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm flex-shrink-0
                        {{ $alert->severity === 'critical' ? 'bg-red-500/15 text-red-400' :
                           ($alert->severity === 'warning'  ? 'bg-amber-500/15 text-amber-400' : 'bg-blue-500/15 text-blue-400') }}">
                        {{ $alert->severity === 'critical' ? '🔴' : ($alert->severity === 'warning' ? '⚠' : 'ℹ') }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-100 truncate">{{ $alert->title }}</p>
                        <p class="text-xs text-gray-500">{{ $alert->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-600 py-4 text-center">No active alerts</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Work Orders --}}
        <div class="card p-5 lg:col-span-2"
             x-show="loaded"
             x-transition:enter="transition ease-out duration-400 delay-500"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-100">Recent Work Orders</h3>
                <a href="{{ route('work-orders.index') }}" class="text-xs text-blue-400 hover:text-blue-300">View all →</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th><th>Title</th><th>Priority</th><th>Status</th><th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders->take(6) as $wo)
                    <tr>
                        <td class="font-mono text-xs text-gray-500">{{ $wo->code }}</td>
                        <td>
                            <a href="{{ route('work-orders.show', $wo) }}" class="font-medium hover:text-blue-400 transition">
                                {{ Str::limit($wo->title, 40) }}
                            </a>
                        </td>
                        <td>
                            <span class="badge-{{ match($wo->priority) { 'critical','high' => 'red', 'medium' => 'yellow', default => 'green' } }}">
                                {{ ucfirst($wo->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="status-{{ $wo->status }} text-xs px-2 py-1 rounded-full font-medium">
                                {{ ucwords(str_replace('_',' ',$wo->status)) }}
                            </span>
                        </td>
                        <td class="text-xs {{ $wo->is_overdue ? 'text-red-400 font-medium' : 'text-gray-500' }}">
                            {{ $wo->due_at?->format('M d') ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDashboardCharts(
            @json($charts['statusCounts']),
            @json($charts['trend'])
        );
    });
</script>
@endpush
@endsection
