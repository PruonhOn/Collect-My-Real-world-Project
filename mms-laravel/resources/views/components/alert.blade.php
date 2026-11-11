{{-- resources/views/components/alert.blade.php --}}
@props(['type' => 'success', 'message'])

@php
$styles = [
    'success' => 'bg-green-500/10 border-green-500/20 text-green-400',
    'error'   => 'bg-red-500/10 border-red-500/20 text-red-400',
    'warning' => 'bg-amber-500/10 border-amber-500/20 text-amber-400',
    'info'    => 'bg-blue-500/10 border-blue-500/20 text-blue-400',
];
$icons = ['success' => '✓', 'error' => '✕', 'warning' => '⚠', 'info' => 'ℹ'];
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-end="opacity-0 translate-y-1"
     class="flex items-center gap-3 px-4 py-3 rounded-xl border {{ $styles[$type] }} text-sm mb-4">
    <span>{{ $icons[$type] }}</span>
    <span class="flex-1">{{ $message }}</span>
    <button @click="show = false" class="opacity-60 hover:opacity-100 transition">✕</button>
</div>


{{-- resources/views/components/empty-state.blade.php --}}
@props(['title' => 'Nothing here', 'description' => '', 'action' => null, 'actionUrl' => '#', 'icon' => '◈'])

<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="w-14 h-14 rounded-2xl bg-gray-800/60 flex items-center justify-center text-2xl mb-4 text-gray-600">
        {{ $icon }}
    </div>
    <h3 class="text-sm font-semibold text-gray-300 mb-1">{{ $title }}</h3>
    @if($description)
    <p class="text-sm text-gray-600 max-w-xs mb-4">{{ $description }}</p>
    @endif
    @if($action)
    <a href="{{ $actionUrl }}" class="btn-primary text-sm">{{ $action }}</a>
    @endif
</div>


{{-- resources/views/components/stat-card.blade.php --}}
@props(['label', 'value', 'color' => 'blue', 'trend' => null, 'trendUp' => true])

<div class="kpi-card p-5">
    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">{{ $label }}</p>
    <p class="text-3xl font-semibold tracking-tight text-{{ $color }}-400 mb-1">{{ $value }}</p>
    @if($trend)
    <p class="text-xs {{ $trendUp ? 'text-green-400' : 'text-red-400' }}">
        {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
    </p>
    @endif
    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-{{ $color }}-500 rounded-b-xl opacity-50"></div>
</div>


{{-- resources/views/components/badge.blade.php --}}
@props(['color' => 'gray', 'size' => 'sm'])

@php
$colors = [
    'blue'   => 'bg-blue-500/15 text-blue-400',
    'green'  => 'bg-green-500/15 text-green-400',
    'red'    => 'bg-red-500/15 text-red-400',
    'yellow' => 'bg-yellow-500/15 text-yellow-400',
    'gray'   => 'bg-gray-700/60 text-gray-400',
    'purple' => 'bg-purple-500/15 text-purple-400',
    'amber'  => 'bg-amber-500/15 text-amber-400',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
