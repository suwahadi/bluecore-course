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

it('puts the slogan and description in the hero with the two buttons', function () {
    $course = featuredCourse();
    Settings::set(['site_slogan' => 'Budidaya ikan dalam galon', 'site_description' => 'Kursus video berbahasa Indonesia.', 'lead_magnet_youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);

    $this->get('/')
        ->assertOk()
        ->assertSee('<h1>Budidaya ikan dalam galon</h1>', false)
        ->assertSeeText('Kursus video berbahasa Indonesia.')
        ->assertSee('href="'.url('/free-video').'"', false)
        ->assertSeeText('Nonton gratis')
        ->assertSee('href="'.route('courses.show', $course).'"', false)
        ->assertSeeText('Lihat kursus');
});

it('hides Nonton gratis without a free video and sends Lihat kursus to the catalog without courses', function () {
    Settings::set(['lead_magnet_youtube_url' => null]);

    $this->get('/')
        ->assertDontSeeText('Nonton gratis')
        ->assertSee('class="btn btn--primary" href="'.url('/courses').'"', false)
        ->assertSeeText('Belum ada kursus yang terbit. Kursus baru muncul di sini begitu admin menerbitkannya.');
});

it('lists only active topics that have published courses', function () {
    $lele = Category::factory()->create(['name' => 'Lele', 'slug' => 'lele', 'sort_order' => 2]);
    $nila = Category::factory()->create(['name' => 'Nila', 'slug' => 'nila', 'sort_order' => 1]);
    $hias = Category::factory()->create(['name' => 'Ikan Hias', 'slug' => 'ikan-hias', 'is_active' => false]);
    $gurame = Category::factory()->create(['name' => 'Gurame', 'slug' => 'gurame']);
    Course::factory()->published()->for($lele)->create();
    Course::factory()->published()->for($nila)->create();
    Course::factory()->published()->for($hias)->create();
    Course::factory()->for($gurame)->create();

    $html = $this->get('/')->getContent();
    preg_match('#<nav class="hero__topics".*?</nav>#s', $html, $topics);

    expect($topics[0] ?? '')->toContain('Topik:')
        ->and(strpos($topics[0], 'Nila'))->toBeLessThan(strpos($topics[0], 'Lele'))
        ->and($topics[0])->toContain('href="'.url('/courses?cat=lele').'"')
        ->not->toContain('Ikan Hias')
        ->not->toContain('Gurame');
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
        ->assertSeeText('Testimoni pembeli')
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

    $this->get('/')->assertDontSeeText('Testimoni pembeli');
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

it('hides Nonton gratis when the saved free video link is not a YouTube video', function () {
    App\Support\Settings::set(['lead_magnet_youtube_url' => 'https://contoh.id/bukan-video']);

    $this->get('/')->assertOk()->assertDontSeeText('Nonton gratis');
});

it('offers a pause button for the testimonial slider', function () {
    App\Models\Testimonial::factory()->count(4)->create();

    $this->get('/')->assertSee('data-carousel-toggle aria-pressed="false" aria-label="Jeda testimoni"', false);
});

it('has no pause button when the testimonials fit without a slider', function () {
    App\Models\Testimonial::factory()->count(3)->create();

    $this->get('/')->assertDontSee('data-carousel-toggle', false);
});
