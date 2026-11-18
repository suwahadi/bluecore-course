<?php

namespace App\Console\Commands;

use App\Enums\SignupSource;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {email}')]
#[Description('Jadikan akun sebagai admin, atau buat admin baru')]
class MakeAdmin extends Command
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Format email belum benar.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'role' => UserRole::Admin,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $this->info("Akun {$email} sekarang admin.");

            return self::SUCCESS;
        }

        $user = new User([
            'name' => $this->ask('Nama'),
            'email' => $email,
            'password' => $this->askPassword(),
            'signup_source' => SignupSource::Direct,
        ]);

        $user->forceFill([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ])->save();

        $this->info("Admin {$email} dibuat.");

        return self::SUCCESS;
    }

    private function askPassword(): string
    {
        while (true) {
            $password = (string) $this->secret('Kata sandi');

            if (strlen($password) >= self::MIN_PASSWORD_LENGTH) {
                return $password;
            }

            $this->error('Kata sandi minimal 8 karakter.');
        }
    }
}
