@props(['title', 'wide' => false])
<section class="section">
    <div class="wrap">
        <div class="{{ $wide ? 'mx-auto max-w-[720px]' : 'mx-auto max-w-[440px]' }}">
            <h1>{{ $title }}</h1>
            <div class="{{ $wide ? 'panel panel--auth mt-5' : 'panel mt-5' }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
