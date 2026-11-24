@php
    $next = \App\Support\NextPath::sanitize(request('next'));
@endphp
<x-layouts.app title="Masuk" :noindex="true">
    <x-auth-panel title="Masuk">
        <x-form action="{{ route('login.store') }}">
            @if ($next)
                <input type="hidden" name="next" value="{{ $next }}">
            @endif
            <x-field name="email" label="Email" type="email" required autocomplete="username" />
            <x-password-input name="password" label="Kata sandi" autocomplete="current-password" required />
            <label class="field field--inline">
                <input type="checkbox" name="remember" value="1" @checked(old('remember', true))>
                <span>Ingat saya</span>
            </label>
            <div class="btn-row">
                <button type="submit" class="btn btn--brand btn--block">Masuk</button>
            </div>
            <div class="grid gap-1.5">
                <p class="small"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
                <p class="muted small">Belum punya akun? <a href="{{ route('register').($next ? '?next='.urlencode($next) : '') }}">Buat akun</a>.</p>
            </div>
        </x-form>
    </x-auth-panel>
</x-layouts.app>
