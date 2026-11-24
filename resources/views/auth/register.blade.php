@php
    $next = \App\Support\NextPath::sanitize(request('next'));
@endphp
<x-layouts.app title="Daftar" :noindex="true">
    <x-auth-panel title="Buat akun" :wide="true">
        <x-form action="{{ route('register.store') }}" :columns="2">
            @if ($next)
                <input type="hidden" name="next" value="{{ $next }}">
            @endif
            @include('auth.partials.register-fields')
            <div class="btn-row field--full">
                <button type="submit" class="btn btn--brand btn--block">Daftar</button>
            </div>
            <div class="field--full grid gap-1.5">
                <x-legal-consent />
                <p class="muted small">Sudah punya akun? <a href="{{ route('login').($next ? '?next='.urlencode($next) : '') }}">Masuk</a>.</p>
            </div>
        </x-form>
    </x-auth-panel>
</x-layouts.app>
