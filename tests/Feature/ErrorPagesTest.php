<?php

use Illuminate\Support\Facades\Route;

it('renders an Indonesian 404 page inside the site layout', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSeeText('Halaman tidak ditemukan')
        ->assertSeeText('Kembali ke beranda')
        ->assertSeeText('BlueCore Course');
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
