<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

function featuredCourse(array $attributes = []): Course
{
    $course = Course::factory()->published()->create(['title' => 'Lele Galon', 'price' => 149000, ...$attributes]);
    foreach (['Menyiapkan galon', 'Memilih benih', 'Pakan harian', 'Panen'] as $index => $title) {
        CourseSegment::factory()->for($course)->create(['title' => $title, 'sort_order' => $index + 1, 'duration_seconds' => 300]);
    }

    return $course;
}

it('puts the eyebrow, title, subtitle and the free video button in the hero', function () {
    featuredCourse();
    $landing = App\Models\Page::factory()->create(['slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    Settings::set([
        'hero_eyebrow' => 'Video gratis untuk pemula',
        'hero_title' => 'Keuntungan Maksimal Sejak Panen Pertama.',
        'hero_subtitle' => 'Hitung Perhitungan Lengkapnya di Video Gratis Ini.',
        'free_video_page_id' => $landing->id,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder([
            '<p class="hero__eyebrow">Video gratis untuk pemula</p>',
            '<h1>Keuntungan Maksimal Sejak Panen Pertama.</h1>',
            '<p class="hero__subtitle">Hitung Perhitungan Lengkapnya di Video Gratis Ini.</p>',
            '<a class="btn btn--primary" href="'.url('/lele-pemula').'">NONTON GRATIS</a>',
        ], false);
});

it('falls back to the site name when the hero title is empty', function () {
    Settings::set(['hero_title' => null, 'hero_eyebrow' => null, 'hero_subtitle' => null]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<h1>BlueCore Course</h1>')
        ->not->toContain('hero__eyebrow')
        ->not->toContain('hero__subtitle');
});

it('hides Tonton video gratis without a free video and sends Lihat kursus to the catalog without courses', function () {
    Settings::set(['free_video_page_id' => null]);

    $this->get('/')
        ->assertDontSee('>Tonton video gratis</a>', false)
        ->assertSee('class="btn btn--primary" href="'.url('/courses').'"', false)
        ->assertSeeText('Belum ada kursus yang terbit. Kursus baru muncul di sini begitu admin menerbitkannya.');
});

it('keeps the hero free of topic badges', function () {
    $lele = Category::factory()->create(['name' => 'Lele', 'slug' => 'lele']);
    Course::factory()->published()->for($lele)->create();

    $this->get('/')
        ->assertOk()
        ->assertDontSee('hero__topics', false)
        ->assertDontSeeText('Topik:')
        ->assertDontSee('href="'.url('/courses?cat=lele').'"', false);
});

it('shows the featured course card without a playable video', function () {
    $course = featuredCourse();

    $html = $this->get('/')->getContent();
    preg_match('#<div class="hero-player">.*?</ol>#s', $html, $card);

    expect($card[0] ?? '')->toContain('Lele Galon')
        ->toContain('Rp 149.000')
        ->toContain('1. Menyiapkan galon')
        ->toContain('Video 1 dari 4')
        ->toContain('2. Memilih benih')
        ->toContain('3. Pakan harian')
        ->not->toContain('4. Panen')
        ->and(substr_count($card[0], '<span data-current'))->toBe(4)
        ->and($html)->not->toContain('<iframe');
});

it('skips the featured card when the course has no videos and falls back from a draft', function () {
    $draft = featuredCourse(['status' => 'draft', 'published_at' => null, 'title' => 'Draf Rahasia']);
    Settings::set(['featured_course_id' => $draft->id]);
    Course::factory()->published()->create(['title' => 'Tanpa Video']);

    $this->get('/')->assertDontSee('class="hero-player"', false)->assertDontSeeText('Draf Rahasia');
});

it('shows up to eight latest courses and marks owned ones', function () {
    $user = User::factory()->create();
    $courses = Course::factory()->published()->count(9)->sequence(fn ($sequence) => ['title' => 'Kursus '.($sequence->index + 1), 'published_at' => now()->subDays(9 - $sequence->index)])->create();
    Enrollment::factory()->for($user)->for($courses[8])->create();

    $this->actingAs($user)->get('/')
        ->assertSeeText('Kursus terbaru')
        ->assertSee('href="'.url('/courses').'">Semua kursus</a>', false)
        ->assertSeeText('Kursus 9')
        ->assertDontSeeText('Kursus 1 ')
        ->assertSeeText('Dimiliki');
});

it('renders testimonials as a grid up to three and as a looping slider beyond', function () {
    Testimonial::factory()->create(['name' => 'Tersembunyi', 'is_visible' => false]);
    Testimonial::factory()->count(3)->sequence(fn ($sequence) => ['name' => 'Pembeli '.($sequence->index + 1), 'sort_order' => $sequence->index + 1])->create();

    $this->get('/')
        ->assertSeeText('Bukan Cuma Berani Mulai, Tapi Tahu Apa yang Harus Dilakukan.')
        ->assertSee('<ul class="testimonials">', false)
        ->assertDontSee('data-carousel', false)
        ->assertDontSeeText('Tersembunyi');

    Testimonial::factory()->create(['name' => 'Pembeli 4', 'sort_order' => 4]);

    $html = $this->get('/')->getContent();
    expect($html)->toContain('data-carousel')
        ->toContain('aria-label="Testimoni sebelumnya"')
        ->toContain('aria-label="Testimoni berikutnya"')
        ->and(substr_count($html, '<li aria-hidden="true" inert>'))->toBe(3)
        ->and(strpos($html, 'Pembeli 1'))->toBeLessThan(strpos($html, 'Pembeli 2'));
});

it('leaves the testimonial section out when none are visible', function () {
    Testimonial::factory()->create(['is_visible' => false]);

    $this->get('/')->assertDontSee('testimonials-title', false);
});

it('loads the home page with a fixed number of queries', function () {
    $user = User::factory()->create();
    foreach (range(1, 3) as $index) {
        featuredCourse(['title' => 'Kursus '.$index]);
    }
    Testimonial::factory()->count(4)->create();

    $this->actingAs($user)->get('/')->assertOk();
    DB::enableQueryLog();
    $this->get('/')->assertOk();
    $few = count(DB::getQueryLog());

    foreach (range(4, 8) as $index) {
        featuredCourse(['title' => 'Kursus '.$index]);
    }
    DB::flushQueryLog();
    $this->get('/')->assertOk();

    expect(count(DB::getQueryLog()))->toBe($few);
});

it('hides Tonton video gratis when the chosen landing page is gone, a draft or without a video', function (array $page, bool $delete) {
    $landing = App\Models\Page::factory()->create(['slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE', ...$page]);
    App\Support\Settings::set(['free_video_page_id' => $landing->id]);
    if ($delete) {
        $landing->delete();
    }

    $this->get('/')->assertOk()->assertDontSee('>Tonton video gratis</a>', false);
})->with([
    'deleted' => [[], true],
    'draft' => [['is_published' => false], false],
    'video emptied' => [['youtube_url' => null], false],
    'link no longer a video' => [['youtube_url' => 'https://contoh.id/bukan-video'], false],
]);

it('offers a pause button for the testimonial slider', function () {
    App\Models\Testimonial::factory()->count(4)->create();

    $this->get('/')->assertSee('data-carousel-toggle aria-pressed="false" aria-label="Jeda testimoni"', false);
});

it('has no pause button when the testimonials fit without a slider', function () {
    App\Models\Testimonial::factory()->count(3)->create();

    $this->get('/')->assertDontSee('data-carousel-toggle', false);
});

it('lists the FAQ page questions as an accordion', function () {
    App\Models\Page::factory()->create([
        'system_key' => 'faq', 'title' => 'FAQ', 'slug' => 'faq',
        'content' => "Pengantar singkat.\n\n### Cocok untuk pemula?\n\nYa, **cocok**.\n\n### Butuh lahan luas?\n\nTidak.\n\n- Teras cukup\n\n<script>alert(1)</script>",
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<section class="section" id="faq">')
        ->toContain('<h2>FAQ</h2>')
        ->toContain('<summary>Cocok untuk pemula?</summary>')
        ->toContain('<p>Ya, <strong>cocok</strong>.</p>')
        ->toContain('<summary>Butuh lahan luas?</summary>')
        ->toContain('<li>Teras cukup</li>')
        ->not->toContain('Pengantar singkat.')
        ->not->toContain('<script>alert(1)</script>');
});

it('skips the FAQ section when the page is a draft or has no questions', function (array $attributes) {
    App\Models\Page::factory()->create(['system_key' => 'faq', 'slug' => 'faq', ...$attributes]);

    $this->get('/')->assertOk()->assertDontSee('id="faq"', false);
})->with([
    'draft' => [['is_published' => false, 'content' => "### Tanya?\n\nJawab."]],
    'no questions' => [['content' => 'Belum ada pertanyaan.']],
]);

it('closes with the free video for guests and the course for members', function () {
    $course = featuredCourse();
    $landing = App\Models\Page::factory()->create(['slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    Settings::set([
        'cta_title' => 'Galon Bekas di Rumah Sudah Siap? Tinggal Kamu yang Mulai.',
        'cta_text' => 'Tonton video gratisnya terlebih dahulu, atau langsung lihat kursus lengkap.',
        'free_video_page_id' => $landing->id,
    ]);

    $this->get('/')->assertOk()->assertSeeInOrder([
        '<h2>Galon Bekas di Rumah Sudah Siap? Tinggal Kamu yang Mulai.</h2>',
        '<p>Tonton video gratisnya terlebih dahulu, atau langsung lihat kursus lengkap.</p>',
        '<a class="btn btn--primary" href="'.url('/lele-pemula').'">Tonton video gratis</a>',
        '<p class="cta-band__note">Daftar gratis • Bayar sekali, akses selamanya</p>',
    ], false);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('<a class="btn btn--primary" href="'.route('courses.show', $course).'">Lihat kursus lengkap</a>', false)
        ->assertDontSee('cta-band__note', false);
});

it('drops the closing section when its title is empty', function () {
    Settings::set(['cta_title' => null]);

    $this->get('/')->assertOk()->assertDontSee('cta-band', false);
});

it('titles the testimonials from the settings', function () {
    Testimonial::factory()->create();
    Settings::set(['testimonials_title' => 'Bukan Cuma Berani Mulai, Tapi Tahu Apa yang Harus Dilakukan.']);

    $this->get('/')->assertOk()
        ->assertSee('<h2 id="testimonials-title">Bukan Cuma Berani Mulai, Tapi Tahu Apa yang Harus Dilakukan.</h2>', false);
});

it('follows the course list with the brief pitch and a link to the full course', function () {
    $course = featuredCourse();
    Settings::set(['home_courses_text' => "Di kursus lengkap, kamu akan **menjalankannya**.\n\n- Kenapa bibit sehat pun bisa stres?\n\n<script>x</script>"]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toMatch('#Kursus terbaru.*<div class="prose">\s*<p>Di kursus lengkap, kamu akan <strong>menjalankannya</strong>.</p>#s')
        ->toContain('<li>Kenapa bibit sehat pun bisa stres?</li>')
        ->not->toContain('<script>x</script>')
        ->toContain('<a class="btn btn--primary btn--block" href="'.route('courses.show', $course).'">Lihat kursus lengkap</a>');
});

it('drops the course list pitch when its text is empty or no course is published', function () {
    Settings::set(['home_courses_text' => 'Di kursus lengkap.']);
    $this->get('/')->assertOk()->assertDontSee('home-pitch', false);

    featuredCourse();
    Settings::set(['home_courses_text' => null]);
    $this->get('/')->assertOk()->assertDontSee('home-pitch', false);
});

it('writes the testimonial author and city on one line', function () {
    Testimonial::factory()->create(['name' => '[Nama pembeli]', 'note' => '[Kota]']);

    $this->get('/')->assertOk()->assertSee('<figcaption><strong>[Nama pembeli]</strong> <span>• [Kota]</span></figcaption>', false);
});

it('tags sample testimonials so visitors do not read them as real buyers', function () {
    Testimonial::factory()->create(['name' => 'Galih Pradipta', 'is_genuine' => false]);
    Testimonial::factory()->create(['name' => 'Bu Sari', 'is_genuine' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, '<span class="badge testimonial__tag">Contoh</span>'))->toBe(1)
        ->and($html)->toMatch('#<span class="badge testimonial__tag">Contoh</span>\s*<blockquote>[^<]*</blockquote>\s*<figcaption><strong>Galih Pradipta</strong>#');
});
