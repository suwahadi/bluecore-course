@php
    $prefix ??= '';
    $bag ??= 'default';
    $fieldId = fn (string $name) => $prefix === '' ? null : $prefix.$name;
@endphp
<x-field name="name" label="Nama" :id="$fieldId('name')" :bag="$bag" required autocomplete="name" placeholder="Nama lengkap" />
<x-field name="phone" label="Nomor telepon" type="tel" :id="$fieldId('phone')" :bag="$bag" help="Wajib diisi. Format 08xx atau 628xx." required inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" />
<x-field name="email" label="Email" type="email" :id="$fieldId('email')" :bag="$bag" :full="true" required autocomplete="username" placeholder="email@contoh.com" />
<x-password-input name="password" label="Kata sandi" :id="$fieldId('password')" :bag="$bag" help="Minimal 8 karakter." autocomplete="new-password" required />
<x-password-input name="password_confirmation" label="Ulangi kata sandi" :id="$fieldId('password_confirmation')" :bag="$bag" autocomplete="new-password" required />
