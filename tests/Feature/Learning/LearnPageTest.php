<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\SegmentProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->buyer = User::factory()->create();
    $this->course = Course::factory()->published()->create(['title' => 'Lele Galon', 'slug' => 'lele-galon']);
    $this->segments = collect([1, 2, 3])->map(fn (int $n) => CourseSegment::factory()->for($this->course)->create([
        'title' => 'Video '.$n,
        'sort_order' => $n,
        'youtube_video_id' => 'abcdefghij'.$n,
        'duration_seconds' => 1000,
    ]));
    $this->enrollment = Enrollment::factory()->for($this->buyer)->for($this->course)->create(['last_accessed_at' => null]);
});

function learnUrl(?CourseSegment $segment = null): string
{
    return '/learn/lele-galon'.($segment ? '/'.$segment->id : '');
}

function progressFor(CourseSegment $segment, array $attributes): SegmentProgress
{
    return SegmentProgress::factory()->create(['user_id' => test()->buyer->id, 'course_segment_id' => $segment->id, ...$attributes]);
}

it('asks guests to sign up and unverified users to verify', function () {
    $url = learnUrl($this->segments[0]);

    $this->get($url)->assertStatus(401)->assertSee('next='.urlencode($url), false);
    $this->get(learnUrl())->assertStatus(401);
    $this->actingAs(User::factory()->unverified()->create())->get($url)->assertRedirect(route('verification.notice'));
});

it('refuses users without access and lets admins in', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(learnUrl())->assertForbidden()->assertSeeText('Anda belum punya akses ke kursus ini.');
    $this->actingAs($stranger)->get(learnUrl($this->segments[0]))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())->get(learnUrl($this->segments[0]))->assertOk();
    expect(Enrollment::query()->count())->toBe(1);
});

it('keeps a buyer learning after the course goes back to draft', function () {
    $this->course->update(['status' => CourseStatus::Draft]);

    $this->actingAs($this->buyer)->get(learnUrl($this->segments[0]))->assertOk()->assertDontSee('href="'.route('courses.show', $this->course).'"', false);
});

it('returns 404 for a video of another course', function () {
    $foreign = CourseSegment::factory()->create();

    $this->actingAs($this->buyer)->get(learnUrl($foreign))->assertNotFound();
});

it('opens the first unfinished video, or the first one when all are done', function () {
    progressFor($this->segments[0], ['percent' => 100, 'completed_at' => now()]);

    $this->actingAs($this->buyer)->get(learnUrl())->assertRedirect(route('learn.show', [$this->course, $this->segments[1]]));

    progressFor($this->segments[1], ['percent' => 100, 'completed_at' => now()]);
    progressFor($this->segments[2], ['percent' => 100, 'completed_at' => now()]);

    $this->get(learnUrl())->assertRedirect(route('learn.show', [$this->course, $this->segments[0]]));
});

it('shows an empty state when the course has no videos', function () {
    $this->segments->each->delete();

    $this->actingAs($this->buyer)->get(learnUrl())
        ->assertOk()
        ->assertSeeText('Video kursus ini belum tersedia.')
        ->assertSee('href="'.url('/my-courses').'"', false);
});

