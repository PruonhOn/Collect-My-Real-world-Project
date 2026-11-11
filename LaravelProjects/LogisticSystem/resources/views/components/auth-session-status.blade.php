@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success border-0 small py-2']) }}>
        {{ $status }}
    </div>
@endif
