<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\SegmentProgress;
use App\Models\User;
use App\Support\Format;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function ownedCourse(string $title, ?Carbon\CarbonInterface $lastAccess, int $videos = 2, int $done = 0): Course
{
    $course = Course::factory()->published()->create(['title' => $title]);
    $segments = collect($videos > 0 ? range(1, $videos) : [])->map(fn (int $n) => CourseSegment::factory()->for($course)->create(['sort_order' => $n]));
    $segments->take($done)->each(fn (CourseSegment $segment) => SegmentProgress::factory()->create([
        'user_id' => test()->user->id,
        'course_segment_id' => $segment->id,
        'percent' => 100,
        'completed_at' => now(),
    ]));
    Enrollment::factory()->for(test()->user)->for($course)->create(['last_accessed_at' => $lastAccess]);

    return $course;
}

it('asks guests to sign up', function () {
    $this->get('/my-courses')->assertStatus(401)->assertSee('next='.urlencode('/my-courses'), false);
});

it('lists owned courses by last access, never opened last', function () {
    ownedCourse('Kursus Lama', now()->subDays(3));
    ownedCourse('Belum Dibuka', null);
    ownedCourse('Kursus Baru', now()->subHour());
    Course::factory()->published()->create(['title' => 'Milik Orang Lain']);

    $this->actingAs($this->user)->get('/my-courses')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex', false)
        ->assertSee('<h1>Kursus saya</h1>', false)
        ->assertSeeInOrder(['Kursus Baru', 'Kursus Lama', 'Belum Dibuka'])
        ->assertDontSeeText('Milik Orang Lain');
});

it('shows progress, last access and the right button', function () {
    $this->travelTo(now()->setDate(2026, 11, 26)->setTime(9, 22));
    $half = ownedCourse('Setengah Jalan', now(), 2, 1);
    $done = ownedCourse('Sudah Tamat', now()->subDay(), 2, 2);
    $fresh = ownedCourse('Belum Dibuka', null);

    $html = $this->actingAs($this->user)->get('/my-courses')->getContent();

    expect($html)
        ->toContain('1 dari 2 video')
        ->toContain('50%')
        ->toContain('Terakhir dibuka '.Format::datetime(now()))
        ->toContain('href="'.route('learn.index', $half).'">Lanjutkan belajar</a>')
        ->toContain('2 dari 2 video')
        ->toContain('<span class="badge badge--ok">Selesai</span>')
        ->toContain('href="'.route('learn.index', $done).'">Putar ulang</a>')
        ->toContain('Belum pernah dibuka')
        ->toContain('href="'.route('learn.index', $fresh).'">Lanjutkan belajar</a>');
    expect(substr_count($html, 'badge--ok'))->toBe(1);
});

it('shows an empty state with a link to the catalog', function () {
    $this->actingAs($this->user)->get('/my-courses')
        ->assertSeeText('Belum ada kursus. Kursus yang Anda beli muncul di sini.')
        ->assertSee('href="'.route('courses.index').'">Lihat kursus</a>', false);
});

it('lists a course that went back to draft and one without videos', function () {
    $draft = ownedCourse('Kursus Draf', now(), 2, 0);
    $draft->update(['status' => CourseStatus::Draft]);
    ownedCourse('Tanpa Video', now(), 0);

    $this->actingAs($this->user)->get('/my-courses')
        ->assertOk()
        ->assertSeeText('Kursus Draf')
        ->assertSeeText('Tanpa Video')
        ->assertSeeText('0 dari 0 video')
        ->assertSeeText('0%');
});

it('uses the same number of queries for 2 or 8 courses', function () {
    ownedCourse('Satu', now());
    ownedCourse('Dua', null, 3, 1);
    $this->actingAs($this->user)->get('/my-courses');

    DB::enableQueryLog();
    $this->get('/my-courses');
    $few = count(DB::getQueryLog());

    collect(range(3, 8))->each(fn (int $n) => ownedCourse('Kursus '.$n, now()->subMinutes($n), 3, 1));
    DB::flushQueryLog();
    $this->get('/my-courses');

    expect(count(DB::getQueryLog()))->toBe($few);
});
