@php
    use App\Support\Settings;
    $heroEyebrow = Settings::get('hero_eyebrow');
    $heroSubtitle = Settings::get('hero_subtitle');
    $ctaTitle = Settings::get('cta_title');
    $ctaText = Settings::get('cta_text');
    $pitchHtml = \App\Support\Markdown::html(Settings::get('home_courses_text'));
@endphp
<x-layouts.app>
    <section class="section section--hero">
        <div class="wrap hero">
            <div class="hero__copy">
                @if (filled($heroEyebrow))
                    <p class="hero__eyebrow">{{ $heroEyebrow }}</p>
                @endif
                <h1>{{ Settings::get('hero_title') ?: Settings::get('site_name') }}</h1>
                @if (filled($heroSubtitle))
                    <p class="hero__subtitle">{{ $heroSubtitle }}</p>
                @endif
                <div class="hero__cta">
                    @if ($freeVideoUrl)
                        <a class="btn btn--primary" href="{{ $freeVideoUrl }}">NONTON GRATIS</a>
                    @else
                        <a class="btn btn--primary" href="{{ $courseUrl }}">Lihat kursus</a>
                    @endif
                </div>
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
                        <x-course-card :course="$course" :owned="isset($owned[$course->id])" :progress="$owned[$course->id] ?? null" />
                    @endforeach
                </div>
                @if ($pitchHtml && $featured)
                    <div class="upsell home-pitch">
                        <div class="upsell__copy">
                            <div class="prose">{!! $pitchHtml !!}</div>
                        </div>
                        <div class="panel upsell__cta">
                            <a class="btn btn--primary btn--block" href="{{ $courseUrl }}">Lihat kursus lengkap</a>
                            <p class="upsell__price">{{ \App\Support\Format::rupiah($featured->price) }} • Bayar sekali, akses selamanya</p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <x-testimonials :items="$testimonials" />

    @if ($faqItems)
        <section class="section" id="faq">
            <div class="wrap faq">
                <h2>{{ $faq->title }}</h2>
                <x-faq-list :items="$faqItems" />
            </div>
        </section>
    @endif

    @if (filled($ctaTitle))
        <section class="section cta-band">
            <div class="wrap cta-band__inner">
                <h2>{{ $ctaTitle }}</h2>
                @if (filled($ctaText))
                    <p>{{ $ctaText }}</p>
                @endif
                @if (! auth()->check() && $freeVideoUrl)
                    <a class="btn btn--primary" href="{{ $freeVideoUrl }}">Tonton video gratis</a>
                @else
                    <a class="btn btn--primary" href="{{ $courseUrl }}">Lihat kursus lengkap</a>
                @endif
                @guest
                    <p class="cta-band__note">Daftar gratis • Bayar sekali, akses selamanya</p>
                @endguest
            </div>
        </section>
    @endif
</x-layouts.app>
