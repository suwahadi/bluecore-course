<x-layouts.app :title="$page->title" :noindex="! $page->is_published">
    <section class="section">
        <div class="wrap prose page-layout">
            @unless ($page->is_published)
                <p class="note note--warn course-note">Halaman ini masih draf. Hanya admin yang bisa melihatnya.</p>
            @endunless
            <h1>{{ $page->title }}</h1>
            <article class="panel panel--prose">
                @if ($html)
                    {!! $html !!}
                @else
                    <p class="muted">Konten belum diisi.</p>
                @endif
            </article>
        </div>
    </section>
</x-layouts.app>
