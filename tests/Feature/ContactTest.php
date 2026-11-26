<?php

use App\Models\User;
use App\Support\Settings;

function contactSettings(array $overrides = []): void
{
    Settings::set(['address' => 'Bogor', 'phone' => '0251 123456', 'whatsapp' => '6281818182878', 'email' => 'support@bluecore.co.id', ...$overrides]);
}

it('shows the contact details from the settings', function () {
    contactSettings();

    $this->get('/contact')
        ->assertOk()
        ->assertSee('<h1>Kontak</h1>', false)
        ->assertSeeInOrder(['Bogor', '0251 123456', '081818182878', 'support@bluecore.co.id'])
        ->assertSee('href="tel:0251123456"', false)
        ->assertSee('href="https://wa.me/6281818182878?text='.rawurlencode('Halo BlueCore Course, saya ingin bertanya.').'"', false)
        ->assertSee('href="mailto:support@bluecore.co.id"', false);
});

it('leaves out empty rows', function () {
    contactSettings(['phone' => null, 'whatsapp' => null, 'email' => null]);

    $this->get('/contact')->assertSeeText('Bogor')->assertDontSeeText('Telepon')->assertDontSeeText('WhatsApp');
});

it('says when no contact details are set', function () {
    contactSettings(['address' => null, 'phone' => null, 'whatsapp' => null, 'email' => null]);

    $this->get('/contact')->assertSeeText('Informasi kontak belum diisi.');
});

it('shows each detail as an icon and its value, with the label only for screen readers', function () {
    contactSettings(['instagram_url' => null, 'youtube_url' => null, 'tiktok_url' => null]);

    $html = $this->get('/contact')->assertOk()->getContent();

    expect(substr_count($html, 'class="contact-list__icon"'))->toBe(4)
        ->and($html)->toContain('<span class="sr-only">Alamat: </span>')
        ->toContain('<span class="sr-only">WhatsApp: </span>')
        ->not->toContain('<dt>')
        ->not->toContain('Chat lewat WhatsApp');
});

it('offers a message form that goes to WhatsApp', function () {
    contactSettings();

    $this->get('/contact')
        ->assertOk()
        ->assertSee('action="'.route('contact.send').'"', false)
        ->assertSee('name="name"', false)
        ->assertSee('name="message"', false)
        ->assertSeeText('Kirim Sekarang')
        ->assertDontSeeText('Pesan Anda terbuka di WhatsApp');
});

it('fills in the name of a signed-in visitor', function () {
    contactSettings();

    $this->actingAs(User::factory()->create(['name' => 'Sari Lestari']))->get('/contact')->assertSee('value="Sari Lestari"', false);
});

it('opens WhatsApp with the greeting, the name and the message', function () {
    contactSettings();

    $this->post('/contact', ['name' => ' Budi ', 'message' => "Kapan kursus nila terbit?\nTerima kasih."])
        ->assertRedirect('https://wa.me/6281818182878?text='.rawurlencode("Halo BlueCore Course, saya Budi.\n\nKapan kursus nila terbit?\nTerima kasih."));
});

it('shows inline errors when the name or message is missing', function () {
    contactSettings();

    $this->from('/contact')->post('/contact', ['name' => '', 'message' => ''])
        ->assertRedirect('/contact')
        ->assertSessionHasErrors(['name' => 'Nama wajib diisi.', 'message' => 'Pesan wajib diisi.']);
});

it('refuses an overly long message', function () {
    contactSettings();

    $this->from('/contact')->post('/contact', ['name' => 'Budi', 'message' => str_repeat('a', 1001)])
        ->assertSessionHasErrors(['message' => 'Pesan maksimal 1000 karakter.']);
});

it('hides the form and refuses messages while no WhatsApp number is set', function () {
    contactSettings(['whatsapp' => null]);

    $this->get('/contact')->assertOk()->assertDontSeeText('Kirim Sekarang');
    $this->post('/contact', ['name' => 'Budi', 'message' => 'Halo'])->assertNotFound();
});

it('lists the social accounts in the contact card', function () {
    contactSettings(['instagram_url' => 'https://instagram.com/bluecore.official', 'youtube_url' => null, 'tiktok_url' => 'https://tiktok.com/@bluecore.id']);

    $html = $this->get('/contact')->assertOk()->getContent();
    $card = substr($html, strpos($html, 'class="contact-card"'), strpos($html, 'class="panel contact-form"') - strpos($html, 'class="contact-card"'));

    expect($card)->toContain('Sosial Media')
        ->toContain('href="https://instagram.com/bluecore.official"')
        ->toContain('@bluecore.id')
        ->not->toContain('youtube');
});
