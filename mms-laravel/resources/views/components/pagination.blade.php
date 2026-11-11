{{-- resources/views/components/pagination.blade.php --}}
@if ($paginator->hasPages())
<nav class="flex items-center justify-between text-sm" aria-label="Pagination">
    <p class="text-gray-500 text-xs">
        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </p>
    <div class="flex items-center gap-1">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
        <span class="px-3 py-1.5 text-gray-600 rounded-lg cursor-not-allowed text-xs">← Prev</span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}"
           class="px-3 py-1.5 text-gray-400 hover:text-gray-200 bg-gray-800/60 hover:bg-gray-700/60 rounded-lg transition text-xs">
            ← Prev
        </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
            <span class="px-2 text-gray-600">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                <span class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium">{{ $page }}</span>
                @else
                <a href="{{ $url }}"
                   class="px-3 py-1.5 text-gray-400 hover:text-gray-200 bg-gray-800/60 hover:bg-gray-700/60 rounded-lg transition text-xs">
                    {{ $page }}
                </a>
                @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
           class="px-3 py-1.5 text-gray-400 hover:text-gray-200 bg-gray-800/60 hover:bg-gray-700/60 rounded-lg transition text-xs">
            Next →
        </a>
        @else
        <span class="px-3 py-1.5 text-gray-600 rounded-lg cursor-not-allowed text-xs">Next →</span>
        @endif
    </div>
</nav>
@endif
