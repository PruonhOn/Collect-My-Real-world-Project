{{-- resources/views/work-orders/create.blade.php --}}
@extends('layouts.app')
@section('title', 'New Work Order')
@section('page-title', 'New Work Order')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('work-orders.index') }}" class="text-gray-500 hover:text-gray-300 transition text-sm">← Work Orders</a>
        <span class="text-gray-700">/</span>
        <span class="text-sm text-gray-300">New</span>
    </div>

    <div class="card p-6">
        <h2 class="text-base font-semibold mb-6">Create Work Order</h2>

        <form action="{{ route('work-orders.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="input w-full @error('title') border-red-500/50 @enderror"
                           placeholder="e.g. Replace HVAC filter in Building A" required>
                    @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Type *</label>
                    <select name="type" class="input w-full" required>
                        @foreach (['corrective' => 'Corrective', 'preventive' => 'Preventive', 'inspection' => 'Inspection', 'emergency' => 'Emergency'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Priority *</label>
                    <select name="priority" class="input w-full" required>
                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('priority', 'medium') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Asset *</label>
                    <select name="asset_id" class="input w-full @error('asset_id') border-red-500/50 @enderror" required>
                        <option value="">Select asset…</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>
                                {{ $asset->name }} ({{ $asset->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('asset_id')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Assign To</label>
                    <select name="assigned_to" class="input w-full">
                        <option value="">Unassigned</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(old('assigned_to') == $tech->id)>
                                {{ $tech->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Due Date</label>
                    <input type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="input w-full">
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Description</label>
                    <textarea name="description" rows="4" class="input w-full resize-none"
                              placeholder="Describe the maintenance work needed…">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <a href="{{ route('work-orders.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Create Work Order</button>
            </div>
        </form>
    </div>
</div>
@endsection
