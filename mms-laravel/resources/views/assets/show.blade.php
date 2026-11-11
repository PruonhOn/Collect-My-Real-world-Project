{{-- resources/views/assets/show.blade.php --}}
@extends('layouts.app')
@section('title', $asset->name)
@section('page-title', $asset->name)

@section('content')
<div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
    <a href="{{ route('assets.index') }}" class="hover:text-gray-300">Assets</a>
    <span>/</span>
    <span class="text-gray-300">{{ $asset->name }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="card p-6">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <h1 class="text-lg font-semibold">{{ $asset->name }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $asset->code }} · {{ $asset->category }}</p>
                </div>
                <span class="badge-{{ $asset->status === 'active' ? 'green' : 'gray' }}">{{ ucfirst($asset->status) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-xs text-gray-500 mb-1">Location</p><p class="text-gray-300">{{ $asset->location }}</p></div>
                <div><p class="text-xs text-gray-500 mb-1">Installed</p><p class="text-gray-300">{{ $asset->installed_at?->format('M d, Y') ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-500 mb-1">Warranty Expires</p>
                    <p class="{{ $asset->is_warranty_active ? 'text-green-400' : 'text-red-400' }}">
                        {{ $asset->warranty_expires_at?->format('M d, Y') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4">
            @foreach (['total_wo' => ['label'=>'Total Orders','color'=>'blue'], 'open_wo' => ['label'=>'Open','color'=>'amber'], 'completed_wo' => ['label'=>'Completed','color'=>'green']] as $key => $cfg)
            <div class="card p-4 text-center">
                <p class="text-2xl font-semibold text-{{ $cfg['color'] }}-400">{{ $stats[$key] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $cfg['label'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Work Orders --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold mb-4">Work Order History</h3>
            <table class="data-table">
                <thead><tr><th>Code</th><th>Title</th><th>Status</th><th>Technician</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($asset->workOrders->take(10) as $wo)
                    <tr>
                        <td class="font-mono text-xs text-gray-500">{{ $wo->code }}</td>
                        <td><a href="{{ route('work-orders.show',$wo) }}" class="hover:text-blue-400 transition">{{ Str::limit($wo->title,40) }}</a></td>
                        <td><span class="status-{{ $wo->status }} text-xs px-2 py-1 rounded-full">{{ ucwords(str_replace('_',' ',$wo->status)) }}</span></td>
                        <td class="text-gray-400 text-xs">{{ $wo->technician?->name ?? '—' }}</td>
                        <td class="text-gray-500 text-xs">{{ $wo->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-gray-600 py-6">No work orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card p-5">
            <h3 class="text-sm font-semibold mb-4">Actions</h3>
            @can('edit assets')
            <a href="{{ route('assets.edit',$asset) }}" class="btn-secondary w-full text-sm mb-2 text-center block">Edit Asset</a>
            @endcan
            @can('create work-orders')
            <a href="{{ route('work-orders.create').'?asset_id='.$asset->id }}" class="btn-primary w-full text-sm text-center block">+ New Work Order</a>
            @endcan
        </div>
    </div>
</div>
@endsection
