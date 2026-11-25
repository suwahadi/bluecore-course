@props(['course'])
@php
    use App\Support\Format;
    $segments = $course->segments;
    $first = $segments->first();
    $queue = $segments->slice(1, 2);
@endphp
@if ($first)
    <div class="hero-player">
        <a class="hero-player__screen" href="{{ route('courses.show', $course) }}" aria-label="Lihat kursus {{ $course->title }}">
            @if ($course->thumbnail_path)
                <img class="thumb" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" width="1200" height="675">
            @else
                <x-thumbnail-placeholder :title="$course->title" />
            @endif
            <span class="hero-player__tag">Paket unggulan</span>
            <span class="hero-player__caption"><strong>1. {{ $first->title }}</strong><span>{{ $first->duration_seconds ? Format::duration($first->duration_seconds) : '' }}</span></span>
        </a>
        <div class="hero-player__body">
            <div class="hero-player__head">
                <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
                <strong class="num">{{ Format::rupiah($course->price) }}</strong>
            </div>
            <div>
                <div class="seg-bar" aria-hidden="true">
                    @foreach ($segments as $segment)
                        <span data-current="{{ $loop->first ? 'true' : 'false' }}"></span>
                    @endforeach
                </div>
                <p class="hero-player__progress"><span>Video 1 dari {{ $segments->count() }}</span><span>Berikutnya diputar otomatis</span></p>
            </div>
            @if ($queue->isNotEmpty())
                <ol class="hero-player__queue">
                    @foreach ($queue as $segment)
                        <li><span>{{ $loop->iteration + 1 }}. {{ $segment->title }}</span><span>{{ $segment->duration_seconds ? Format::duration($segment->duration_seconds) : '' }}</span></li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
@endif