it('renders the player, navigation, sidebar and materials', function () {
    progressFor($this->segments[0], ['percent' => 100, 'completed_at' => now(), 'last_position_seconds' => 3]);
    progressFor($this->segments[2], ['percent' => 40, 'last_position_seconds' => 2]);
    $material = CourseMaterial::factory()->for($this->course)->create(['title' => 'Panduan Pakan']);
    $second = $this->segments[1];

    $html = $this->actingAs($this->buyer)->get(learnUrl($second))->assertOk()->getContent();

    expect($html)
        ->toContain('<meta name="robots" content="noindex')
        ->toContain('data-player')
        ->toContain('data-video-id="abcdefghij2"')
        ->toContain('data-position-url="'.route('learn.position', $second).'"')
        ->toContain('data-complete-url="'.route('learn.complete', $second).'"')
        ->toContain('<h1>Video 2</h1>')
        ->toContain('href="'.route('learn.show', [$this->course, $this->segments[0]]).'">Sebelumnya</a>')
        ->toContain('href="'.route('learn.show', [$this->course, $this->segments[2]]).'">Berikutnya</a>')
        ->toContain('action="'.route('learn.complete', $second).'"')
        ->toContain('Tandai selesai dan lanjut')
        ->toContain('1 dari 3 video (33%)')
        ->toContain('aria-current="true"')
        ->toContain('style="width: 100%"')
        ->toContain('style="width: 40%"')
        ->toContain('Materi PDF')
        ->toContain('Panduan Pakan')
        ->toContain('href="'.route('materials.download', [$this->course, $material]).'"')
        ->not->toContain('Kursus selesai.');
    expect(substr_count($html, 'data-done="true"'))->toBeGreaterThanOrEqual(2);
});

it('disables Sebelumnya on the first video and ends with Tandai selesai on the last', function () {
    $this->actingAs($this->buyer)->get(learnUrl($this->segments[0]))
        ->assertSee('<span class="btn" aria-disabled="true">Sebelumnya</span>', false);

    $this->get(learnUrl($this->segments[2]))
        ->assertDontSee('>Berikutnya</a>', false)
        ->assertDontSeeText('Tandai selesai dan lanjut')
        ->assertSeeText('Tandai selesai');
});

it('shows the finished state when every video is done', function () {
    $this->segments->each(fn ($segment) => progressFor($segment, ['percent' => 100, 'completed_at' => now()]));

    $this->actingAs($this->buyer)->get(learnUrl($this->segments[0]))
        ->assertSeeText('Kursus selesai. Semua video sudah ditandai selesai. Anda tetap bisa memutar ulang kapan saja.')
        ->assertSeeText('3 dari 3 video (100%)');
});

it('offers to resume from the last position', function (int $position, ?int $duration, ?string $note, int $start) {
    $this->segments[0]->update(['duration_seconds' => $duration]);
    progressFor($this->segments[0], ['last_position_seconds' => $position, 'percent' => 10]);

    $response = $this->actingAs($this->buyer)->get(learnUrl($this->segments[0]))->assertSee('data-start="'.$start.'"', false);

    $note === null
        ? $response->assertDontSeeText('Lanjut dari')->assertDontSeeText('Putar dari awal')
        : $response->assertSeeText($note)->assertSee('data-restart', false)->assertSeeText('Putar dari awal');
})->with([
    'middle of the video' => [754, 1000, 'Lanjut dari 12:34', 754],
    'first seconds' => [5, 1000, null, 0],
    'last seconds' => [995, 1000, null, 0],
    'unknown duration' => [3723, null, 'Lanjut dari 1:02:03', 3723],
]);

it('touches last access on a visit, at most every five minutes', function () {
    $this->actingAs($this->buyer)->get(learnUrl($this->segments[0]));
    $first = $this->enrollment->fresh()->last_accessed_at;
    expect($first)->not->toBeNull();

    $this->travel(3)->minutes();
    $this->get(learnUrl($this->segments[1]));

    expect($this->enrollment->fresh()->last_accessed_at->equalTo($first))->toBeTrue();
});

it('loads the page in the same number of queries for 3 or 12 videos', function () {
    $this->actingAs($this->buyer)->get(learnUrl($this->segments[0]));

    DB::enableQueryLog();
    $this->get(learnUrl($this->segments[1]));
    $few = count(DB::getQueryLog());

    collect(range(4, 12))->each(fn (int $n) => CourseSegment::factory()->for($this->course)->create(['sort_order' => $n]));
    CourseMaterial::factory()->count(3)->for($this->course)->create();
    DB::flushQueryLog();
    $this->get(learnUrl($this->segments[1]));

    expect(count(DB::getQueryLog()))->toBe($few);
});
