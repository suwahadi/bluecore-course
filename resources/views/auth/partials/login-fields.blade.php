@php
    $prefix ??= '';
    $bag ??= 'default';
    $fieldId = fn (string $name) => $prefix === '' ? null : $prefix.$name;
@endphp
<x-field name="email" label="Email" type="email" :id="$fieldId('email')" :bag="$bag" required autocomplete="username" />
<x-password-input name="password" label="Kata sandi" :id="$fieldId('password')" :bag="$bag" autocomplete="current-password" required />
<label class="field field--inline">
    <input type="checkbox" name="remember" value="1" @checked(session()->hasOldInput() ? old('remember') : true)>
    <span>Ingat saya</span>
</label>
