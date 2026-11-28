@props(['code', 'title', 'message'])
<x-layouts.app :title="$title" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="panel panel--prose error-panel">
                <p class="error-panel__code">{{ $code }}</p>
                <h1>{{ $title }}</h1>
                <p class="muted">{{ $message }}</p>
                <div class="btn-row error-panel__actions">
                    <a class="btn btn--brand" href="{{ url('/') }}">Kembali ke beranda</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
