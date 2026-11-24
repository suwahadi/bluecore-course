@if ($paginator->hasPages())
    <nav class="pager" aria-label="Navigasi halaman">
        <button type="button" class="btn btn--small" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage())>Sebelumnya</button>
        <span class="pager__info">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        <button type="button" class="btn btn--small" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages())>Berikutnya</button>
    </nav>
@endif
