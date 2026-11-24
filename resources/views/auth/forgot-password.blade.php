<x-layouts.app title="Lupa kata sandi" :noindex="true">
    <x-auth-panel title="Lupa kata sandi">
        <x-form action="{{ route('password.email') }}">
            <x-field name="email" label="Email" type="email" required autocomplete="email" placeholder="email@contoh.com" />
            <div class="btn-row">
                <button type="submit" class="btn btn--brand btn--block">Kirim tautan atur ulang</button>
            </div>
            <p class="muted small"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
        </x-form>
    </x-auth-panel>
</x-layouts.app>
