<?php

use App\Models\User;
use App\Support\Settings;

it('loads no tracker when no ID is set', function () {
    $html = $this->get('/')->getContent();

    expect($html)->not->toContain('googletagmanager')->not->toContain('fbq(');
});

it('loads GA4 and Meta Pixel with a page view when the IDs are set', function () {
    Settings::set(['ga4_measurement_id' => 'G-ABC123', 'meta_pixel_id' => '1234567890']);

    $this->get('/')
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123', false)
        ->assertSee("gtag('config', 'G-ABC123')", false)
        ->assertSee("fbq('init', '1234567890')", false)
        ->assertSee("fbq('track', 'PageView')", false);
});

it('never loads trackers in the admin panel or for admins', function () {
    Settings::set(['ga4_measurement_id' => 'G-ABC123', 'meta_pixel_id' => '1234567890']);
    $admin = User::factory()->admin()->create();

    expect($this->actingAs($admin)->get('/admin')->getContent())->not->toContain('googletagmanager')->not->toContain('fbq(')
        ->and($this->actingAs($admin)->get('/')->getContent())->not->toContain('googletagmanager')->not->toContain('fbq(');
});

it('sends sign_up and CompleteRegistration once after registering, without personal data', function () {
    Settings::set(['ga4_measurement_id' => 'G-ABC123', 'meta_pixel_id' => '1234567890']);

    $this->post('/register', [
        'name' => 'Sari Lestari', 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/');

    $first = $this->get('/')->getContent();
    $second = $this->get('/')->getContent();

    expect($first)->toContain("gtag('event', 'sign_up')")
        ->toContain("fbq('track', 'CompleteRegistration')")
        ->and($second)->not->toContain('sign_up')->not->toContain('CompleteRegistration');

    preg_match_all('#<script[^>]*>(.*?)</script>#s', $first, $scripts);
    expect(implode("\n", $scripts[1]))->not->toContain('Sari Lestari')->not->toContain('sari@contoh.id')->not->toContain('08123456789');
});
