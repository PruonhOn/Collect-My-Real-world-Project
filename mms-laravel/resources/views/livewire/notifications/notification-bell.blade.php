{{-- resources/views/livewire/notifications/notification-bell.blade.php --}}
<div class="relative" x-data="{ open: @entangle('open') }">
    <button @click="open = !open"
            class="relative w-8 h-8 rounded-lg bg-gray-800/60 border border-white/10
                   flex items-center justify-center text-gray-400 hover:text-gray-200 hover:bg-gray-700/60 transition">
        🔔
        @if($this->count > 0)
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold
                     rounded-full flex items-center justify-center border border-gray-900">
            {{ $this->count > 9 ? '9+' : $this->count }}
        </span>
        @endif
    </button>

    <div x-show="open"
         x-cloak
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="absolute right-0 top-full mt-2 w-80 bg-gray-900 border border-white/10 rounded-xl shadow-2xl z-50 overflow-hidden">

        <div class="flex items-center justify-between px-4 py-3 border-b border-white/[0.06]">
            <span class="text-sm font-semibold">Notifications</span>
            @if($this->count > 0)
            <button wire:click="markAllRead" class="text-xs text-blue-400 hover:text-blue-300">Mark all read</button>
            @endif
        </div>

        <div class="max-h-72 overflow-y-auto divide-y divide-white/[0.05]">
            @forelse ($this->notifications as $notif)
            <div wire:key="{{ $notif->id }}"
                 class="flex items-start gap-3 px-4 py-3 hover:bg-white/[0.03] transition cursor-pointer"
                 wire:click="markRead('{{ $notif->id }}')">
                <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0
                    {{ isset($notif->data['severity']) && $notif->data['severity'] === 'critical' ? 'bg-red-500' : 'bg-blue-500' }}">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-200 truncate">{{ $notif->data['title'] ?? 'Notification' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $notif->data['message'] ?? '' }}</p>
                    <p class="text-xs text-gray-600 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-sm text-gray-600">All caught up!</div>
            @endforelse
        </div>
    </div>
</div>


{{-- resources/views/livewire/work-orders/work-order-form.blade.php --}}
<div>
    @if (session('success'))
    <div class="mb-4 px-4 py-3 bg-green-500/10 border border-green-500/20 rounded-lg text-sm text-green-400">
        {{ session('success') }}
    </div>
    @endif

    <div class="card p-6">
        <h2 class="text-base font-semibold mb-5">{{ $workOrder ? 'Edit Work Order' : 'New Work Order' }}</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Title *</label>
                <input type="text" wire:model.live="title" class="input w-full @error('title') border-red-500/50 @enderror"
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
                    <select wire:model="asset_id" class="input w-full @error('asset_id') border-red-500/50 @enderror">
                        <option value="0">Select asset…</option>
                        @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->name }}</option>
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
                <textarea wire:model="description" rows="3" class="input w-full resize-none"
                          placeholder="Optional details…"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-white/[0.06]">
                <button wire:click="save" wire:loading.attr="disabled"
                        class="btn-primary flex items-center gap-2">
                    <span wire:loading wire:target="save" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    {{ $workOrder ? 'Save Changes' : 'Create Work Order' }}
                </button>
            </div>
        </div>
    </div>
</div>
