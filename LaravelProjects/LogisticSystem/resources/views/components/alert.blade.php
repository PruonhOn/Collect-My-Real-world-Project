@props(['type' => 'success', 'message' => '', 'dismissible' => true])

@php
    $icons = [
        'success' => 'bi-check-circle',
        'error' => 'bi-exclamation-triangle',
        'danger' => 'bi-exclamation-triangle',
        'warning' => 'bi-exclamation-circle',
        'info' => 'bi-info-circle',
    ];
    $type = in_array($type, ['error', 'danger']) ? 'danger' : $type;
    $icon = $icons[$type] ?? 'bi-info-circle';
    $message = $message ?: session($type) ?? '';
@endphp

@if($message)
    <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
        <i class="bi {{ $icon }} me-2"></i>
        {{ $message }}
        @if($dismissible)
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        @endif
    </div>
@endif
