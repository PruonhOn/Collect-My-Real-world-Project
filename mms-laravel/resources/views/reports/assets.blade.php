{{-- resources/views/reports/assets.blade.php --}}
@extends('layouts.app')
@section('title', 'Asset Health Report')
@section('page-title', 'Asset Health Report')

@section('content')
<div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
    <a href="{{ route('reports.index') }}" class="hover:text-gray-300">Reports</a>
    <span>/</span>
    <span class="text-gray-300">Asset Health</span>
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-4 border-b border-white/[0.06] flex items-center justify-between">
        <h3 class="text-sm font-semibold">Asset Maintenance Overview</h3>
        <span class="badge-gray text-xs">{{ now()->format('M Y') }}</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Asset</th>
                <th>Category</th>
                <th>Location</th>
                <th>Total WOs</th>
                <th>Open</th>
                <th>Overdue</th>
                <th>Completed</th>
                <th>Health</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assets ?? \App\Services\ReportService::new()->assetHealthReport() as $asset)
            <tr>
                <td>
                    <a href="{{ route('assets.show', $asset) }}" class="font-medium hover:text-blue-400 transition">
                        {{ $asset->name }}
                    </a>
                    <p class="text-xs text-gray-600 font-mono">{{ $asset->code }}</p>
                </td>
                <td><span class="badge-gray">{{ $asset->category }}</span></td>
                <td class="text-gray-400 text-xs">{{ $asset->location }}</td>
                <td class="text-center font-medium text-gray-300">{{ $asset->work_orders_count }}</td>
                <td class="text-center">
                    @if($asset->open_count > 0)
                    <span class="badge-blue">{{ $asset->open_count }}</span>
                    @else
                    <span class="text-gray-600">—</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($asset->overdue_count > 0)
                    <span class="badge-red">{{ $asset->overdue_count }}</span>
                    @else
                    <span class="text-gray-600">—</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge-green">{{ $asset->completed_count }}</span>
                </td>
                <td>
                    @php
                    $health = $asset->overdue_count > 0 ? 'critical' : ($asset->open_count > 3 ? 'warning' : 'good');
                    @endphp
                    <span class="badge-{{ match($health) { 'critical' => 'red', 'warning' => 'yellow', default => 'green' } }}">
                        {{ ucfirst($health) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-10 text-gray-600">No assets found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
