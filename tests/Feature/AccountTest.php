<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function profileInput(array $overrides = []): array
{
    return array_merge(['name' => 'Budi Santoso', 'email' => 'budi@contoh.id', 'phone' => '08123456789'], $overrides);
}

it('shows both forms with the PRD helper texts and current values', function () {
    $user = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@contoh.id', 'phone' => '08123456789']);

    $this->actingAs($user)->get('/account')
        ->assertOk()
        ->assertSeeText('Akun')
        ->assertSeeText('Profil')
        ->assertSeeText('Ganti kata sandi')
        ->assertSeeText('Mengganti email akan meminta verifikasi ulang.')
        ->assertSeeText('Format 08xx atau 628xx.')
        ->assertSeeText('Minimal 8 karakter.')
        ->assertSee('value="budi@contoh.id"', false)
        ->assertSee('value="08123456789"', false)
        ->assertSee('<meta name="robots" content="noindex">', false);
});

it('updates the profile and shows the toast', function () {
    $user = User::factory()->create(['email' => 'budi@contoh.id']);

    $this->actingAs($user)->put('/user/profile-information', profileInput(['name' => 'Budi S.', 'phone' => '+62 812-1111-2222']))
        ->assertRedirect()
        ->assertSessionHas('status', 'profile-information-updated');

    expect($user->fresh()->name)->toBe('Budi S.')->and($user->fresh()->phone)->toBe('+6281211112222')
        ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($user)->withSession(['status' => 'profile-information-updated'])->get('/account')->assertSee('Profil disimpan.', false);
});

it('clears verification and resends when the email changes', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'budi@contoh.id']);

    $this->actingAs($user)->put('/user/profile-information', profileInput(['email' => 'BUDI.BARU@contoh.id']));

    expect($user->fresh()->email)->toBe('budi.baru@contoh.id')->and($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('does not resend when the email stays the same', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'budi@contoh.id']);

    $this->actingAs($user)->put('/user/profile-information', profileInput(['email' => 'Budi@Contoh.id']));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
});

it('validates the profile like registration', function () {
    User::factory()->create(['email' => 'dipakai@contoh.id']);
    $user = User::factory()->create(['email' => 'budi@contoh.id']);

    $this->actingAs($user)->put('/user/profile-information', profileInput(['name' => '', 'phone' => '0712', 'email' => 'dipakai@contoh.id']))
        ->assertSessionHasErrorsIn('updateProfileInformation', [
            'name' => 'Nama wajib diisi.',
            'phone' => 'Nomor telepon belum benar. Gunakan format 08xx atau 628xx.',
            'email' => 'Email sudah dipakai akun lain.',
        ]);
    $this->actingAs($user)->put('/user/profile-information', profileInput(['phone' => '']))
        ->assertSessionHasErrorsIn('updateProfileInformation', ['phone' => 'Nomor telepon wajib diisi.']);
});

it('changes the password after checking the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/password', [
        'current_password' => 'password', 'password' => 'kata-sandi-baru', 'password_confirmation' => 'kata-sandi-baru',
    ])->assertRedirect()->assertSessionHas('status', 'password-updated');

    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
    $this->actingAs($user)->withSession(['status' => 'password-updated'])->get('/account')->assertSee('Kata sandi diganti.', false);
});

it('rejects a wrong or empty current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/password', ['current_password' => 'salah', 'password' => 'kata-sandi-baru', 'password_confirmation' => 'kata-sandi-baru'])
        ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'Kata sandi saat ini salah.']);
    $this->actingAs($user)->put('/user/password', ['current_password' => '', 'password' => 'kata-sandi-baru', 'password_confirmation' => 'kata-sandi-baru'])
        ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'Kata sandi saat ini wajib diisi.']);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('rejects a short or unconfirmed new password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/password', ['current_password' => 'password', 'password' => 'pendek', 'password_confirmation' => 'lain'])
        ->assertSessionHasErrorsIn('updatePassword', [
            'password' => 'Kata sandi minimal 8 karakter.',
            'password_confirmation' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
        ]);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('shows errors from each form under its own fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/account')
        ->put('/user/password', ['current_password' => 'salah', 'password' => 'kata-sandi-baru', 'password_confirmation' => 'kata-sandi-baru'])
        ->assertRedirect('/account');

    $this->actingAs($user)->get('/account')
        ->assertSee('id="current_password-error"', false)
        ->assertSeeText('Kata sandi saat ini salah.')
        ->assertDontSee('id="email-error"', false);
});

it('orders the account headings without skipping a level', function () {
    $html = $this->actingAs(User::factory()->create())->get('/account')->getContent();

    expect($html)->toContain('<h2 class="panel-title">Profil</h2>')->toContain('<h2 class="panel-title">Ganti kata sandi</h2>')->not->toContain('<h3>Profil</h3>');
});
