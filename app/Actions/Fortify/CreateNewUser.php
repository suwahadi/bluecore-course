<?php

namespace App\Actions\Fortify;

use App\Enums\SignupSource;
use App\Models\User;
use App\Support\NextPath;
use App\Support\PersonName;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        $input['phone'] = PhoneNumber::clean($input['phone'] ?? null);
        $input['email'] = is_string($input['email'] ?? null) ? Str::lower(trim($input['email'])) : '';

        Validator::make($input, [
            'name' => PersonName::rules(),
            'phone' => PhoneNumber::rules(),
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            ...$this->passwordRules(),
        ], [
            ...PersonName::messages(),
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar, contoh nama@domain.com.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email sudah terdaftar. Masuk dengan email itu.',
            ...$this->passwordMessages(),
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
