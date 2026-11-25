<?php

use App\Enums\SignupSource;
use App\Models\User;

it('promotes an existing user and verifies the email', function () {
    $user = User::factory()->unverified()->create(['email' => 'budi@contoh.id']);

    $this->artisan('app:make-admin', ['email' => 'budi@contoh.id'])
        ->expectsOutput('Akun budi@contoh.id sekarang admin.')
        ->assertSuccessful();

    expect($user->fresh()->isAdmin())->toBeTrue()
        ->and($user->fresh()->email_verified_at)->not->toBeNull();
});

it('creates a new verified admin', function () {
    $this->artisan('app:make-admin', ['email' => 'admin@contoh.id'])
        ->expectsQuestion('Nama', 'Admin Utama')
        ->expectsQuestion('Kata sandi', 'rahasia123')
        ->expectsOutput('Admin admin@contoh.id dibuat.')
        ->assertSuccessful();

    $admin = User::where('email', 'admin@contoh.id')->first();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->name)->toBe('Admin Utama')
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->signup_source)->toBe(SignupSource::Direct);
});

it('asks again when the password is too short', function () {
    $this->artisan('app:make-admin', ['email' => 'admin@contoh.id'])
        ->expectsQuestion('Nama', 'Admin Utama')
        ->expectsQuestion('Kata sandi', 'pendek')
        ->expectsOutput('Kata sandi minimal 8 karakter.')
        ->expectsQuestion('Kata sandi', 'rahasia123')
        ->assertSuccessful();
});

it('rejects an invalid email', function () {
    $this->artisan('app:make-admin', ['email' => 'bukan-email'])
        ->expectsOutput('Format email belum benar.')
        ->assertFailed();
});

it('asks again when the name is empty', function () {
    $this->artisan('app:make-admin', ['email' => 'admin@contoh.id'])
        ->expectsQuestion('Nama', ' ')
        ->expectsOutput('Nama wajib diisi.')
        ->expectsQuestion('Nama', 'Admin Utama')
        ->expectsQuestion('Kata sandi', 'rahasia123')
        ->assertSuccessful();

    expect(App\Models\User::firstWhere('email', 'admin@contoh.id')->name)->toBe('Admin Utama');
});

it('refuses to create a new admin without an interactive terminal', function () {
    $this->artisan('app:make-admin', ['email' => 'admin@contoh.id', '--no-interaction' => true])
        ->expectsOutput('Admin baru dibuat lewat terminal interaktif agar nama dan kata sandi bisa diisi.')
        ->assertFailed();

    expect(App\Models\User::query()->where('email', 'admin@contoh.id')->exists())->toBeFalse();
});
