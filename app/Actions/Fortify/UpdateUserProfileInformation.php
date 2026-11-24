<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function update(User $user, array $input): void
    {
        $input['phone'] = PhoneNumber::clean($input['phone'] ?? null);
        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => PhoneNumber::rules(),
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar, contoh nama@domain.com.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            ...PhoneNumber::messages(),
        ])->validateWithBag('updateProfileInformation');

        $emailChanged = $input['email'] !== $user->email;

        $user->forceFill([
            'name' => trim($input['name']),
            'email' => $input['email'],
            'phone' => $input['phone'],
            ...($emailChanged ? ['email_verified_at' => null] : []),
        ])->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
