@props(['messages'])

@if ($messages)
    @foreach ((array) $messages as $message)
        <div {{ $attributes->merge(['class' => 'text-danger small mt-1']) }}>{{ $message }}</div>
    @endforeach
@endif
