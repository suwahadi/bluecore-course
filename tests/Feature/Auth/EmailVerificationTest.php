<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

beforeEach(fn () => Route::middleware(['web', 'auth', 'verified'])->get('/_verified-only', fn () => 'ok'));

it('holds unverified users on the notice page', function () {
    $user = User::factory()->unverified()->create(['email' => 'budi@contoh.id']);

    $this->actingAs($user)->get('/_verified-only')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSeeText('Verifikasi email Anda')
        ->assertSeeText('budi@contoh.id')
        ->assertSeeText('Kirim ulang email verifikasi')
        ->assertSee('href="'.url('/account').'"', false);
});

it('resends the verification email with a toast', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmailNotification::class);

    $this->actingAs($user)->withSession(['status' => 'verification-link-sent'])->get(route('verification.notice'))
        ->assertSee('Tautan verifikasi baru sudah dikirim ke email Anda.', false);
});

it('verifies through the signed link and returns to the intended page', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get('/_verified-only');

    $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($link)->assertRedirect(url('/_verified-only'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('sends the verification email when someone registers', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Budi Santoso', 'phone' => '08123456789', 'email' => 'budi@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ]);

    Notification::assertSentTo(User::firstWhere('email', 'budi@contoh.id'), VerifyEmailNotification::class);
});

it('sends an Indonesian, queued verification email from the site', function () {
    $user = User::factory()->unverified()->create(['name' => 'Budi Santoso']);
    $notification = new VerifyEmailNotification;
    $mail = $notification->toMail($user);

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($mail->subject)->toBe('Verifikasi email Anda')
        ->and($mail->actionText)->toBe('Verifikasi email')
        ->and($mail->greeting)->toBe('Halo, Budi Santoso')
        ->and($mail->from[1])->toBe('BlueCore Course')
        ->and($mail->replyTo[0][0])->toBe('support@bluecore.co.id')
        ->and($mail->actionUrl)->toContain('/email/verify/'.$user->id.'/');
});

it('lets verified users skip the notice', function () {
    $this->actingAs(User::factory()->create())->get(route('verification.notice'))->assertRedirect();
});

it('sends an expired verification link back to the notice with a hint', function () {
    $user = User::factory()->unverified()->create();
    $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($expired)
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('error', 'Tautan verifikasi sudah kedaluwarsa. Kirim ulang email verifikasi.');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
