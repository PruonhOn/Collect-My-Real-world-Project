@props(['href', 'active' => false, 'icon'])

<a href="{{ $href }}"
    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
    {{ $active ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
    {!! $icon !!}
    <span>{{ $slot }}</span>
</a>
