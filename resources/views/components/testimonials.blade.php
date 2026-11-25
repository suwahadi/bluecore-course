@props(['items'])
@php
    $slider = $items->count() > 3;
@endphp
@if ($items->isNotEmpty())
    <section class="section section--band">
        <div class="wrap">
            <div class="page-head">
                <h2 id="testimonials-title">Testimoni pembeli</h2>
                @if ($slider)
                    <div class="carousel-nav">
                        <button type="button" class="btn btn-icon" data-carousel-prev aria-label="Testimoni sebelumnya" title="Sebelumnya"><x-icon name="back" /></button>
                        <button type="button" class="btn btn-icon" data-carousel-toggle aria-pressed="false" aria-label="Jeda testimoni" title="Jeda"><span data-icon-pause><x-icon name="pause" /></span><span data-icon-play hidden><x-icon name="play" /></span></button>
                        <button type="button" class="btn btn-icon" data-carousel-next aria-label="Testimoni berikutnya" title="Berikutnya"><x-icon name="forward" /></button>
                    </div>
                @endif
            </div>
            @if ($slider)
                <div class="carousel" data-carousel data-count="{{ $items->count() }}" role="region" aria-roledescription="carousel" aria-labelledby="testimonials-title">
                    <ul class="carousel__track">
                        @foreach ($items as $testimonial)
                            <li role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $items->count() }}">@include('components.partials.testimonial-card')</li>
                        @endforeach
                        @foreach ($items->take(3) as $testimonial)
                            <li aria-hidden="true" inert>@include('components.partials.testimonial-card')</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <ul class="testimonials">
                    @foreach ($items as $testimonial)
                        <li>@include('components.partials.testimonial-card')</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
@endif
