<?php

namespace App\Actions\Fortify;

use App\Enums\SignupSource;
use App\Models\User;
use App\Support\NextPath;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        $input['phone'] = PhoneNumber::clean($input['phone'] ?? null);
        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => PhoneNumber::rules(),
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar, contoh nama@domain.com.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email sudah terdaftar. Masuk dengan email itu.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password_confirmation.required' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
            'password_confirmation.same' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
            ...PhoneNumber::messages(),
        ])->validate();

        return User::create([
            'name' => trim($input['name']),
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => $input['password'],
            'signup_source' => self::signupSource($input['next'] ?? null),
        ]);
    }

    private static function signupSource(?string $next): SignupSource
    {
        $path = NextPath::sanitize($next);

        return match (true) {
            $path === '/free-video' => SignupSource::FreeVideo,
            $path !== null && str_starts_with($path, '/courses/') => SignupSource::Course,
            default => SignupSource::Direct,
        };
    }
}
