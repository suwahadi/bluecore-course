<?php

namespace App\Console\Commands;

use App\Enums\SignupSource;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\PersonName;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

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

        if (! $this->input->isInteractive()) {
            $this->error('Admin baru dibuat lewat terminal interaktif agar nama dan kata sandi bisa diisi.');

            return self::FAILURE;
        }

        $user = new User([
            'name' => $this->askName(),
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

    private function askName(): string
    {
        while (true) {
            $name = trim((string) $this->ask('Nama'));
            $validator = Validator::make(['name' => $name], ['name' => PersonName::rules()], PersonName::messages());

            if ($validator->passes()) {
                return $name;
            }

            $this->error($validator->errors()->first('name'));
        }
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
