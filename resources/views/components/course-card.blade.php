@props(['course', 'owned' => false, 'progress' => null])
@php
    $percent = $owned ? 0 : $course->discountPercent();
@endphp
<a {{ $attributes->merge(['class' => 'course-card']) }} href="{{ route('courses.show', $course) }}">
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
    <div class="course-card__body">
        <span class="course-card__cat">{{ $course->category?->name }}</span>
        <span class="course-card__title">{{ $course->title }}</span>
        @if ($course->badge || $course->rating || $course->fish_type)
            <span class="course-card__labels">
                @if ($course->badge)
                    <span class="badge badge--hot">{{ $course->badge }}</span>
                @endif
                @if ($course->rating)
                    <span class="course-card__rating"><x-icon name="star" :size="14" :solid="true" /><span class="sr-only">Rating</span> {{ number_format($course->rating, 1, ',', '') }}</span>
                @endif
                @if ($course->fish_type)
                    <span>{{ $course->fish_type }}</span>
                @endif
            </span>
        @endif
        @if (filled($course->short_description))
            <span class="course-card__desc">{{ $course->short_description }}</span>
        @endif
        <x-course-meta :course="$course" class="course-card__meta" />
        @if ($owned && $progress)
            <x-segment-bar :progress="$progress" :thin="true" />
        @endif
        <span class="course-card__price">
            @if ($owned)
                <span class="badge badge--brand">Dimiliki</span>
            @else
                <x-price :course="$course" :badge="false" />
            @endif
        </span>
        @unless ($owned)
            <span class="course-card__more">Lihat isi kursus</span>
        @endunless
    </div>
</a>
