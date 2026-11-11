{{-- resources/views/reports/work-orders.blade.php --}}
@extends('layouts.app')
@section('title','Work Order Report')
@section('page-title','Work Order Report')

@section('content')
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('reports.index') }}" class="hover:text-gray-300">Reports</a>
        <span>/</span><span class="text-gray-300">Work Orders</span>
    </div>
    <form action="{{ route('reports.export') }}" method="POST" class="flex gap-2">
        @csrf
        <input type="hidden" name="report" value="work_orders">
        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
        <input type="hidden" name="date_to" value="{{ $dateTo }}">
        <button name="type" value="excel" class="btn-secondary text-sm">Export Excel</button>
        <button name="type" value="pdf"   class="btn-secondary text-sm">Export PDF</button>
    </form>
</div>

{{-- Date filter --}}
<div class="card p-4 mb-5">
    <form method="GET" class="flex gap-4 items-end flex-wrap">
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="input text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="input text-sm">
        </div>
        <button type="submit" class="btn-primary text-sm">Apply Filter</button>
    </form>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    @foreach ([
        ['label'=>'Total Orders','value'=>$summary['total'],'color'=>'blue'],
        ['label'=>'Completed','value'=>$summary['completed'],'color'=>'green'],
        ['label'=>'Overdue','value'=>$summary['overdue'],'color'=>'red'],
        ['label'=>'Avg Resolve','value'=>$summary['avg_resolve'].'h','color'=>'amber'],
    ] as $s)
    <div class="card p-4 text-center">
        <p class="text-2xl font-semibold text-{{ $s['color'] }}-400">{{ $s['value'] }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $s['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr><th>Code</th><th>Title</th><th>Asset</th><th>Type</th><th>Priority</th><th>Status</th><th>Technician</th><th>Due</th><th>Completed</th></tr>
        </thead>
        <tbody>
            @forelse ($orders as $wo)
            <tr>
                <td class="font-mono text-xs text-gray-500">{{ $wo->code }}</td>
                <td><a href="{{ route('work-orders.show',$wo) }}" class="hover:text-blue-400">{{ Str::limit($wo->title,35) }}</a></td>
                <td class="text-gray-400 text-xs">{{ $wo->asset->name }}</td>
                <td><span class="badge-gray text-xs">{{ ucfirst($wo->type) }}</span></td>
                <td><span class="badge-{{ match($wo->priority){'critical','high'=>'red','medium'=>'yellow',default=>'green'} }}">{{ ucfirst($wo->priority) }}</span></td>
                <td><span class="status-{{ $wo->status }} text-xs px-2 py-1 rounded-full">{{ ucwords(str_replace('_',' ',$wo->status)) }}</span></td>
                <td class="text-gray-400 text-xs">{{ $wo->technician?->name ?? '—' }}</td>
                <td class="text-gray-500 text-xs">{{ $wo->due_at?->format('M d') ?? '—' }}</td>
                <td class="text-gray-500 text-xs">{{ $wo->completed_at?->format('M d') ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center py-10 text-gray-600">No records for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
