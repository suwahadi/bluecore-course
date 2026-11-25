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

        expect($html)->toContain('<html')->toContain('Halo,');
    }
});
