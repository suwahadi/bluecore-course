<x-layouts.app title="Daftar atau masuk" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="mx-auto max-w-[560px] text-center">
                <h1>Daftar dulu untuk melanjutkan</h1>
                <p class="muted mt-2.5">Buat akun atau masuk dengan akun yang sudah ada. Setelah itu kamu langsung kembali ke halaman ini.</p>
                <x-button-pair class="mx-auto mt-5 max-w-[400px]">
                    <a class="btn btn--primary" href="{{ route('register').'?next='.urlencode($next) }}">Buat akun</a>
                    <a class="btn" href="{{ route('login').'?next='.urlencode($next) }}">Masuk</a>
                </x-button-pair>
            </div>
        </div>
    </section>
</x-layouts.app>
