<x-layouts.app>
    <section class="section section--hero">
        <div class="wrap hero">
            <div class="hero__copy">
                <h1>{{ \App\Support\Settings::get('site_slogan') }}</h1>
                @if (filled(\App\Support\Settings::get('site_description')))
                    <p>{{ \App\Support\Settings::get('site_description') }}</p>
                @endif
                <div class="btn-pair">
                    @if ($freeVideo)
                        <a class="btn btn--primary" href="{{ url('/free-video') }}">Nonton gratis</a>
                    @endif
                    <a class="btn {{ $freeVideo ? 'btn--soft' : 'btn--primary' }}" href="{{ $featured ? route('courses.show', $featured) : url('/courses') }}">Lihat kursus</a>
                </div>
                @if ($topics->isNotEmpty())
                    <nav class="hero__topics" aria-label="Topik kursus">
                        <span>Topik:</span>
                        @foreach ($topics as $topic)
                            <a href="{{ url('/courses?cat='.$topic->slug) }}">{{ $topic->name }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>
            @if ($featured)
                <x-hero-player :course="$featured" />
            @endif
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="page-head">
                <h2>Kursus terbaru</h2>
                <a href="{{ url('/courses') }}">Semua kursus</a>
            </div>
            @if ($latest->isEmpty())
                <x-empty-state message="Belum ada kursus yang terbit. Kursus baru muncul di sini begitu admin menerbitkannya." />
            @else
                <div class="course-grid course-grid--four">
                    @foreach ($latest as $course)
                        <x-course-card :course="$course" :owned="in_array($course->id, $ownedIds, true)" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-testimonials :items="$testimonials" />
</x-layouts.app>
