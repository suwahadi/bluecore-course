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
        ->assertSee('https://wa.me/6281818182878', false)
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
