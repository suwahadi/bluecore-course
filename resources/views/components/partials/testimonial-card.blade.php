<figure class="testimonial">
    @unless ($testimonial->is_genuine)
        <span class="badge testimonial__tag">Contoh</span>
    @endunless
    <blockquote>{{ $testimonial->quote }}</blockquote>
    <figcaption><strong>{{ $testimonial->name }}</strong>@if ($testimonial->note) <span>• {{ $testimonial->note }}</span>@endif</figcaption>
</figure>
