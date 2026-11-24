<?php

namespace App\Actions\Fortify;

trait PasswordValidationRules
{
    protected function passwordRules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    protected function passwordMessages(): array
    {
        return [
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password_confirmation.required' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
            'password_confirmation.same' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
        ];
    }
}
