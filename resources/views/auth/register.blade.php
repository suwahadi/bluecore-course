@php
    $next = \App\Support\NextPath::sanitize(request('next'));
@endphp
<x-layouts.app title="Daftar" :noindex="true">
    <x-auth-panel title="Buat akun" :wide="true">
        <x-form action="{{ route('register.store') }}" :columns="2">
            @if ($next)
                <input type="hidden" name="next" value="{{ $next }}">
            @endif
            <x-field name="name" label="Nama" required autocomplete="name" placeholder="Nama lengkap" />
            <x-field name="phone" label="Nomor telepon" type="tel" help="Wajib diisi. Format 08xx atau 628xx." required inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" />
            <x-field name="email" label="Email" type="email" :full="true" required autocomplete="username" placeholder="email@contoh.com" />
            <x-password-input name="password" label="Kata sandi" help="Minimal 8 karakter." autocomplete="new-password" required />
            <x-password-input name="password_confirmation" label="Ulangi kata sandi" autocomplete="new-password" required />
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
