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
    App\Models\CourseSegment::factory()->count(4)->for(Course::first())->create();

    $this->actingAs(User::factory()->unverified()->create())->get('/lele-pemula')
        ->assertOk()
        ->assertSee('data-player data-video-id="M7lc1UVf-VE"', false)
        ->assertDontSee('<iframe', false)
        ->assertDontSee('data-position-url', false)
        ->assertSee('<h1>Lele untuk Pemula</h1>', false)
        ->assertSee('<h2>Mulai</h2>', false)
        ->assertSee('<li>Galon bersih</li>', false)
        ->assertDontSee('<b>tebal</b>', false)
        ->assertSeeText('Lanjutan dari video gratis')
        ->assertSee('<h2>Lele Galon</h2>', false)
        ->assertSee('<a class="btn btn--primary btn--block" href="'.url('/courses/'.Course::first()->slug.'/checkout').'">BELI KURSUS SEKARANG</a>', false)
        ->assertSee('href="'.route('courses.show', Course::first()).'#daftar-video">Lihat isi 4 bab</a>', false)
        ->assertSeeText('Belajar sebelum beli bibit, bukan setelah ikan bermasalah.')
        ->assertDontSee('lead-gate', false);
});

it('locks the page for guests without sending the video', function () {
    $this->seed(PageSeeder::class);
    landingPage();

    $html = $this->get('/lele-pemula')->assertOk()->getContent();

    expect($html)->toContain('class="lead-gate__page" inert aria-hidden="true"')
        ->toContain('<h1 id="lead-gate-title">Satu Langkah Lagi, Lalu Langsung Tonton</h1>')
        ->toContain('Buat akun gratis untuk membuka videonya. Akunmu juga menyimpan video ini agar bisa ditonton ulang kapan saja.')
        ->toContain('Daftar &amp; tonton gratis')
        ->toContain('Gratis, tanpa kartu kredit. Email dipakai untuk akunmu dan informasi belajar dari Bluecore Course.')
        ->toContain('Masuk dan tonton')
        ->toContain('name="next" value="/lele-pemula"')
        ->toContain('Dengan mendaftar, kamu menyetujui')
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

it('frames the landing content in the same panel as the static pages', function () {
    landingPage(['requires_signup' => false]);

    $html = $this->get('/lele-pemula')->assertOk()->getContent();

    expect($html)->toMatch('#<h1>Lele untuk Pemula</h1>\s*<article class="panel panel--prose prose">\s*<h2>Mulai</h2>#');
});

it('sells the featured course under the video with the upsell copy', function () {
    landingPage(['requires_signup' => false, 'upsell_content' => "## Rencanamu Rp18.200 per Kilo\n\n- Kenapa bibit sehat bisa stres?\n\n<b>x</b>"]);
    $course = Course::factory()->published()->create(['title' => 'Lele Galon', 'price' => 149000]);
    App\Support\Settings::set(['featured_course_id' => $course->id]);

    $html = $this->get('/lele-pemula')->assertOk()->getContent();

    expect($html)->toContain('<h2>Rencanamu Rp18.200 per Kilo</h2>')
        ->toContain('<li>Kenapa bibit sehat bisa stres?</li>')
        ->not->toContain('<h2>Lele Galon</h2>')
        ->not->toContain('<b>x</b>')
        ->toContain('Rp 149.000 • Bayar sekali, akses selamanya');
});

it('lets owners continue the course instead of buying it again', function () {
    landingPage();
    $course = Course::factory()->published()->create(['title' => 'Lele Galon']);
    $owner = App\Models\Enrollment::factory()->for($course)->create()->user;

    $this->actingAs($owner)->get('/lele-pemula')
        ->assertSee('href="'.route('learn.index', $course).'">Lanjutkan belajar</a>', false)
        ->assertDontSeeText('Bayar sekali, akses selamanya');
});

it('skips the upsell when no course is published', function () {
    landingPage(['requires_signup' => false]);

    $this->get('/lele-pemula')->assertOk()->assertDontSeeText('Lanjutan dari video gratis');
});

it('sends guests on an open landing to sign up before the checkout', function () {
    landingPage(['requires_signup' => false]);
    $course = Course::factory()->published()->create(['slug' => 'lele-galon']);

    $this->get('/lele-pemula')
        ->assertSee('<a class="btn btn--primary btn--block" href="'.url('/register?next='.urlencode('/courses/lele-galon/checkout')).'">BELI KURSUS SEKARANG</a>', false);
});
