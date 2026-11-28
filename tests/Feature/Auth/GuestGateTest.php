<?php

use App\Models\Course;
use Illuminate\Support\Facades\Route;

it('shows the gate in place with status 401 and next links', function () {
    $this->get('/account')
        ->assertStatus(401)
        ->assertSeeText('Daftar dulu untuk melanjutkan')
        ->assertSeeText('Buat akun atau masuk dengan akun yang sudah ada. Setelah itu kamu langsung kembali ke halaman ini.')
        ->assertSee('href="'.url('/register').'?next=%2Faccount"', false)
        ->assertSee('href="'.url('/login').'?next=%2Faccount"', false)
        ->assertSee('class="btn-pair', false)
        ->assertSee('<meta name="robots" content="noindex">', false);
});

it('keeps the query string in next', function () {
    Route::middleware(['web', 'auth'])->get('/_gate', fn () => 'ok');

    $this->get('/_gate?from=2026-10-01')
        ->assertStatus(401)
        ->assertSee('href="'.url('/register').'?next=%2F_gate%3Ffrom%3D2026-10-01"', false);
});

it('sends guest POSTs to the login page', function () {
    Route::middleware(['web', 'auth'])->post('/_gate', fn () => 'ok');

    $this->post('/_gate')->assertRedirect('/login');
});

it('answers JSON requests with a 401 JSON body', function () {
    Route::middleware(['web', 'auth'])->get('/_gate', fn () => 'ok');

    $this->getJson('/_gate')->assertStatus(401)->assertJsonStructure(['message']);
});

it('asks guests to sign up with next links on every private page', function (string $path) {
    Course::factory()->published()->create(['slug' => 'lele-galon']);

    $this->get($path)
        ->assertStatus(401)
        ->assertSee('href="'.url('/register').'?next='.urlencode($path).'"', false)
        ->assertSee('href="'.url('/login').'?next='.urlencode($path).'"', false);
})->with(['/courses/lele-galon/checkout', '/orders', '/learn/lele-galon', '/my-courses', '/account']);
