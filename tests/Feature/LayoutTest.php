<?php

use App\Models\Page;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    View::addLocation(base_path('tests/fixtures/views'));
    Route::middleware('web')->get('/_layout-probe', fn () => view('layout-probe'));
    Route::middleware('web')->get('/_noindex-probe', fn () => view('layout-probe', ['noindex' => true]));
});

it('shows guest links and the brand', function () {
    $this->get('/_layout-probe')
        ->assertOk()
        ->assertSeeText('BlueCore Course')
        ->assertSeeText('Budidaya ikan dalam galon, langkah demi langkah.')
        ->assertSeeText('Masuk')
        ->assertSeeText('Daftar')
        ->assertDontSeeText('Keluar')
        ->assertSee(asset('images/logo.webp'), false);
});

it('shows the account menu for members without the admin link', function () {
    $this->actingAs(User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@contoh.id']))
        ->get('/_layout-probe')
        ->assertOk()
        ->assertSeeText('Budi Santoso')
        ->assertSeeText('budi@contoh.id')
        ->assertSeeText('Keluar')
        ->assertSee('href="'.url('/my-courses').'"', false)
        ->assertDontSee('href="'.url('/admin').'"', false);
});

it('shows the admin link to admins', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/_layout-probe')
        ->assertSee('href="'.url('/admin').'"', false);
});

it('lists only published footer pages in order', function () {
    Page::factory()->create(['title' => 'Kebijakan Privasi', 'sort_order' => 2]);
    Page::factory()->create(['title' => 'Syarat dan Ketentuan', 'sort_order' => 1]);
    Page::factory()->create(['title' => 'Draf Rahasia', 'is_published' => false]);
    Page::factory()->create(['title' => 'Tidak di Footer', 'show_in_footer' => false]);

    $this->get('/_layout-probe')
        ->assertOk()
        ->assertSeeTextInOrder(['Semua kursus', 'Syarat dan Ketentuan', 'Kebijakan Privasi', 'Kontak'])
        ->assertDontSeeText('Draf Rahasia')
        ->assertDontSeeText('Tidak di Footer');
});

it('refreshes footer pages after a page changes', function () {
    $page = Page::factory()->create(['title' => 'Tentang Kami']);
    $this->get('/_layout-probe')->assertSeeText('Tentang Kami');

    $page->update(['title' => 'Profil BlueCore']);

    $this->get('/_layout-probe')->assertSeeText('Profil BlueCore')->assertDontSeeText('Tentang Kami');
});

it('hides the marketplace column when no store links are set', function () {
    $this->get('/_layout-probe')->assertOk()->assertDontSeeText('Marketplace');
});

it('shows only the marketplace stores that have links', function () {
    Settings::set(['shopee_url' => 'https://shopee.co.id/bluecore']);

    $this->get('/_layout-probe')
        ->assertOk()
        ->assertSeeText('Marketplace')
        ->assertSee('https://shopee.co.id/bluecore', false)
        ->assertDontSee('tokopedia.webp', false);
});

it('links contact details from settings', function () {
    $this->get('/_layout-probe')
        ->assertOk()
        ->assertSee('https://wa.me/6281818182878?text='.rawurlencode('Halo BlueCore Course, saya ingin bertanya.'), false)
        ->assertSee('mailto:support@bluecore.co.id', false)
        ->assertSeeText('@bluecore.official');
});

it('builds the page title and meta tags', function () {
    $this->get('/_layout-probe')
        ->assertOk()
        ->assertSee('<title>Uji: BlueCore Course</title>', false)
        ->assertSee('<meta name="description" content="Deskripsi halaman uji">', false)
        ->assertSee('<meta property="og:title" content="Uji: BlueCore Course">', false)
        ->assertSee('<link rel="canonical" href="'.url('/_layout-probe').'">', false)
        ->assertDontSee('noindex', false);
});

it('adds noindex when asked', function () {
    $this->get('/_noindex-probe')->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
});

it('starts the page with a skip link to the main content', function () {
    $html = $this->get('/_layout-probe')->assertOk()->getContent();

    expect($html)->toMatch('/<body>\s*<a class="skip-link" href="#main">Langsung ke konten<\/a>/');
});

it('injects toasts after load so screen readers announce them', function () {
    $html = $this->withSession(['status' => 'Data berhasil disimpan.'])->get('/_layout-probe')->getContent();

    expect($html)->toContain('Data berhasil disimpan.')
        ->and($html)->not->toContain('<span>Data berhasil disimpan.</span>')
        ->and($html)->toContain('x-text="message.text"');
});

it('returns focus to the account button when its menu closes with Escape', function () {
    $this->actingAs(User::factory()->create())->get('/_layout-probe')->assertSee('x-ref="toggle"', false)->assertSee('$refs.toggle.focus()', false);
});

it('shows the WhatsApp number in the WhatsApp row, not the phone number', function () {
    Settings::set(['whatsapp' => '6281211112222', 'phone' => '0215550000']);

    $this->get('/')->assertSee('href="https://wa.me/6281211112222?text='.rawurlencode('Halo BlueCore Course, saya ingin bertanya.').'"', false)->assertSeeText('081211112222')->assertDontSeeText('0215550000');
});

it('shows social handles without query strings or fragments', function () {
    Settings::set(['instagram_url' => 'https://www.instagram.com/bluecore.official/?igsh=abc123#bio']);

    $this->get('/')->assertSeeText('@bluecore.official')->assertDontSeeText('igsh');
});

it('keeps the footer headings in order after the page heading', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('<h2 class="site-footer__title">Tautan</h2>')->not->toContain('<h4');
});

it('lists social accounts in their own footer column after the contact column', function () {
    Settings::set([
        'instagram_url' => 'https://instagram.com/bluecore.official',
        'youtube_url' => 'https://youtube.com/@BlueCore.official',
        'tiktok_url' => 'https://tiktok.com/@bluecore.id',
    ]);

    $html = $this->get('/')->assertOk()->getContent();
    $contact = substr($html, strpos($html, 'Hubungi kami</h2>'), strpos($html, 'Sosial Media</h2>') - strpos($html, 'Hubungi kami</h2>'));

    expect($html)->toContain('<h2 class="site-footer__title">Sosial Media</h2>')
        ->and($contact)->toContain('wa.me')->not->toContain('instagram.com')
        ->and(substr($html, strpos($html, 'Sosial Media</h2>')))->toContain('instagram.com/bluecore.official')->toContain('@BlueCore.official')->toContain('@bluecore.id');
});

it('drops the social column when no social links are set', function () {
    Settings::set(['instagram_url' => null, 'youtube_url' => null, 'tiktok_url' => null]);

    $this->get('/')->assertOk()->assertDontSeeText('Sosial Media')->assertSeeText('Hubungi kami');
});

it('ships a real favicon and an apple touch icon', function () {
    $this->get('/_layout-probe')
        ->assertOk()
        ->assertSee('<link rel="icon" href="'.asset('favicon.ico').'" sizes="any">', false)
        ->assertSee('<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'">', false);

    expect(filesize(public_path('favicon.ico')))->toBeGreaterThan(0)
        ->and(getimagesize(public_path('apple-touch-icon.png'))[0])->toBe(180);
});
