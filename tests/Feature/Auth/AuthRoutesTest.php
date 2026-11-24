<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('throttles each auth endpoint as the PRD requires', function (string $name, string $throttle) {
    expect(Route::getRoutes()->getByName($name)?->gatherMiddleware())->toContain($throttle);
})->with([
    ['register.store', 'throttle:5,1,register'],
    ['password.email', 'throttle:3,1,forgot-password'],
    ['password.update', 'throttle:5,1,reset-password'],
    ['verification.send', 'throttle:6,1,verification-send'],
    ['verification.verify', 'throttle:6,1,verify-email'],
    ['user-profile-information.update', 'throttle:6,1,profile'],
    ['user-password.update', 'throttle:6,1,password'],
]);

it('registers only the auth features the PRD uses', function () {
    expect(Route::has('two-factor.login'))->toBeFalse()
        ->and(Route::has('password.confirm'))->toBeFalse()
        ->and(Route::has('account'))->toBeTrue();
});

it('keeps the signed-in header on a 404', function () {
    $this->actingAs(User::factory()->create(['name' => 'Budi Santoso']))
        ->get('/tidak-ada')
        ->assertNotFound()
        ->assertSeeText('Budi Santoso')
        ->assertDontSee('href="'.url('/login').'"', false);
});

it('translates Fortify status keys in the toast', function () {
    $this->withSession(['status' => 'profile-information-updated'])
        ->get('/tidak-ada')
        ->assertSee('Profil disimpan.', false);
});

it('uses the Indonesian login messages', function () {
    expect(__('auth.failed'))->toBe('Email atau kata sandi salah.')
        ->and(__('auth.throttle', ['seconds' => 42]))->toBe('Terlalu banyak percobaan. Coba lagi dalam 42 detik.');
});

it('keeps a separate counter for each auth limit', function () {
    foreach (range(1, 3) as $attempt) {
        $this->post('/register', ['name' => '', 'email' => 'salah']);
    }

    $this->post('/forgot-password', ['email' => 'siapa@contoh.id'])->assertRedirect();
    $this->post('/register', ['name' => '', 'email' => 'salah'])->assertSessionHasErrors();
});
