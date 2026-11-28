<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

const SAME_RESET_ANSWER = 'Jika email terdaftar, tautan reset sudah dikirim.';

it('answers the same way for known and unknown emails', function () {
    Notification::fake();
    User::factory()->create(['email' => 'ada@contoh.id']);

    $this->post('/forgot-password', ['email' => 'ada@contoh.id'])
        ->assertRedirect()->assertSessionHas('status', SAME_RESET_ANSWER)->assertSessionHasNoErrors();
    $this->post('/forgot-password', ['email' => 'tidak@contoh.id'])
        ->assertRedirect()->assertSessionHas('status', SAME_RESET_ANSWER)->assertSessionHasNoErrors();
});

it('gives the same answer while the broker throttles repeated requests', function () {
    Notification::fake();
    User::factory()->create(['email' => 'ada@contoh.id']);

    $this->post('/forgot-password', ['email' => 'ada@contoh.id']);

    $this->post('/forgot-password', ['email' => 'ada@contoh.id'])
        ->assertSessionHas('status', SAME_RESET_ANSWER)->assertSessionHasNoErrors();
    Notification::assertSentTimes(ResetPasswordNotification::class, 1);
});

it('validates the email field in Indonesian', function () {
    $this->post('/forgot-password', ['email' => 'bukan-email'])
        ->assertSessionHasErrors(['email' => 'Format email belum benar, contoh nama@domain.com.']);
    $this->post('/forgot-password', ['email' => ''])
        ->assertSessionHasErrors(['email' => 'Email wajib diisi.']);
});

it('sends an Indonesian, queued reset email from the site', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'ada@contoh.id', 'name' => 'Budi Santoso']);

    $this->post('/forgot-password', ['email' => 'ada@contoh.id']);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $mail = $notification->toMail($user);

        return $notification instanceof ShouldQueue
            && $mail->subject === 'Atur ulang kata sandi'
            && $mail->actionText === 'Atur ulang kata sandi'
            && $mail->greeting === 'Halo,'
            && $mail->replyTo[0][0] === 'support@bluecore.co.id'
            && $mail->from[1] === 'Bluecore Course';
    });
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['email' => 'ada@contoh.id']);
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token, 'email' => 'ada@contoh.id',
        'password' => 'kata-sandi-baru', 'password_confirmation' => 'kata-sandi-baru',
    ])->assertRedirect('/login')->assertSessionHas('status', 'Kata sandi berhasil diatur ulang. Silakan masuk.');

    expect(Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
});

it('rejects a short or unconfirmed new password', function () {
    $user = User::factory()->create(['email' => 'ada@contoh.id']);

    $this->post('/reset-password', [
        'token' => Password::createToken($user), 'email' => 'ada@contoh.id',
        'password' => 'pendek', 'password_confirmation' => 'lain',
    ])->assertSessionHasErrors([
        'password' => 'Kata sandi minimal 8 karakter.',
        'password_confirmation' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
    ]);
});

it('gives the same error for a bad token and an unknown email', function () {
    $this->post('/reset-password', [
        'token' => 'salah', 'email' => 'tidak@contoh.id',
        'password' => 'kata-sandi-baru', 'password_confirmation' => 'kata-sandi-baru',
    ])->assertSessionHasErrors(['email' => 'Tautan atur ulang tidak valid atau sudah kedaluwarsa.']);
});

it('renders the forgot and reset forms', function () {
    $this->get('/forgot-password')->assertOk()->assertSeeText('Lupa kata sandi')->assertSeeText('Kirim tautan atur ulang');
    $this->get('/reset-password/abc?email=ada@contoh.id')
        ->assertOk()
        ->assertSee('name="token" value="abc"', false)
        ->assertSee('name="email" value="ada@contoh.id"', false)
        ->assertDontSeeText('Minimal 8 karakter.')
        ->assertSeeText('Atur ulang kata sandi');
});
