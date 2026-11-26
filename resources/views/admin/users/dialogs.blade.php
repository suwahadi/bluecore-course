@php
    use App\Enums\UserRole;
    $isSelf = $user->is(auth()->user());
    $ownedCourseIds = $user->enrollments->pluck('course_id')->all();
    $grantable = $courses->reject(fn ($course) => in_array($course->id, $ownedCourseIds, true));
    $roleOptions = collect(UserRole::cases())->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all();
@endphp
<x-admin.dialog-form :dialog="'user-'.$user->id" :action="route('admin.users.update', $user)" method="PUT" :title="'Edit pengguna '.$user->name" :wide="true" :columns="2" submit-label="Simpan pengguna">
    <x-field name="name" label="Nama" :value="$user->name" required maxlength="100" autocomplete="off" />
    <x-field name="email" label="Email" type="email" :value="$user->email" help="Jika diganti, status email kembali belum terverifikasi." required maxlength="150" autocomplete="off" />
    <x-field name="phone" label="Nomor telepon" type="tel" :value="$user->phone" inputmode="tel" autocomplete="off" placeholder="08xxxxxxxxxx" />
    @if ($isSelf)
        <x-field name="role" label="Peran" type="select" :options="$roleOptions" :value="$user->role->value" help="Peran akun sendiri tidak bisa diubah." disabled />
        <input type="hidden" name="role" value="{{ $user->role->value }}">
    @else
        <x-field name="role" label="Peran" type="select" :options="$roleOptions" :value="$user->role->value" />
    @endif
    <x-password-input name="password" label="Kata sandi baru" help="Kosongkan jika kata sandi tidak diganti." autocomplete="new-password" />
    <x-password-input name="password_confirmation" label="Ulangi kata sandi baru" autocomplete="new-password" />
    @unless ($isSelf)
        <x-slot:after>
            <div class="danger-zone">
                <button type="button" class="btn btn--danger btn--small" x-on:click="open = false; $dispatch('open-modal', 'anonymize-{{ $user->id }}')">Hapus data pribadi</button>
            </div>
        </x-slot:after>
    @endunless
</x-admin.dialog-form>

@unless ($isSelf)
    <x-modal :name="'anonymize-'.$user->id" title="Hapus data pribadi?">
        <p>Data pribadi {{ $user->name }} akan dihapus permanen. Riwayat pesanan tetap disimpan untuk pembukuan.</p>
        <x-form method="DELETE" :action="route('admin.users.anonymize', $user)">
            <x-button-pair>
                <button type="button" class="btn" x-on:click="open = false">Batal</button>
                <button type="submit" class="btn btn--danger">Ya, hapus data</button>
            </x-button-pair>
        </x-form>
    </x-modal>
@endunless

@if ($grantable->isEmpty())
    <x-modal :name="'grant-'.$user->id" title="Beri akses kursus">
        <p>{{ $user->name }} sudah punya akses ke semua kursus.</p>
        <button type="button" class="btn btn--block" x-on:click="open = false">Tutup</button>
    </x-modal>
@else
    <x-admin.dialog-form :dialog="'grant-'.$user->id" :action="route('admin.users.enrollments.store', $user)" title="Beri akses kursus" submit-label="Beri akses">
        <p class="muted small">Akses manual untuk <strong>{{ $user->name }}</strong>, {{ $user->email }}. Tidak membuat pesanan dan tidak dihitung sebagai pendapatan.</p>
        <x-field name="course_id" label="Kursus" type="select" :options="$grantable->mapWithKeys(fn ($course) => [$course->id => $course->title.($course->status === $draftStatus ? ' (draf)' : '')])->all()" required />
        <x-field name="note" label="Alasan" help="Tersimpan bersama akses sebagai catatan audit." required maxlength="200" placeholder="misalnya kompensasi kendala pembayaran" />
    </x-admin.dialog-form>
@endif
