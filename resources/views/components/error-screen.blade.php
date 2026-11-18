@props(['code', 'title', 'message'])
<x-layouts.app :title="$title" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="mx-auto max-w-[560px] text-center">
                <p class="muted">{{ $code }}</p>
                <h1 class="mt-1.5">{{ $title }}</h1>
                <p class="muted mt-2.5">{{ $message }}</p>
                <div class="btn-row mt-5 justify-center">
                    <a class="btn btn--brand" href="{{ url('/') }}">Kembali ke beranda</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
