@php
    $terms = \App\Models\Page::systemPage('terms');
    $privacy = \App\Models\Page::systemPage('privacy');
@endphp
<p {{ $attributes->merge(['class' => 'muted small']) }}>Dengan mendaftar, kamu menyetujui @if ($terms?->is_published)<a href="{{ url($terms->slug) }}">{{ $terms->title }}</a>@else{{ $terms->title ?? 'Syarat & Ketentuan' }}@endif dan @if ($privacy?->is_published)<a href="{{ url($privacy->slug) }}">{{ $privacy->title }}</a>@else{{ $privacy->title ?? 'Kebijakan Privasi' }}@endif.</p>
