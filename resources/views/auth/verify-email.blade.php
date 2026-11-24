<x-layouts.app title="Verifikasi email" :noindex="true">
    <x-auth-panel title="Verifikasi email Anda">
        <x-form action="{{ route('verification.send') }}">
            <p>Kami sudah mengirim tautan verifikasi ke <strong>{{ auth()->user()->email }}</strong>. Buka email itu untuk melanjutkan.</p>
            <div class="btn-row">
                <button type="submit" class="btn btn--brand btn--block">Kirim ulang email verifikasi</button>
            </div>
            <p class="muted small">Salah alamat email? <a href="{{ url('/account') }}">Ganti di halaman Akun</a>.</p>
        </x-form>
    </x-auth-panel>
</x-layouts.app>
