@php
    use App\Support\Format;
@endphp
<x-layouts.app title="Kursus saya" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="page-head"><h1>Kursus saya</h1></div>
            @if ($enrollments->isEmpty())
                <x-empty-state message="Belum ada kursus. Kursus yang kamu beli muncul di sini.">
                    <a class="btn btn--brand" href="{{ route('courses.index') }}">Lihat kursus</a>
                </x-empty-state>
            @else
                <div class="course-grid">
                    @foreach ($enrollments as $enrollment)
                        @php
                            $course = $enrollment->course;
                            $summary = $progress[$course->id];
                            $complete = $summary->isComplete();
                        @endphp
                        <article class="course-card my-course">
                            <span class="course-card__media">
                                @if ($course->thumbnail_path)
                                    <img class="thumb" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="" width="1200" height="675" loading="lazy">
                                @else
                                    <x-thumbnail-placeholder :title="$course->title" />
                                @endif
                            </span>
                            <div class="course-card__body">
                                <span class="course-card__cat">{{ $course->category?->name }}</span>
                                <h2 class="course-card__title">{{ $course->title }}</h2>
                                <div class="progress-line my-course__progress">
                                    <span>{{ $summary->done() }} dari {{ $summary->total() }} video</span>
                                    <span class="num">{{ $summary->percent() }}%@if ($complete) <span class="badge badge--ok">Selesai</span>@endif</span>
                                </div>
                                <x-segment-bar :progress="$summary" :thin="true" />
                                <span class="muted small">{{ $enrollment->last_accessed_at ? 'Terakhir dibuka '.Format::datetime($enrollment->last_accessed_at) : 'Belum pernah dibuka' }}</span>
                                <a class="btn btn--brand btn--small my-course__cta" href="{{ route('learn.index', $course) }}">{{ $complete ? 'Putar ulang' : 'Lanjutkan belajar' }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
