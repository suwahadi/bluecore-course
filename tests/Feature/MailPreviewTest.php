<?php

use App\Http\Controllers\Dev\MailPreviewController;
use App\Models\User;

it('is not reachable outside the local environment', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/_dev/mail')->assertNotFound();
});

it('renders every email the app sends from its real notification', function () {
    $previews = MailPreviewController::previews();

    expect(array_keys($previews))->toBe(['verify', 'reset', 'order-created', 'payment-succeeded', 'order-expired', 'access-granted', 'admin-order-paid']);

    foreach ($previews as $preview) {
        $html = (string) $preview['message']()->render();

        expect($html)->toContain('<html')->toMatch('/Halo,( Sari Lestari| Admin BlueCore)?<\/h1>/');
    }
});

it('greets the verified sample people by name, except in verify and reset emails', function () {
    $previews = MailPreviewController::previews();

    expect((string) $previews['order-created']['message']()->render())->toContain('Halo, Sari Lestari')
        ->and((string) $previews['admin-order-paid']['message']()->render())->toContain('Halo, Admin BlueCore')
        ->and((string) $previews['verify']['message']()->render())->not->toContain('Halo, Sari');
});
