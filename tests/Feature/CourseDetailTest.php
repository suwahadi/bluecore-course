<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;

function detailCourse(array $attributes = []): Course
{
    $course = Course::factory()->published()->for(Category::factory()->create(['name' => 'Lele']))->create([
        'title' => 'Lele Galon',
        'slug' => 'lele-galon',
        'short_description' => 'Mulai dari satu galon.',
        'description' => "Paragraf <b>satu</b>.\n\nParagraf dua.",
        'price' => 125000,
        'compare_at_price' => 175000,
        ...$attributes,
    ]);
    CourseSegment::factory()->for($course)->create(['title' => 'Menyiapkan galon', 'duration_seconds' => 600, 'sort_order' => 1]);
    CourseSegment::factory()->for($course)->create(['title' => 'Memilih benih', 'duration_seconds' => 900, 'sort_order' => 2]);
    CourseMaterial::factory()->for($course)->create(['title' => 'Panduan galon', 'file_size' => 1_258_291]);

    return $course;
}

it('shows the course facts, videos and materials without a player', function () {
    $course = detailCourse();
    Enrollment::factory()->count(2)->for($course)->create();

    $response = $this->get('/courses/lele-galon')
        ->assertOk()
        ->assertSee('<h1>Lele Galon</h1>', false)
        ->assertSeeText('Lele')
        ->assertSeeText('Mulai dari satu galon.')
        ->assertSee('<p>Paragraf &lt;b&gt;satu&lt;/b&gt;.</p>', false)
        ->assertSee('<p>Paragraf dua.</p>', false)
        ->assertSeeText('Rp 125.000')
        ->assertSeeText('Rp 175.000')
        ->assertSeeText('Hemat 29%')
        ->assertSeeInOrder(['Menyiapkan galon', '10 menit', 'Memilih benih', '15 menit'])
        ->assertSeeInOrder(['Panduan galon', '1,2 MB'])
        ->assertSeeText('2 video')
        ->assertSeeText('25 menit')
        ->assertSeeText('1 materi PDF')
        ->assertSeeText('Akses selamanya')
        ->assertSeeText('Sudah diikuti 2 orang')
        ->assertSeeText('Bisa diunduh setelah punya akses.');

    expect($response->getContent())->not->toContain('<iframe')
        ->toContain('<meta name="description" content="Mulai dari satu galon.">');
});

it('hides the participant count when nobody has joined', function () {
    detailCourse();

    $this->get('/courses/lele-galon')->assertDontSeeText('Sudah diikuti');
});

it('asks guests to sign up with a checkout next path', function () {
    detailCourse();

    $this->get('/courses/lele-galon')
        ->assertSee('href="'.url('/register?next='.urlencode('/courses/lele-galon/checkout')).'"', false)
        ->assertSeeText('Daftar untuk membeli')
        ->assertSee('href="'.url('/login?next='.urlencode('/courses/lele-galon/checkout')).'"', false)
        ->assertSeeText('Sudah punya akun?');
});

it('offers checkout to signed-in users without access', function () {
    detailCourse();

    $this->actingAs(User::factory()->create())->get('/courses/lele-galon')
        ->assertSee('href="'.url('/courses/lele-galon/checkout').'"', false)
        ->assertSeeText('Beli sekarang');
});

it('sends buyers with an open order back to it', function () {
    $course = detailCourse();
    $user = User::factory()->create();
    Order::factory()->for($user)->for($course)->create(['status' => OrderStatus::Pending, 'expires_at' => now()->subHour()]);
    $open = Order::factory()->for($user)->for($course)->create(['status' => OrderStatus::Pending, 'expires_at' => now()->addHour()]);

    $this->actingAs($user)->get('/courses/lele-galon')
        ->assertSee('href="'.url('/order/'.$open->uuid).'"', false)
        ->assertSeeText('Selesaikan pembayaran')
        ->assertDontSeeText('Beli sekarang');
});

it('lets owners and admins continue learning', function () {
    $course = detailCourse();
    $owner = Enrollment::factory()->for($course)->create()->user;

    $this->actingAs($owner)->get('/courses/lele-galon')
        ->assertSee('href="'.url('/learn/lele-galon').'"', false)
        ->assertSeeText('Lanjutkan belajar')
        ->assertSeeText('Unduh dari halaman belajar.');

    $this->actingAs(User::factory()->admin()->create())->get('/courses/lele-galon')->assertSeeText('Lanjutkan belajar');
});

it('hides drafts from everyone but admins', function () {
    detailCourse(['status' => 'draft', 'published_at' => null]);

    $this->get('/courses/lele-galon')->assertNotFound();
    $this->actingAs(User::factory()->create())->get('/courses/lele-galon')->assertNotFound();

    $this->actingAs(User::factory()->admin()->create())->get('/courses/lele-galon')
        ->assertOk()
        ->assertSeeText('Kursus ini masih draf. Hanya admin yang bisa melihat halaman ini.')
        ->assertSee('<meta name="robots" content="noindex">', false);
});

it('answers 404 for an unknown slug', function () {
    $this->get('/courses/tidak-ada')->assertNotFound();
});

it('keeps buyers learning when the course goes back to draft', function () {
    $course = detailCourse(['status' => 'draft']);
    $owner = Enrollment::factory()->for($course)->create()->user;

    expect($owner->can('learn', $course))->toBeTrue()
        ->and(User::factory()->create()->can('learn', $course))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('learn', $course))->toBeTrue();
});

it('uses the thumbnail as the share image', function () {
    detailCourse(['thumbnail_path' => 'courses/sampul.webp']);

    $this->get('/courses/lele-galon')->assertSee('<meta property="og:image" content="'.Storage::disk('public')->url('courses/sampul.webp').'">', false);
});
