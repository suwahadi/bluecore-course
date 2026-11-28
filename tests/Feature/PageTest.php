<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;

it('shows a published page with its Markdown content', function () {
    Page::factory()->create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami', 'content' => "## Siapa kami\n\n- Bogor\n- Galon"]);

    $this->get('/tentang-kami')
        ->assertOk()
        ->assertSee('<h1>Tentang Kami</h1>', false)
        ->assertSee('<h2>Siapa kami</h2>', false)
        ->assertSee('<li>Bogor</li>', false)
        ->assertSee('<title>Tentang Kami: Bluecore Course</title>', false);
});

it('strips raw HTML and unsafe links from the content', function () {
    Page::factory()->create(['slug' => 'aman', 'content' => "<script>alert(1)</script>\n\n[klik](javascript:alert(2))\n\n<img src=x onerror=alert(3)>"]);

    $html = $this->get('/aman')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)')
        ->not->toContain('javascript:alert(2)')
        ->not->toContain('onerror=alert(3)');
});

it('says when a page has no content yet', function () {
    Page::factory()->create(['slug' => 'kosong', 'content' => null]);

    $this->get('/kosong')->assertSeeText('Konten belum diisi.');
});

it('hides draft pages from everyone but admins', function () {
    Page::factory()->create(['title' => 'Rahasia', 'slug' => 'rahasia', 'is_published' => false]);

    $this->get('/rahasia')->assertNotFound();
    $this->actingAs(User::factory()->create())->get('/rahasia')->assertNotFound();
    $this->actingAs(User::factory()->admin()->create())->get('/rahasia')
        ->assertOk()
        ->assertSeeText('Halaman ini masih draf. Hanya admin yang bisa melihatnya.')
        ->assertSee('<meta name="robots" content="noindex">', false);
});

it('serves the seeded system pages', function () {
    $this->seed(PageSeeder::class);

    $this->get('/terms-and-condition')->assertOk()->assertSeeText('Syarat & Ketentuan');
    $this->get('/privacy-policy')->assertOk()->assertSeeText('Kebijakan Privasi');
});

it('answers 404 for unknown slugs and slugs that are not slugs', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/tidak-ada', '/Tentang-Kami', '/tentang_kami']);

it('never lets a page shadow the admin panel', function () {
    Page::factory()->create(['title' => 'Bukan Admin', 'slug' => 'admin']);

    $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertOk()
        ->assertSeeText('Panel admin')
        ->assertSee('<h1 class="admin-title">Dasbor</h1>', false)
        ->assertDontSee('<h1>Bukan Admin</h1>', false);
});

it('keeps every first path segment of the app routes in the reserved list', function () {
    $segments = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => explode('/', trim($route->uri(), '/'))[0])
        ->reject(fn (string $segment) => $segment === '' || str_starts_with($segment, '{') || str_contains($segment, '.') || str_starts_with($segment, 'livewire') || str_starts_with($segment, '_'))
        ->unique()
        ->values();

    expect($segments->diff(Page::RESERVED_SLUGS)->all())->toBe([]);
});

it('rejects reserved slugs in the page route pattern', function () {
    $pattern = '#^'.Page::slugRoutePattern().'$#';

    expect(preg_match($pattern, 'tentang-kami'))->toBe(1)
        ->and(preg_match($pattern, 'admin'))->toBe(0)
        ->and(preg_match($pattern, 'courses'))->toBe(0)
        ->and(preg_match($pattern, 'livewire-e3677e29'))->toBe(0)
        ->and(preg_match($pattern, 'administrasi'))->toBe(1);
});

it('frames the page content in a panel below the title', function () {
    Page::factory()->create(['title' => 'Syarat & Ketentuan', 'slug' => 'terms-and-condition', 'content' => "## Akun\n\nIsi data dengan benar."]);

    $html = $this->get('/terms-and-condition')->assertOk()->getContent();

    expect($html)->toMatch('#<h1>Syarat &amp; Ketentuan</h1>\s*<article class="panel panel--prose">\s*<h2>Akun</h2>#');
});

it('shows the FAQ system page as an accordion under its intro', function () {
    Page::factory()->create(['system_key' => 'faq', 'title' => 'FAQ', 'slug' => 'faq', 'content' => "Belum terjawab? Hubungi kami.

### Tanya?

Jawab **singkat**."]);
    Page::factory()->create(['slug' => 'tentang-kami', 'content' => "### Visi

Isi biasa."]);

    $this->get('/faq')->assertOk()
        ->assertSeeInOrder(['<h1>FAQ</h1>', '<p>Belum terjawab? Hubungi kami.</p>', '<summary>Tanya?</summary>', '<p>Jawab <strong>singkat</strong>.</p>'], false)
        ->assertDontSee('panel--prose', false);
    $this->get('/tentang-kami')->assertOk()->assertSee('<h3>Visi</h3>', false)->assertDontSee('faq__item', false);
});

it('keeps the FAQ page as plain text until it has questions', function () {
    Page::factory()->create(['system_key' => 'faq', 'slug' => 'faq', 'content' => 'Belum ada pertanyaan.']);

    $this->get('/faq')->assertOk()
        ->assertSee('<article class="panel panel--prose">', false)
        ->assertSee('<p>Belum ada pertanyaan.</p>', false)
        ->assertDontSee('faq__item', false);
});
