<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

it('renders an Indonesian 404 page inside the site layout', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSeeText('Halaman tidak ditemukan')
        ->assertSeeText('Kembali ke beranda')
        ->assertSeeText('Bluecore Course');
});

it('renders an Indonesian 403 page', function () {
    Route::middleware('web')->get('/_forbidden', fn () => abort(403));

    $this->get('/_forbidden')->assertForbidden()->assertSeeText('Akses ditolak');
});

it('renders an Indonesian 419 page', function () {
    Route::middleware('web')->get('/_expired', fn () => abort(419));

    $this->get('/_expired')->assertStatus(419)->assertSeeText('Sesi berakhir');
});

it('renders a standalone 500 page without touching the database', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/_boom', fn () => throw new RuntimeException('boom'));

    $this->get('/_boom')
        ->assertStatus(500)
        ->assertSeeText('Terjadi kesalahan')
        ->assertDontSeeText('boom');
});

it('renders an Indonesian 429 page with a way back', function () {
    foreach (range(1, 3) as $attempt) {
        $this->post('/forgot-password', ['email' => 'siapa@contoh.id']);
    }

    $this->post('/forgot-password', ['email' => 'siapa@contoh.id'])
        ->assertStatus(429)
        ->assertSeeText('Terlalu banyak permintaan')
        ->assertSeeText('Kembali ke beranda')
        ->assertSeeText('Bluecore Course');
});

it('renders a standalone Indonesian maintenance page', function () {
    Route::middleware('web')->get('/_maintenance', fn () => abort(503));
    DB::enableQueryLog();

    $html = $this->get('/_maintenance')->assertStatus(503)->getContent();

    expect($html)->toContain('Sedang pemeliharaan')
        ->toContain('<html lang="id">')
        ->not->toContain('/build/assets')
        ->and(DB::getQueryLog())->toBe([]);
});

it('frames the whole error message in the static page panel', function () {
    $html = $this->get('/halaman-yang-tidak-ada')->assertNotFound()->getContent();

    expect($html)->toMatch('#<div class="panel panel--prose error-panel">\s*<p class="error-panel__code">404</p>\s*<h1>Halaman tidak ditemukan</h1>\s*<p class="muted">[^<]+</p>\s*<div class="btn-row error-panel__actions">\s*<a class="btn btn--brand" href="[^"]+">Kembali ke beranda</a>\s*</div>\s*</div>#');
});
