@php
    $user = auth()->user();
@endphp
<x-layouts.app title="Akun" :noindex="true">
    <section class="section">
        <div class="wrap">
            <div class="page-head">
                <h1>Akun</h1>
            </div>
            <div class="two-col two-col--even">
                <x-form method="PUT" action="{{ route('user-profile-information.update') }}" class="panel panel--form">
                    <h3>Profil</h3>
                    <x-field name="name" label="Nama" :value="$user->name" bag="updateProfileInformation" required autocomplete="name" />
                    <x-field name="email" label="Email" type="email" :value="$user->email" help="Mengganti email akan meminta verifikasi ulang." bag="updateProfileInformation" required autocomplete="email" />
                    <x-field name="phone" label="Nomor telepon" type="tel" :value="$user->phone" help="Format 08xx atau 628xx." bag="updateProfileInformation" required inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" />
                    <div class="btn-row">
                        <button type="submit" class="btn btn--brand">Simpan profil</button>
                    </div>
                </x-form>
                <x-form method="PUT" action="{{ route('user-password.update') }}" class="panel panel--form">
                    <h3>Ganti kata sandi</h3>
                    <input type="email" name="username" value="{{ $user->email }}" autocomplete="username" hidden>
                    <x-password-input name="current_password" label="Kata sandi saat ini" bag="updatePassword" autocomplete="current-password" required />
                    <x-password-input name="password" label="Kata sandi baru" help="Minimal 8 karakter." bag="updatePassword" autocomplete="new-password" required />
                    <x-password-input name="password_confirmation" label="Ulangi kata sandi baru" bag="updatePassword" autocomplete="new-password" required />
                    <div class="btn-row">
                        <button type="submit" class="btn btn--brand">Ganti kata sandi</button>
                    </div>
                </x-form>
            </div>
        </div>
    </section>
</x-layouts.app>
