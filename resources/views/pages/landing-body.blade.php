<section class="section">
    <div class="wrap free-video">
        @unless ($page->is_published)
            <p class="note note--warn course-note">Halaman ini masih draf. Hanya admin yang bisa melihatnya.</p>
        @endunless
        @if ($videoId)
            <div class="player player--embed">
                <div data-player data-video-id="{{ $videoId }}"></div>
            </div>
        @else
            <div class="player" role="img" aria-label="Pratinjau video">
                <span class="player__play"><x-icon name="play" :size="26" :solid="true" /></span>
            </div>
        @endif
        <div class="free-video__copy">
            <h1>{{ $page->title }}</h1>
            @if ($html)
                <article class="panel panel--prose prose">{!! $html !!}</article>
            @endif
        </div>
    </div>
</section>
@if ($upsellCourse)
    <section class="section section--band">
        <div class="wrap upsell">
            <div class="upsell__copy">
                <p class="upsell__eyebrow">Lanjutan dari video gratis</p>
                @if ($upsellHtml)
                    <div class="prose">{!! $upsellHtml !!}</div>
                @else
                    <h2>{{ $upsellCourse->title }}</h2>
                    @if (filled($upsellCourse->short_description))
                        <p>{{ $upsellCourse->short_description }}</p>
                    @endif
                @endif
            </div>
            <div class="panel upsell__cta">
                @if ($ownsUpsell)
                    <a class="btn btn--primary btn--block" href="{{ route('learn.index', $upsellCourse) }}">Lanjutkan belajar</a>
                @else
                    <a class="btn btn--primary btn--block" href="{{ route('courses.show', $upsellCourse) }}">Lihat kursus lengkap</a>
                    <a class="upsell__link" href="{{ route('courses.show', $upsellCourse) }}#daftar-video">Lihat isi {{ $upsellCourse->segments_count }} bab</a>
                    <p class="upsell__price">{{ \App\Support\Format::rupiah($upsellCourse->price) }} • Bayar sekali, akses selamanya</p>
                    <p class="buybox__urgency">Belajar sebelum beli bibit, bukan setelah ikan bermasalah.</p>
                @endif
            </div>
        </div>
    </section>
@endif
