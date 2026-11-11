@props(['name' => '', 'bag' => 'default'])

@error($name, $bag)
    <div {{ $attributes->merge(['class' => 'invalid-feedback d-block']) }}>
        @if(is_array($message))
            @foreach($message as $line)
                <span>{{ $line }}</span>
            @endforeach
        @else
            {{ $message }}
        @endif
    </div>
@enderror
