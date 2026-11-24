@props(['course', 'owned' => false])
@php
    $percent = $owned ? 0 : $course->discountPercent();
@endphp
<a class="course-card" href="{{ route('courses.show', $course) }}">
    <span class="course-card__media">
        @if ($course->thumbnail_path)
            <img class="thumb" src="{{ Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail {{ $course->title }}" width="1200" height="675" loading="lazy">
        @else
            <x-thumbnail-placeholder :title="$course->title" />
        @endif
        @if ($percent)
            <span class="badge course-card__tag" title="Hemat {{ $percent }}%">−{{ $percent }}%</span>
        @endif
    </span>
    <span class="course-card__body">
        <span class="course-card__cat">{{ $course->category?->name }}</span>
        <span class="course-card__title">{{ $course->title }}</span>
        <span class="course-card__meta">
            <span><x-icon name="video" :size="16" />{{ $course->segments_count }} video</span>
            <span><x-icon name="clock" :size="16" />{{ \App\Support\Format::duration((int) $course->segments_sum_duration_seconds) }}</span>
        </span>
        <span class="course-card__price">
            @if ($owned)
                <span class="badge badge--brand">Dimiliki</span>
            @else
                <x-price :course="$course" :badge="false" />
            @endif
        </span>
    </span>
</a>
