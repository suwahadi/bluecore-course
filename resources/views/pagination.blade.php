@if ($paginator->hasPages())
    <nav class="pager" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn--small" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="btn btn--small" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif
        <span class="pager__info">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn--small" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="btn btn--small" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
