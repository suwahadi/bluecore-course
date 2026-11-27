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
    $landing = App\Models\Page::factory()->create(['slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    Settings::set(['site_slogan' => 'Budidaya ikan dalam galon', 'site_description' => 'Kursus video berbahasa Indonesia.', 'free_video_page_id' => $landing->id]);

    $this->get('/')
        ->assertOk()
        ->assertSee('<h1>Budidaya ikan dalam galon</h1>', false)
        ->assertSeeText('Kursus video berbahasa Indonesia.')
        ->assertSee('href="'.url('/lele-pemula').'"', false)
        ->assertSeeText('Nonton gratis')
        ->assertSee('href="'.route('courses.show', $course).'"', false)
        ->assertSeeText('Lihat kursus');
});

it('hides Nonton gratis without a free video and sends Lihat kursus to the catalog without courses', function () {
    Settings::set(['free_video_page_id' => null]);

    $this->get('/')
        ->assertDontSeeText('Nonton gratis')
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

it('hides Nonton gratis when the chosen landing page is gone, a draft or without a video', function (array $page, bool $delete) {
    $landing = App\Models\Page::factory()->create(['slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE', ...$page]);
    App\Support\Settings::set(['free_video_page_id' => $landing->id]);
    if ($delete) {
        $landing->delete();
    }

    $this->get('/')->assertOk()->assertDontSeeText('Nonton gratis');
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
