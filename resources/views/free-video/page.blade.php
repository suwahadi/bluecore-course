<section class="section">
    <div class="wrap free-video">
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
            <h1>{{ $title }}</h1>
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </div>
</section>
@if ($courses->isNotEmpty())
    <section class="section section--band">
        <div class="wrap">
            <div class="page-head">
                <h2>Video lainnya ada di kursus lengkap</h2>
                <a href="{{ route('courses.index') }}">Semua kursus</a>
            </div>
            <div class="course-grid course-grid--four">
                @foreach ($courses as $course)
                    <x-course-card :course="$course" :owned="in_array($course->id, $ownedIds, true)" />
                @endforeach
            </div>
        </div>
    </section>
@endif
