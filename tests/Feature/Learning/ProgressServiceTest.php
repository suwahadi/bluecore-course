<?php

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\SegmentProgress;
use App\Models\User;
use App\Services\Progress\ProgressService;
use App\Support\CourseProgress;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->service = app(ProgressService::class);
    $this->user = User::factory()->create();
    $this->segment = CourseSegment::factory()->create(['duration_seconds' => 1000]);
});

function progressRow(): SegmentProgress
{
    return SegmentProgress::query()->where(['user_id' => test()->user->id, 'course_segment_id' => test()->segment->id])->sole();
}

it('marks a video completed once, however often it is called', function () {
    $this->service->markCompleted($this->user, $this->segment);
    $this->service->markCompleted($this->user, $this->segment);

    expect(SegmentProgress::query()->count())->toBe(1)
        ->and(progressRow())->percent->toBe(100)
        ->and(progressRow()->completed_at)->not->toBeNull();
});

it('keeps the last position when a video is marked completed', function () {
    $this->service->recordPosition($this->user, $this->segment, 300, 1000);

    $this->service->markCompleted($this->user, $this->segment);

    expect(progressRow())->last_position_seconds->toBe(300)->percent->toBe(100);
});

it('records the latest position and only lets the percent rise', function () {
    $this->service->recordPosition($this->user, $this->segment, 300, 1000);
    expect(progressRow())->last_position_seconds->toBe(300)->percent->toBe(30);

    $this->service->recordPosition($this->user, $this->segment, 100, 1000);
    expect(progressRow())->last_position_seconds->toBe(100)->percent->toBe(30)
        ->and(progressRow()->completed_at)->toBeNull();
});

it('clips the position to the duration', function () {
    $this->service->recordPosition($this->user, $this->segment, 1500, 1000);

    expect(progressRow())->last_position_seconds->toBe(1000)->percent->toBe(100);
});

it('completes a video at 90 percent and never un-completes it', function () {
    $this->service->recordPosition($this->user, $this->segment, 899, 1000);
    expect(progressRow()->completed_at)->toBeNull();

    $this->service->recordPosition($this->user, $this->segment, 900, 1000);
    $completedAt = progressRow()->completed_at;
    expect($completedAt)->not->toBeNull();

    $this->travel(10)->minutes();
    $this->service->recordPosition($this->user, $this->segment, 10, 1000);

    expect(progressRow())->last_position_seconds->toBe(10)->percent->toBe(90)
        ->and(progressRow()->completed_at->equalTo($completedAt))->toBeTrue();
});

it('keys the progress of a course by video in one query', function () {
    $other = CourseSegment::factory()->create(['course_id' => $this->segment->course_id]);
    $this->service->recordPosition($this->user, $this->segment, 400, 1000);
    SegmentProgress::factory()->create(['course_segment_id' => $other->id]);

    DB::enableQueryLog();
    $progress = $this->service->forSegments($this->user, collect([$this->segment, $other]));

    expect(DB::getQueryLog())->toHaveCount(1)
        ->and($progress->keys()->all())->toBe([$this->segment->id])
        ->and($progress[$this->segment->id]->last_position_seconds)->toBe(400);
});

it('summarises progress per course in two queries', function () {
    $course = Course::factory()->create();
    $first = CourseSegment::factory()->for($course)->create(['sort_order' => 2]);
    $second = CourseSegment::factory()->for($course)->create(['sort_order' => 1]);
    $third = CourseSegment::factory()->for($course)->create(['sort_order' => 3]);
    $empty = Course::factory()->create();
    $this->service->markCompleted($this->user, $first);
    $this->service->recordPosition($this->user, $third, 100, 1000);
    $this->service->markCompleted(User::factory()->create(), $second);

    DB::enableQueryLog();
    $summaries = $this->service->summaries($this->user, [$course->id, $empty->id, $this->segment->course_id]);

    expect(DB::getQueryLog())->toHaveCount(2)
        ->and($summaries[$course->id]->cells)->toBe([false, true, false])
        ->and($summaries[$course->id]->done())->toBe(1)
        ->and($summaries[$course->id]->total())->toBe(3)
        ->and($summaries[$empty->id]->total())->toBe(0)
        ->and($summaries[$empty->id]->percent())->toBe(0)
        ->and($summaries[$empty->id]->isComplete())->toBeFalse()
        ->and($summaries[$this->segment->course_id]->cells)->toBe([false]);
});

it('runs no query for an empty list of courses', function () {
    DB::enableQueryLog();

    expect($this->service->summaries($this->user, []))->toBe([])
        ->and(DB::getQueryLog())->toHaveCount(0);
});

it('rounds the course percent down', function () {
    expect((new CourseProgress([true, true, false]))->percent())->toBe(66)
        ->and((new CourseProgress([true, true, false]))->isComplete())->toBeFalse()
        ->and((new CourseProgress([true, true, true]))->percent())->toBe(100)
        ->and((new CourseProgress([true, true, true]))->isComplete())->toBeTrue()
        ->and((new CourseProgress([]))->percent())->toBe(0);
});

it('builds course progress from loaded videos and keyed progress', function () {
    $course = Course::factory()->create();
    $segments = collect([
        CourseSegment::factory()->for($course)->create(['sort_order' => 1]),
        CourseSegment::factory()->for($course)->create(['sort_order' => 2]),
    ]);
    $this->service->markCompleted($this->user, $segments[1]);

    $progress = $this->service->fromSegments($segments, $this->service->forSegments($this->user, $segments));

    expect($progress->cells)->toBe([false, true]);
});

it('touches last access only when it is older than five minutes', function () {
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->for($this->user)->for($course)->create(['last_accessed_at' => null]);

    $this->service->touch($this->user, $course);
    $first = $enrollment->fresh()->last_accessed_at;
    expect($first)->not->toBeNull();

    $this->travel(4)->minutes();
    $this->service->touch($this->user, $course);
    expect($enrollment->fresh()->last_accessed_at->equalTo($first))->toBeTrue();

    $this->travel(2)->minutes();
    $this->service->touch($this->user, $course);
    expect($enrollment->fresh()->last_accessed_at->greaterThan($first))->toBeTrue();

    $this->service->touch(User::factory()->create(), $course);
    expect(Enrollment::query()->count())->toBe(1);
});

it('creates the progress row outside the locking transaction', function () {
    $queries = [];
    $outerLevel = DB::transactionLevel();
    DB::listen(function ($query) use (&$queries) {
        $queries[] = [strtolower(strtok($query->sql, ' ')), DB::transactionLevel()];
    });

    $this->service->recordPosition($this->user, $this->segment, 300, 1000);
    $this->service->recordPosition($this->user, $this->segment, 400, 1000);

    $insertsInsideLock = array_filter($queries, fn (array $query) => $query[0] === 'insert' && $query[1] > $outerLevel);

    expect($insertsInsideLock)->toBe([])
        ->and(progressRow())->last_position_seconds->toBe(400)->percent->toBe(40);
});

it('stamps a new progress row with one moment', function () {
    $this->service->recordPosition($this->user, $this->segment, 100, 1000);

    $row = progressRow();

    expect($row->created_at)->not->toBeNull()
        ->and($row->created_at->equalTo($row->updated_at))->toBeTrue();
});
