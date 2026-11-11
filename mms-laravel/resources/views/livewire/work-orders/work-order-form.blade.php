{{-- resources/views/livewire/work-orders/work-order-form-page.blade.php --}}
<div>
    <div class="space-y-4">

        @error('*')
        <div class="px-4 py-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-400">
            Please fix the errors below.
        </div>
        @enderror

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-1.5">Title *</label>
            <input type="text" wire:model.live="title"
                   class="input w-full @error('title') !border-red-500/50 @enderror"
                   placeholder="Describe the maintenance task…">
            @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Type *</label>
                <select wire:model="type" class="input w-full">
                    <option value="corrective">Corrective</option>
                    <option value="preventive">Preventive</option>
                    <option value="inspection">Inspection</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Priority *</label>
                <select wire:model="priority" class="input w-full">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Asset *</label>
                <select wire:model="asset_id" class="input w-full @error('asset_id') !border-red-500/50 @enderror">
                    <option value="0">Select asset…</option>
                    @foreach ($assets as $asset)
                    <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->code }})</option>
                    @endforeach
                </select>
                @error('asset_id')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Assign To</label>
                <select wire:model="assigned_to" class="input w-full">
                    <option value="">Unassigned</option>
                    @foreach ($technicians as $tech)
                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-1.5">Due Date</label>
            <input type="datetime-local" wire:model="due_at" class="input w-full">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-1.5">Description</label>
            <textarea wire:model="description" rows="3"
                      class="input w-full resize-none"
                      placeholder="Optional: describe what needs to be done…"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-white/[0.06]">
            <a href="{{ route('work-orders.index') }}" class="btn-secondary">Cancel</a>
            <button wire:click="save" wire:loading.attr="disabled"
                    class="btn-primary flex items-center gap-2">
                <span wire:loading wire:target="save"
                      class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin">
                </span>
                {{ $workOrder ? 'Save Changes' : 'Create Work Order' }}
            </button>
        </div>
    </div>
</div>
