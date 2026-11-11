{{-- resources/views/assets/index.blade.php --}}
@extends('layouts.app')
@section('title','Assets')
@section('page-title','Assets')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Assets</h1>
        <p class="text-sm text-gray-500 mt-1">Equipment registry and maintenance history</p>
    </div>
    @can('create assets')
    <a href="{{ route('assets.create') }}" class="btn-primary">+ Add Asset</a>
    @endcan
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th><th>Name</th><th>Category</th><th>Location</th>
                <th>Status</th><th>Work Orders</th><th>Warranty</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assets as $asset)
            <tr class="animate-fade-in">
                <td class="font-mono text-xs text-gray-500">{{ $asset->code }}</td>
                <td>
                    <a href="{{ route('assets.show', $asset) }}" class="font-medium hover:text-blue-400 transition">
                        {{ $asset->name }}
                    </a>
                </td>
                <td><span class="badge-gray">{{ $asset->category }}</span></td>
                <td class="text-gray-400 text-xs">{{ $asset->location }}</td>
                <td>
                    <span class="badge-{{ $asset->status === 'active' ? 'green' : ($asset->status === 'inactive' ? 'yellow' : 'gray') }}">
                        {{ ucfirst($asset->status) }}
                    </span>
                </td>
                <td>
                    <span class="text-sm font-medium text-gray-300">{{ $asset->work_orders_count }}</span>
                    <span class="text-xs text-gray-600 ml-1">total</span>
                </td>
                <td class="text-xs {{ $asset->is_warranty_active ? 'text-green-400' : 'text-gray-600' }}">
                    {{ $asset->warranty_expires_at?->format('M Y') ?? '—' }}
                </td>
                <td>
                    <div class="flex gap-1">
                        <a href="{{ route('assets.show', $asset) }}" class="text-xs px-2 py-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition">View</a>
                        @can('edit assets')
                        <a href="{{ route('assets.edit', $asset) }}" class="text-xs px-2 py-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition">Edit</a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-10 text-gray-600">No assets found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($assets->hasPages())
    <div class="px-5 py-3 border-t border-white/[0.06]">{{ $assets->links() }}</div>
    @endif
</div>
@endsection
