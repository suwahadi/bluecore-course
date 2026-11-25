<?php

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\SegmentProgress;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Settings::set(['lead_magnet_youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    $this->user = User::factory()->create();
    $this->owned = Course::factory()->published()->create(['title' => 'Lele Galon', 'slug' => 'lele-galon']);
    $this->other = Course::factory()->published()->create(['title' => 'Cupang Hias']);
    $segments = collect([1, 2])->map(fn (int $n) => CourseSegment::factory()->for($this->owned)->create(['sort_order' => $n]));
    CourseSegment::factory()->for($this->other)->create();
    SegmentProgress::factory()->create(['user_id' => $this->user->id, 'course_segment_id' => $segments[0]->id, 'percent' => 100, 'completed_at' => now()]);
    Enrollment::factory()->for($this->user)->for($this->owned)->create();
});

function thinBars(string $html): array
{
    preg_match_all('/<div class="seg-bar seg-bar--thin" aria-hidden="true">(.*?)<\/div>/s', $html, $matches);

    return array_map(fn (string $bar) => preg_match_all('/data-done="true"/', $bar).'/'.preg_match_all('/<span /', $bar), $matches[1]);
}

it('shows a progress bar on owned cards only', function (string $url) {
    $html = $this->actingAs($this->user)->get($url)->assertOk()->getContent();

    expect(thinBars($html))->toBe(['1/2'])
        ->and($html)->toContain('Dimiliki');
})->with(['home' => '/', 'catalog' => '/courses', 'free video' => '/free-video']);

it('adds at most two queries for the owner and none for guests', function () {
    $stranger = User::factory()->create();
    $this->actingAs($stranger)->get('/');

    DB::enableQueryLog();
    $this->get('/');
    $withoutOwned = count(DB::getQueryLog());

    $this->actingAs($this->user)->get('/');
    DB::flushQueryLog();
    $this->get('/');
    $withOwned = count(DB::getQueryLog());

    expect($withOwned - $withoutOwned)->toBeLessThanOrEqual(2);

    auth()->logout();
    $this->get('/');
    DB::flushQueryLog();
    $html = $this->get('/')->getContent();

    expect(thinBars($html))->toBe([])
        ->and(collect(DB::getQueryLog())->pluck('query')->filter(fn ($sql) => str_contains($sql, 'segment_progress')))->toBeEmpty();
});

it('shows progress in the buy box for learners', function () {
    $this->actingAs($this->user)->get('/courses/lele-galon')
        ->assertSeeInOrder(['Lanjutkan belajar', 'Progres', '1 dari 2 video'])
        ->assertSee('href="'.route('learn.index', $this->owned).'"', false);

    $html = $this->actingAs(User::factory()->create())->get('/courses/lele-galon')->getContent();
    expect($html)->not->toContain('1 dari 2 video')->and(thinBars($html))->toBe([]);
});

it('links every learn button through the route', function () {
    $files = collect(['app', 'resources/views'])
        ->flatMap(fn (string $dir) => File::allFiles(base_path($dir)))
        ->filter(fn ($file) => str_contains(File::get($file->getPathname()), "url('/learn/"))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($files)->toBe([]);
});
