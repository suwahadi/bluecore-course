<?php

use App\Support\Settings;

it('shows the contact details from the settings', function () {
    Settings::set(['address' => 'Bogor', 'phone' => '0251 123456', 'whatsapp' => '6281818182878', 'email' => 'support@bluecore.co.id']);

    $this->get('/contact')
        ->assertOk()
        ->assertSee('<h1>Kontak</h1>', false)
        ->assertSeeInOrder(['Alamat', 'Bogor', 'Telepon', '0251 123456', 'WhatsApp', 'Chat lewat WhatsApp', 'Email', 'support@bluecore.co.id'])
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
