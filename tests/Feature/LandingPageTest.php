<?php

use App\Models\Course;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;

function landingPage(array $attributes = []): Page
{
    return Page::factory()->create([
        'title' => 'Lele untuk Pemula',
        'slug' => 'lele-pemula',
        'content' => "## Mulai\n\n- Galon bersih\n\n<b>tebal</b>",
        'youtube_url' => 'https://youtu.be/M7lc1UVf-VE',
        'show_in_footer' => false,
        ...$attributes,
    ]);
}

it('plays the video for signed-in users, verified or not', function () {
    landingPage();
    Course::factory()->published()->create(['title' => 'Lele Galon']);

    $this->actingAs(User::factory()->unverified()->create())->get('/lele-pemula')
        ->assertOk()
        ->assertSee('data-player data-video-id="M7lc1UVf-VE"', false)
        ->assertDontSee('<iframe', false)
        ->assertDontSee('data-position-url', false)
        ->assertSee('<h1>Lele untuk Pemula</h1>', false)
        ->assertSee('<h2>Mulai</h2>', false)
        ->assertSee('<li>Galon bersih</li>', false)
        ->assertDontSee('<b>tebal</b>', false)
        ->assertSeeText('Video lainnya ada di kursus lengkap')
        ->assertSeeText('Lele Galon')
        ->assertDontSee('lead-gate', false);
});

it('locks the page for guests without sending the video', function () {
    $this->seed(PageSeeder::class);
    landingPage();

    $html = $this->get('/lele-pemula')->assertOk()->getContent();

    expect($html)->toContain('class="lead-gate__page" inert aria-hidden="true"')
        ->toContain('Daftar dulu, lalu tonton gratis')
        ->toContain('Daftar dan tonton')
        ->toContain('Masuk dan tonton')
        ->toContain('name="next" value="/lele-pemula"')
        ->toContain('Dengan mendaftar, Anda setuju dengan')
        ->not->toContain('youtube-nocookie')
        ->not->toContain('M7lc1UVf-VE')
        ->not->toContain('data-player')
        ->not->toContain('data-video-id');
});

it('opens the video to guests when the page does not require signing up', function () {
    landingPage(['requires_signup' => false]);

    $this->get('/lele-pemula')
        ->assertOk()
        ->assertSee('data-player data-video-id="M7lc1UVf-VE"', false)
        ->assertDontSee('lead-gate', false);
});

it('hides a draft landing page from guests and flags it for admins', function () {
    landingPage(['is_published' => false]);

    $this->get('/lele-pemula')->assertNotFound();
    $this->actingAs(User::factory()->admin()->create())->get('/lele-pemula')
        ->assertOk()
        ->assertSeeText('Halaman ini masih draf. Hanya admin yang bisa melihatnya.')
        ->assertSee('data-video-id="M7lc1UVf-VE"', false);
});

it('keeps pages without a video in the plain layout', function () {
    Page::factory()->create(['slug' => 'tentang-kami', 'content' => 'Isi biasa.']);

    $this->get('/tentang-kami')
        ->assertOk()
        ->assertSee('class="panel panel--prose"', false)
        ->assertDontSee('lead-gate', false)
        ->assertDontSee('data-player', false);
});

it('brings new users back to the landing page with the video open', function () {
    landingPage();

    $this->post('/register', [
        'name' => 'Sari Lestari', 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'next' => '/lele-pemula', '_form' => 'register',
    ])->assertRedirect('/lele-pemula');

    $this->get('/lele-pemula')->assertSee('data-video-id="M7lc1UVf-VE"', false);
});

it('reopens the login tab with its error and still hides the video', function () {
    landingPage();
    User::factory()->create(['email' => 'budi@contoh.id']);

    $html = $this->from('/lele-pemula')->followingRedirects()->post('/login', [
        'email' => 'budi@contoh.id', 'password' => 'salah-sekali', 'next' => '/lele-pemula', '_form' => 'login',
    ])->getContent();

    expect($html)->toContain('id="lead-tab-login" aria-controls="lead-panel-login" aria-selected="true"')
        ->toContain('id="lead-login-email-error" role="alert">Email atau kata sandi salah.')
        ->not->toContain('id="lead-register-email-error"')
        ->not->toContain('M7lc1UVf-VE');
});

it('reopens the register tab with its errors', function () {
    landingPage();

    $html = $this->from('/lele-pemula')->followingRedirects()->post('/register', [
        'name' => '', 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'next' => '/lele-pemula', '_form' => 'register',
    ])->getContent();

    expect($html)->toContain('id="lead-tab-register" aria-controls="lead-panel-register" aria-selected="true"')
        ->toContain('id="lead-register-name-error" role="alert">Nama wajib diisi.')
        ->not->toContain('id="lead-login-email-error"');
});
