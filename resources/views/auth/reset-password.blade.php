<x-layouts.app title="Atur ulang kata sandi" :noindex="true">
    <x-auth-panel title="Atur ulang kata sandi">
        <x-form action="{{ route('password.update') }}">
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <input type="hidden" name="email" value="{{ old('email', $request->email) }}">
            <x-password-input name="password" label="Kata sandi baru" help="Minimal 8 karakter." autocomplete="new-password" required />
            <x-password-input name="password_confirmation" label="Ulangi kata sandi" autocomplete="new-password" required />
            @error('email')
                <p class="field__error" role="alert">{{ $message }} <a href="{{ route('password.request') }}">Minta tautan baru</a>.</p>
            @enderror
            <div class="btn-row">
                <button type="submit" class="btn btn--brand btn--block">Atur ulang kata sandi</button>
            </div>
        </x-form>
    </x-auth-panel>
</x-layouts.app>
