<figure class="testimonial">
    <blockquote>{{ $testimonial->quote }}</blockquote>
    <figcaption>
        <strong>{{ $testimonial->name }}</strong>
        @if ($testimonial->note)
            <span>{{ $testimonial->note }}</span>
        @endif
    </figcaption>
</figure>
