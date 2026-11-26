<?php

use App\Support\Settings;

it('shows the contact details from the settings', function () {
    Settings::set(['address' => 'Bogor', 'phone' => '0251 123456', 'whatsapp' => '6281818182878', 'email' => 'support@bluecore.co.id']);

    $this->get('/contact')
        ->assertOk()
        ->assertSee('<h1>Kontak</h1>', false)
        ->assertSeeInOrder(['Bogor', '0251 123456', '081818182878', 'support@bluecore.co.id'])
        ->assertSee('href="tel:0251123456"', false)
        ->assertSee('href="https://wa.me/6281818182878"', false)
        ->assertSee('href="mailto:support@bluecore.co.id"', false)
        ->assertDontSee('<form', false);
});

it('leaves out empty rows', function () {
    Settings::set(['address' => 'Bogor', 'phone' => null, 'whatsapp' => null, 'email' => null]);

    $this->get('/contact')->assertSeeText('Bogor')->assertDontSeeText('Telepon')->assertDontSeeText('WhatsApp');
});

it('says when no contact details are set', function () {
    Settings::set(['address' => null, 'phone' => null, 'whatsapp' => null, 'email' => null]);

    $this->get('/contact')->assertSeeText('Informasi kontak belum diisi.');
});

it('shows each detail as an icon and its value, with the label only for screen readers', function () {
    Settings::set(['address' => 'Bogor', 'phone' => '0251 123456', 'whatsapp' => '6281818182878', 'email' => 'support@bluecore.co.id']);

    $html = $this->get('/contact')->assertOk()->getContent();

    expect(substr_count($html, 'class="contact-list__icon"'))->toBe(4)
        ->and($html)->toContain('<span class="sr-only">Alamat: </span>')
        ->toContain('<span class="sr-only">WhatsApp: </span>')
        ->not->toContain('<dt>')
        ->not->toContain('Chat lewat WhatsApp');
});
