@props(['type' => 'primary', 'submit' => false])

@php
    $styles = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-primary-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    ];
@endphp

<button type="{{ $submit ? 'submit' : 'button' }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition ' . $styles[$type]]) }}>
    {{ $slot }}
</button>
