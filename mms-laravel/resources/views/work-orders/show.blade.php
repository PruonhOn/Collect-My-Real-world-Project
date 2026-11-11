{{-- resources/views/work-orders/show.blade.php --}}
@extends('layouts.app')
@section('title', $workOrder->code)
@section('page-title', $workOrder->code)

@section('content')
<div
    x-data="{ completeModal: false }"
    class="space-y-5"
>
    {{-- Breadcrumb + actions --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('work-orders.index') }}" class="hover:text-gray-300 transition">Work Orders</a>
            <span>/</span>
            <span class="text-gray-300">{{ $workOrder->code }}</span>
        </div>
        <div class="flex gap-2">
            @can('update', $workOrder)
                @if ($workOrder->status === 'open' || $workOrder->status === 'assigned')
                <form action="{{ route('work-orders.start', $workOrder) }}" method="POST">
                    @csrf @method('PATCH')
                    <button class="btn-secondary text-sm">▶ Start</button>
                </form>
                @endif
                @if ($workOrder->status === 'in_progress')
                <button @click="completeModal = true" class="btn-primary text-sm">✓ Complete</button>
                @endif
                <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn-secondary text-sm">Edit</a>
            @endcan
            @can('delete', $workOrder)
            <form action="{{ route('work-orders.destroy', $workOrder) }}" method="POST"
                  onsubmit="return confirm('Delete this work order?')">
                @csrf @method('DELETE')
                <button class="btn-danger text-sm">Delete</button>
            </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Main info --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="card p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-100">{{ $workOrder->title }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $workOrder->code }} · {{ ucfirst($workOrder->type) }}</p>
                    </div>
                    <span class="status-{{ $workOrder->status }} text-xs px-3 py-1.5 rounded-full font-medium">
                        {{ ucwords(str_replace('_', ' ', $workOrder->status)) }}
                    </span>
                </div>

                @if ($workOrder->description)
                <p class="text-sm text-gray-400 leading-relaxed mb-4 pb-4 border-b border-white/[0.06]">
                    {{ $workOrder->description }}
                </p>
                @endif

                {{-- Progress --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-500 font-medium">Progress</span>
                        <span class="text-xs font-semibold text-gray-300">{{ $workOrder->progress }}%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full progress-fill
                            {{ $workOrder->status === 'completed' ? 'bg-green-500' :
                               ($workOrder->is_overdue ? 'bg-red-500' : 'bg-blue-500') }}"
                             style="width: {{ $workOrder->progress }}%">
                        </div>
                    </div>
                </div>

                {{-- Meta grid --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Asset</p>
                        <a href="{{ route('assets.show', $workOrder->asset) }}" class="text-blue-400 hover:underline">
                            {{ $workOrder->asset->name }}
                        </a>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Location</p>
                        <p class="text-gray-300">{{ $workOrder->asset->location }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Priority</p>
                        <span class="badge-{{ match($workOrder->priority) {'critical','high'=>'red','medium'=>'yellow',default=>'green'} }}">
                            {{ ucfirst($workOrder->priority) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Due Date</p>
                        <p class="{{ $workOrder->is_overdue ? 'text-red-400 font-medium' : 'text-gray-300' }}">
                            {{ $workOrder->due_at?->format('M d, Y H:i') ?? 'No deadline' }}
                            @if($workOrder->is_overdue) <span class="text-xs ml-1">(OVERDUE)</span> @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Created By</p>
                        <p class="text-gray-300">{{ $workOrder->creator->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Created At</p>
                        <p class="text-gray-300">{{ $workOrder->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    @if ($workOrder->started_at)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Started</p>
                        <p class="text-gray-300">{{ $workOrder->started_at->format('M d, Y H:i') }}</p>
                    </div>
                    @endif
                    @if ($workOrder->completed_at)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Completed</p>
                        <p class="text-green-400">{{ $workOrder->completed_at->format('M d, Y H:i') }}</p>
                    </div>
                    @endif
                </div>

                @if ($workOrder->resolution_notes)
                <div class="mt-4 p-3 bg-green-500/[0.06] border border-green-500/20 rounded-lg">
                    <p class="text-xs text-green-400 font-medium mb-1">Resolution Notes</p>
                    <p class="text-sm text-gray-300">{{ $workOrder->resolution_notes }}</p>
                </div>
                @endif
            </div>

            {{-- Activity Log --}}
            <div class="card p-5">
                <h3 class="text-sm font-semibold mb-4">Activity Log</h3>
                <div class="space-y-0">
                    @forelse ($workOrder->logs as $log)
                    <div class="flex gap-3 pb-4 relative">
                        @if (!$loop->last)
                        <div class="absolute left-3.5 top-8 bottom-0 w-px bg-white/[0.06]"></div>
                        @endif
                        <div class="w-7 h-7 rounded-full bg-gray-800 border border-white/10 flex items-center justify-center text-xs flex-shrink-0 z-10">
                            {{ strtoupper(substr($log->user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 pt-0.5">
                            <p class="text-sm text-gray-300">
                                <span class="font-medium">{{ $log->user->name }}</span>
                                <span class="text-gray-500"> {{ $log->action_label }}</span>
                            </p>
                            @if ($log->note)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $log->note }}</p>
                            @endif
                            <p class="text-xs text-gray-600 mt-1">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-600">No activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar: Technician + Assign --}}
        <div class="space-y-5">
            <div class="card p-5">
                <h3 class="text-sm font-semibold mb-4">Assigned Technician</h3>
                @if ($workOrder->technician)
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-purple-600/20 text-purple-300 flex items-center justify-center font-semibold">
                        {{ $workOrder->technician->initials }}
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ $workOrder->technician->name }}</p>
                        <p class="text-xs text-gray-500">{{ $workOrder->technician->email }}</p>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-600 mb-4">Not assigned</p>
                @endif

                @can('assign', $workOrder)
                <form action="{{ route('work-orders.assign', $workOrder) }}" method="POST">
                    @csrf @method('PATCH')
                    <select name="user_id" class="input w-full mb-2 text-sm">
                        <option value="">Select technician…</option>
                        @foreach (\App\Models\User::role('technician')->get() as $tech)
                        <option value="{{ $tech->id }}" @selected($workOrder->assigned_to == $tech->id)>
                            {{ $tech->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary w-full text-sm">
                        {{ $workOrder->assigned_to ? 'Reassign' : 'Assign' }}
                    </button>
                </form>
                @endcan
            </div>

            {{-- Alerts for this order --}}
            @if ($workOrder->alerts->count())
            <div class="card p-5">
                <h3 class="text-sm font-semibold mb-3">Alerts</h3>
                @foreach ($workOrder->alerts as $alert)
                <div class="flex items-center gap-2 py-2 border-t border-white/[0.05] first:border-0 first:pt-0">
                    <span class="w-2 h-2 rounded-full flex-shrink-0
                        {{ $alert->severity === 'critical' ? 'bg-red-500' : ($alert->severity === 'warning' ? 'bg-amber-500' : 'bg-blue-500') }}">
                    </span>
                    <p class="text-xs text-gray-300">{{ $alert->title }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Complete Modal --}}
    <div x-show="completeModal" x-cloak class="modal-backdrop" @click.self="completeModal = false">
        <div class="modal-panel p-6" @click.stop>
            <h3 class="text-base font-semibold mb-4">Complete Work Order</h3>
            <form action="{{ route('work-orders.complete', $workOrder) }}" method="POST">
                @csrf @method('PATCH')
                <div class="mb-4">
                    <label class="block text-xs text-gray-400 mb-2">Resolution Notes (optional)</label>
                    <textarea name="notes" rows="4" class="input w-full resize-none"
                              placeholder="Describe what was done to resolve the issue…"></textarea>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" @click="completeModal = false" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Mark Complete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
