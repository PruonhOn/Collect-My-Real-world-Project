{{-- resources/views/livewire/dashboard/kpi-cards.blade.php --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4" wire:poll.30s="loadStats">
    @php
    $cards = [
        ['key'=>'open',      'label'=>'Open Orders',   'color'=>'blue',  'icon'=>'⚙'],
        ['key'=>'overdue',   'label'=>'Overdue',       'color'=>'red',   'icon'=>'⚠'],
        ['key'=>'completed', 'label'=>'Done Today',    'color'=>'green', 'icon'=>'✓'],
        ['key'=>'in_prog',   'label'=>'In Progress',   'color'=>'amber', 'icon'=>'◷'],
    ];
    @endphp
    @foreach ($cards as $c)
    <div class="kpi-card p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">{{ $c['label'] }}</p>
            <span class="text-sm bg-{{ $c['color'] }}-500/15 text-{{ $c['color'] }}-400 w-7 h-7 flex items-center justify-center rounded-lg">{{ $c['icon'] }}</span>
        </div>
        <p class="text-3xl font-semibold text-{{ $c['color'] }}-400 tracking-tight">{{ $stats[$c['key']] ?? 0 }}</p>
        <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-{{ $c['color'] }}-500 rounded-b-xl opacity-50"></div>
    </div>
    @endforeach
</div>
