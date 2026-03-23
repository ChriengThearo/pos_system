@if ($paginator->hasPages())
    <nav class="pager-nav" role="navigation" aria-label="Pagination">
        <div class="pager-links">
            @if ($paginator->onFirstPage())
                <span class="pager-btn disabled">Previous</span>
            @else
                <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-page active">{{ $page }}</span>
                        @else
                            <a class="pager-page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="pager-btn disabled">Next</span>
            @endif
        </div>
        <div class="pager-meta">
            Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
        </div>
    </nav>
@endif
