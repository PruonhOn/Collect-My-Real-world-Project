{{-- resources/views/reports/index.blade.php --}}
@extends('layouts.app')
@section('title','Reports')
@section('page-title','Reports')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="text-sm text-gray-500 mt-1">Generate and export system reports</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    @php
    $reportTypes = [
        ['title'=>'Work Order Report','desc'=>'Summary of all work orders by status, priority and technician.','icon'=>'⚙','route'=>'reports.work-orders','color'=>'blue'],
        ['title'=>'Asset Health Report','desc'=>'Overview of asset maintenance frequency and upcoming PMs.','icon'=>'◈','route'=>'reports.assets','color'=>'teal'],
        ['title'=>'Technician Performance','desc'=>'Resolution times, completion rates and workload per technician.','icon'=>'◎','route'=>'reports.work-orders','color'=>'purple'],
    ];
    @endphp

    @foreach ($reportTypes as $r)
    <div class="card p-5 hover:border-white/[0.14] transition cursor-pointer group">
        <div class="w-10 h-10 rounded-xl bg-{{ $r['color'] }}-500/15 text-{{ $r['color'] }}-400 flex items-center justify-center text-lg mb-4">
            {{ $r['icon'] }}
        </div>
        <h3 class="text-sm font-semibold mb-1">{{ $r['title'] }}</h3>
        <p class="text-xs text-gray-500 mb-4 leading-relaxed">{{ $r['desc'] }}</p>
        <a href="{{ route($r['route']) }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">
            Generate report →
        </a>
    </div>
    @endforeach
</div>

{{-- Quick Export --}}
<div class="card p-6 mt-6">
    <h3 class="text-sm font-semibold mb-4">Quick Export</h3>
    <form action="{{ route('reports.export') }}" method="POST" class="flex flex-wrap gap-4 items-end">
        @csrf
        <input type="hidden" name="report" value="work_orders">
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">Date From</label>
            <input type="date" name="date_from" value="{{ now()->startOfMonth()->toDateString() }}" class="input">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">Date To</label>
            <input type="date" name="date_to" value="{{ now()->toDateString() }}" class="input">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1.5">Format</label>
            <select name="type" class="input">
                <option value="excel">Excel (.xlsx)</option>
                <option value="pdf">PDF</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">Export</button>
    </form>
</div>
@endsection
