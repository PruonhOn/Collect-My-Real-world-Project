{{-- resources/views/work-orders/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit ' . $workOrder->code)
@section('page-title', 'Edit Work Order')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
        <a href="{{ route('work-orders.index') }}" class="hover:text-gray-300">Work Orders</a>
        <span>/</span>
        <a href="{{ route('work-orders.show', $workOrder) }}" class="hover:text-gray-300">{{ $workOrder->code }}</a>
        <span>/</span>
        <span class="text-gray-300">Edit</span>
    </div>

    <div class="card p-6">
        <h2 class="text-base font-semibold mb-6">Edit Work Order</h2>

        <form action="{{ route('work-orders.update', $workOrder) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Title *</label>
                <input type="text" name="title" value="{{ old('title', $workOrder->title) }}"
                       class="input w-full @error('title') border-red-500/50 @enderror" required>
                @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Type *</label>
                    <select name="type" class="input w-full" required>
                        @foreach (['corrective' => 'Corrective', 'preventive' => 'Preventive', 'inspection' => 'Inspection', 'emergency' => 'Emergency'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', $workOrder->type) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Priority *</label>
                    <select name="priority" class="input w-full" required>
                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('priority', $workOrder->priority) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Asset *</label>
                    <select name="asset_id" class="input w-full" required>
                        @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $workOrder->asset_id) == $asset->id)>
                            {{ $asset->name }} ({{ $asset->code }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Assign To</label>
                    <select name="assigned_to" class="input w-full">
                        <option value="">Unassigned</option>
                        @foreach ($technicians as $tech)
                        <option value="{{ $tech->id }}" @selected(old('assigned_to', $workOrder->assigned_to) == $tech->id)>
                            {{ $tech->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Due Date</label>
                <input type="datetime-local" name="due_at"
                       value="{{ old('due_at', $workOrder->due_at?->format('Y-m-d\TH:i')) }}"
                       class="input w-full">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Description</label>
                <textarea name="description" rows="4" class="input w-full resize-none">{{ old('description', $workOrder->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <a href="{{ route('work-orders.show', $workOrder) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
