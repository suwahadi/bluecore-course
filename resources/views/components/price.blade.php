@props(['course', 'badge' => true])
@php
    $percent = $course->discountPercent();
@endphp
<strong class="num">{{ \App\Support\Format::rupiah($course->price) }}</strong>
@if ($percent)
    <span class="strike num">{{ \App\Support\Format::rupiah($course->compare_at_price) }}</span>
    @if ($badge)
        <span class="badge badge--accent">Hemat {{ $percent }}%</span>
    @endif
@endif
