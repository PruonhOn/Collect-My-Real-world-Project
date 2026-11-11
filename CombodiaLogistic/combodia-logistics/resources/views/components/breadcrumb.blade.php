@props(['items' => []])

@if(!empty($items))
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0" style="background: transparent; padding: 0; margin-bottom: 1rem;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none"><i class="bi bi-house"></i></a></li>
        @foreach($items as $index => $item)
            @if($index === count($items) - 1)
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @else
                <li class="breadcrumb-item">
                    @if(isset($item['url']) && $item['url'])
                        <a href="{{ $item['url'] }}" class="text-decoration-none">{{ $item['label'] }}</a>
                    @else
                        {{ $item['label'] }}
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
