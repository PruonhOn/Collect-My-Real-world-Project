{{-- resources/views/livewire/work-orders/work-order-table.blade.php --}}
<div>
    {{-- Filters Bar --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Search orders…"
            class="bg-gray-800/60 border border-white/10 rounded-lg px-3 py-2 text-sm
                   text-gray-100 placeholder-gray-500 outline-none focus:border-blue-500/40 w-56"
        />

        <select wire:model.live="statusFilter"
                class="bg-gray-800/60 border border-white/10 rounded-lg px-3 py-2 text-sm text-gray-300">
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="assigned">Assigned</option>
            <option value="in_progress">In Progress</option>
            <option value="on_hold">On Hold</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <select wire:model.live="priorityFilter"
                class="bg-gray-800/60 border border-white/10 rounded-lg px-3 py-2 text-sm text-gray-300">
            <option value="">All Priorities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>

        @if($search || $statusFilter || $priorityFilter)
        <button wire:click="$set('search', ''); $set('statusFilter', ''); $set('priorityFilter', '');"
                class="text-xs text-gray-500 hover:text-gray-300 underline">
            Clear filters
        </button>
        @endif

        <div class="ml-auto text-xs text-gray-500">
            {{ $workOrders->total() }} orders
            <span wire:loading class="text-blue-400 ml-1">updating…</span>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-gray-900 border border-white/8 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/8">
                    @php
                        $cols = [
                            ['field' => 'code',     'label' => 'ID'],
                            ['field' => 'title',    'label' => 'Title'],
                            ['field' => 'asset_id', 'label' => 'Asset'],
                            ['field' => null,        'label' => 'Assigned To'],
                            ['field' => 'priority', 'label' => 'Priority'],
                            ['field' => 'status',   'label' => 'Status'],
                            ['field' => null,        'label' => 'Progress'],
                            ['field' => 'due_at',   'label' => 'Due'],
                            ['field' => null,        'label' => ''],
                        ];
                    @endphp
                    @foreach ($cols as $col)
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider
                               {{ $col['field'] ? 'cursor-pointer hover:text-gray-300 select-none' : '' }}"
                        @if($col['field']) wire:click="sort('{{ $col['field'] }}')" @endif>
                        {{ $col['label'] }}
                        @if($col['field'] && $sortField === $col['field'])
                            <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($workOrders as $wo)
                <tr class="hover:bg-white/[0.02] transition group"
                    x-data
                    x-intersect.once="$el.classList.add('animate-fade-in')">

                    {{-- Code --}}
                    <td class="px-4 py-3.5 text-gray-500 font-mono text-xs">{{ $wo->code }}</td>

                    {{-- Title --}}
                    <td class="px-4 py-3.5">
                        <a href="{{ route('work-orders.show', $wo) }}"
                           class="font-medium text-gray-100 hover:text-blue-400 transition">
                            {{ $wo->title }}
                        </a>
                        @if($wo->is_overdue)
                            <span class="ml-2 text-xs text-red-400 font-medium">OVERDUE</span>
                        @endif
                    </td>

                    {{-- Asset --}}
                    <td class="px-4 py-3.5 text-gray-400">{{ $wo->asset->name }}</td>

                    {{-- Assigned To --}}
                    <td class="px-4 py-3.5">
                        @if ($wo->technician)
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-purple-600/30 text-purple-300
                                            text-xs flex items-center justify-center font-medium">
                                    {{ strtoupper(substr($wo->technician->name, 0, 2)) }}
                                </div>
                                <span class="text-gray-300 text-xs">{{ $wo->technician->name }}</span>
                            </div>
                        @else
                            <span class="text-gray-600 text-xs">Unassigned</span>
                        @endif
                    </td>

                    {{-- Priority --}}
                    <td class="px-4 py-3.5">
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-1 rounded-full
                            {{ match($wo->priority) {
                                'critical' => 'bg-red-500/15 text-red-400',
                                'high'     => 'bg-orange-500/15 text-orange-400',
                                'medium'   => 'bg-yellow-500/15 text-yellow-400',
                                default    => 'bg-green-500/15 text-green-400',
                            } }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ ucfirst($wo->priority) }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-3.5">
                        @can('update', $wo)
                        <select wire:change="updateStatus({{ $wo->id }}, $event.target.value)"
                                class="text-xs rounded-full px-2.5 py-1 border-0 outline-none cursor-pointer
                                    {{ match($wo->status) {
                                        'completed'   => 'bg-green-500/15 text-green-400',
                                        'in_progress' => 'bg-blue-500/15 text-blue-400',
                                        'on_hold'     => 'bg-gray-500/15 text-gray-400',
                                        'cancelled'   => 'bg-red-500/15 text-red-400',
                                        'assigned'    => 'bg-purple-500/15 text-purple-400',
                                        default       => 'bg-gray-700/50 text-gray-300',
                                    } }}">
                            @foreach (['open','assigned','in_progress','on_hold','completed','cancelled'] as $s)
                                <option value="{{ $s }}" @selected($wo->status === $s)>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                        @else
                        <span class="text-xs text-gray-400">{{ ucwords(str_replace('_', ' ', $wo->status)) }}</span>
                        @endcan
                    </td>

                    {{-- Progress --}}
                    <td class="px-4 py-3.5 w-32">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700
                                    {{ $wo->status === 'completed' ? 'bg-green-500' :
                                       ($wo->is_overdue ? 'bg-red-500' : 'bg-blue-500') }}"
                                     style="width: {{ $wo->progress }}%">
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 w-8 text-right">{{ $wo->progress }}%</span>
                        </div>
                    </td>

                    {{-- Due --}}
                    <td class="px-4 py-3.5 text-xs
                               {{ $wo->is_overdue ? 'text-red-400 font-medium' :
                                  ($wo->due_at?->diffInDays() <= 2 ? 'text-yellow-400' : 'text-gray-500') }}">
                        {{ $wo->due_at?->format('M d') ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                            <a href="{{ route('work-orders.show', $wo) }}"
                               class="text-xs px-2 py-1 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition">
                                View
                            </a>
                            @can('delete', $wo)
                            <button wire:click="delete({{ $wo->id }})"
                                    wire:confirm="Delete this work order?"
                                    class="text-xs px-2 py-1 rounded bg-red-500/10 hover:bg-red-500/20 text-red-400 transition">
                                Del
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-12 text-center text-gray-600">
                        No work orders found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if ($workOrders->hasPages())
        <div class="px-4 py-3 border-t border-white/8">
            {{ $workOrders->links() }}
        </div>
        @endif
    </div>
</div>
