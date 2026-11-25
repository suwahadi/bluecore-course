<?php

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\SegmentProgress;
use App\Models\User;

beforeEach(function () {
    $this->buyer = User::factory()->create();
    $this->course = Course::factory()->published()->create(['slug' => 'lele-galon']);
    $this->segments = collect([1, 2])->map(fn (int $n) => CourseSegment::factory()->for($this->course)->create(['sort_order' => $n]));
    Enrollment::factory()->for($this->buyer)->for($this->course)->create();
});

function completeUrl(CourseSegment $segment): string
{
    return '/learn/segments/'.$segment->id.'/complete';
}

function positionUrl(CourseSegment $segment): string
{
    return '/learn/segments/'.$segment->id.'/position';
}

it('marks a video done and returns the next video url', function () {
    $this->actingAs($this->buyer)->postJson(completeUrl($this->segments[0]))
        ->assertOk()
        ->assertExactJson(['next_url' => route('learn.show', [$this->course, $this->segments[1]])]);
    $this->postJson(completeUrl($this->segments[0]))->assertOk();
    $this->postJson(completeUrl($this->segments[1]))->assertOk()->assertExactJson(['next_url' => null]);

    expect(SegmentProgress::query()->where('course_segment_id', $this->segments[0]->id)->count())->toBe(1)
        ->and(SegmentProgress::query()->whereNotNull('completed_at')->count())->toBe(2);
});

it('redirects a form submit to the next video, or back to the last one', function () {
    $this->actingAs($this->buyer)->post(completeUrl($this->segments[0]))
        ->assertRedirect(route('learn.show', [$this->course, $this->segments[1]]));
    $this->post(completeUrl($this->segments[1]))
        ->assertRedirect(route('learn.show', [$this->course, $this->segments[1]]));
});

it('refuses progress on a course the user does not own', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->postJson(completeUrl($this->segments[0]))->assertForbidden();
    $this->actingAs($stranger)->postJson(positionUrl($this->segments[0]), ['position' => 10, 'duration' => 100])->assertForbidden();
    $this->actingAs($stranger)->postJson(positionUrl($this->segments[0]), ['position' => 'x'])->assertForbidden();

    expect(SegmentProgress::query()->count())->toBe(0);
});

it('asks guests to sign up before saving progress', function () {
    $this->postJson(completeUrl($this->segments[0]))->assertUnauthorized();
    $this->postJson(positionUrl($this->segments[0]), ['position' => 10, 'duration' => 100])->assertUnauthorized();
});

it('returns 404 for a video that does not exist', function () {
    $this->actingAs($this->buyer)->postJson('/learn/segments/999999/complete')->assertNotFound();
});

it('records the position and answers 204', function () {
    $this->actingAs($this->buyer)->postJson(positionUrl($this->segments[0]), ['position' => 250, 'duration' => 1000])->assertNoContent();

    expect(SegmentProgress::query()->sole())->last_position_seconds->toBe(250)->percent->toBe(25);
});

it('accepts a beacon sent as a form', function () {
    $this->actingAs($this->buyer)->post(positionUrl($this->segments[0]), ['position' => '120', 'duration' => '600'])->assertNoContent();

    expect(SegmentProgress::query()->sole()->last_position_seconds)->toBe(120);
});

it('rejects odd position payloads', function (array $payload) {
    $this->actingAs($this->buyer)->postJson(positionUrl($this->segments[0]), $payload)->assertUnprocessable();

    expect(SegmentProgress::query()->count())->toBe(0);
})->with([
    'negative position' => [['position' => -1, 'duration' => 100]],
    'too long' => [['position' => 86401, 'duration' => 86401]],
    'decimal' => [['position' => '12.5', 'duration' => 100]],
    'text' => [['position' => 'abc', 'duration' => 100]],
    'array' => [['position' => ['1'], 'duration' => 100]],
    'zero duration' => [['position' => 0, 'duration' => 0]],
    'missing duration' => [['position' => 10]],
    'missing position' => [['duration' => 100]],
]);

it('clips a position past the duration', function () {
    $this->actingAs($this->buyer)->postJson(positionUrl($this->segments[0]), ['position' => 900, 'duration' => 600])->assertNoContent();

    expect(SegmentProgress::query()->sole())->last_position_seconds->toBe(600)->percent->toBe(100);
});

it('throttles position reports at 30 per minute', function () {
    $this->actingAs($this->buyer);

    foreach (range(1, 30) as $second) {
        $this->postJson(positionUrl($this->segments[0]), ['position' => $second, 'duration' => 100])->assertNoContent();
    }

    $this->postJson(positionUrl($this->segments[0]), ['position' => 31, 'duration' => 100])->assertTooManyRequests();
});

it('throttles completions at 60 per minute', function () {
    $this->actingAs($this->buyer);

    foreach (range(1, 60) as $attempt) {
        $this->postJson(completeUrl($this->segments[0]))->assertOk();
    }

    $this->postJson(completeUrl($this->segments[0]))->assertTooManyRequests();
});
