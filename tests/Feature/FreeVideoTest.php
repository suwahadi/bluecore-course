<?php

use App\Enums\SignupSource;
use App\Models\Course;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\PageSeeder;

beforeEach(function () {
    Settings::set([
        'lead_magnet_title' => 'Cara menyiapkan galon',
        'lead_magnet_description' => "Paragraf <b>satu</b>.\n\nParagraf dua.",
        'lead_magnet_youtube_url' => 'https://youtu.be/M7lc1UVf-VE',
    ]);
});

it('shows an empty state without a video link', function () {
    Settings::set(['lead_magnet_youtube_url' => null]);

    $this->get('/free-video')->assertOk()->assertSeeText('Video gratis belum tersedia.')->assertSee('href="'.route('courses.index').'"', false);
    $this->actingAs(User::factory()->create())->get('/free-video')->assertSeeText('Video gratis belum tersedia.');
});

it('plays the video for signed-in users, verified or not', function () {
    Course::factory()->published()->create(['title' => 'Lele Galon']);

    $this->actingAs(User::factory()->unverified()->create())->get('/free-video')
        ->assertOk()
        ->assertSee('src="https://www.youtube-nocookie.com/embed/M7lc1UVf-VE?rel=0&amp;modestbranding=1"', false)
        ->assertSee('<h1>Cara menyiapkan galon</h1>', false)
        ->assertSee('<p>Paragraf &lt;b&gt;satu&lt;/b&gt;.</p>', false)
        ->assertSee('<p>Paragraf dua.</p>', false)
        ->assertSeeText('Video lainnya ada di kursus lengkap')
        ->assertSeeText('Lele Galon')
        ->assertDontSee('lead-gate', false);
});

it('falls back to a default title', function () {
    Settings::set(['lead_magnet_title' => null]);

    $this->actingAs(User::factory()->create())->get('/free-video')->assertSee('<h1>Video gratis</h1>', false);
});

it('locks the page for guests without sending the video', function () {
    $this->seed(PageSeeder::class);

    $html = $this->get('/free-video')->assertOk()->getContent();

    expect($html)->toContain('class="lead-gate__page" inert aria-hidden="true"')
        ->toContain('role="tablist"')
        ->toContain('Daftar dan tonton')
        ->toContain('Masuk dan tonton')
        ->toContain('name="next" value="/free-video"')
        ->toContain('Dengan mendaftar, Anda setuju dengan')
        ->toContain('form-grid form-grid--2')
        ->not->toContain('youtube-nocookie')
        ->not->toContain('M7lc1UVf-VE')
        ->not->toContain('videoId');
});

it('brings new users back to the open video and records the source', function () {
    $this->post('/register', [
        'name' => 'Sari Lestari', 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'next' => '/free-video', '_form' => 'register',
    ])->assertRedirect('/free-video');

    expect(User::firstWhere('email', 'sari@contoh.id')->signup_source)->toBe(SignupSource::FreeVideo);
    $this->get('/free-video')->assertSee('youtube-nocookie.com/embed/M7lc1UVf-VE', false);
});

it('reopens the login tab with its error and still hides the video', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $html = $this->from('/free-video')->followingRedirects()->post('/login', [
        'email' => 'budi@contoh.id', 'password' => 'salah-sekali', 'next' => '/free-video', '_form' => 'login',
    ])->getContent();

    expect($html)->toContain('id="lead-tab-login" aria-controls="lead-panel-login" aria-selected="true"')
        ->toContain('id="lead-login-email-error" role="alert">Email atau kata sandi salah.')
        ->not->toContain('id="lead-register-email-error"')
        ->not->toContain('M7lc1UVf-VE');
});

it('reopens the register tab with its errors', function () {
    $html = $this->from('/free-video')->followingRedirects()->post('/register', [
        'name' => '', 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'next' => '/free-video', '_form' => 'register',
    ])->getContent();

    expect($html)->toContain('id="lead-tab-register" aria-controls="lead-panel-register" aria-selected="true"')
        ->toContain('id="lead-register-name-error" role="alert">Nama wajib diisi.')
        ->not->toContain('id="lead-login-email-error"');
});
