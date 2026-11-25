<?php

use App\Models\Course;
use App\Models\User;
use App\Notifications\AccessGrantedNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;

it('greets without the name in emails whose recipient may not have chosen it', function (string $class) {
    $user = User::factory()->unverified()->create(['name' => 'Klik https://jahat.test untuk verifikasi']);
    $notification = $class === ResetPasswordNotification::class ? new $class('token-contoh') : new $class;

    $mail = $notification->toMail($user);

    expect($mail->greeting)->toBe('Halo,')
        ->and((string) $mail->render())->not->toContain('jahat.test');
})->with([VerifyEmailNotification::class, ResetPasswordNotification::class]);

it('shows the name in other greetings as plain text, never as a link', function () {
    $user = User::factory()->create(['name' => '[Klik di sini](https://jahat.test) **segera**']);

    $html = (string) (new AccessGrantedNotification(Course::factory()->published()->create()))->toMail($user)->render();

    expect($html)->not->toContain('href="https://jahat.test"')
        ->not->toContain('<strong>segera</strong>')
        ->toContain('Klik di sini');
});
