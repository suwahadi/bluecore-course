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

it('greets without the name while the address is not verified yet', function () {
    $user = App\Models\User::factory()->unverified()->create(['name' => 'Akun diblokir, hubungi https://jahat.test']);
    $order = App\Models\Order::factory()->for($user)->create(['status' => App\Enums\OrderStatus::Expired]);

    $mail = (new App\Notifications\OrderExpiredNotification($order))->toMail($user);

    expect((string) $mail->greeting)->toBe('Halo,')
        ->and((string) $mail->render())->not->toContain('jahat.test');
});

it('keeps the plain text part free of escape backslashes', function () {
    $user = User::factory()->create(['name' => 'Budi (Pak) [x](y) Nur_aini \\ [z]']);
    $mail = (new AccessGrantedNotification(Course::factory()->published()->create()))->toMail($user);

    $text = (string) app(Illuminate\Mail\Markdown::class)->renderText($mail->markdown, $mail->data());
    $html = (string) $mail->render();

    expect($text)->toContain('Halo, Budi (Pak) [x](y) Nur_aini \\ [z]')
        ->and($html)->toContain('Budi (Pak) [x](y) Nur_aini \\ [z]')
        ->not->toContain('href="y"');
});
