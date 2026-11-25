@props(['course', 'participants' => false, 'variant' => null])
@php
    $items = [['video', $course->segments_count.' video']];

    if ((int) $course->segments_sum_duration_seconds > 0) {
        $items[] = ['clock', \App\Support\Format::duration((int) $course->segments_sum_duration_seconds)];
    }

    if ((int) ($course->materials_count ?? 0) > 0) {
        $items[] = ['file', $course->materials_count.' materi PDF'];
    }

    $items[] = ['infinity', 'Akses selamanya'];

    if ($participants && (int) ($course->enrollments_count ?? 0) > 0) {
        $items[] = ['users', 'Sudah diikuti '.$course->enrollments_count.' orang'];
    }
@endphp
<ul {{ $attributes->merge(['class' => $variant ? 'meta-list meta-list--'.$variant : 'meta-list']) }}>
    @foreach ($items as [$icon, $text])
        <li><x-icon :name="$icon" :size="16" /><span>{{ $text }}</span></li>
    @endforeach
</ul>
