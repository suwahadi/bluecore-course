<?php

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Courses\CourseQuery;
use App\Support\Settings;

it('returns only published courses with their counts and durations', function () {
    $published = Course::factory()->published()->create();
    CourseSegment::factory()->for($published)->create(['duration_seconds' => 120]);
    CourseSegment::factory()->for($published)->create(['duration_seconds' => 180]);
    Course::factory()->create();

    $cards = app(CourseQuery::class)->cards()->get();

    expect($cards)->toHaveCount(1)
        ->and($cards->first()->segments_count)->toBe(2)
        ->and((int) $cards->first()->segments_sum_duration_seconds)->toBe(300)
        ->and($cards->first()->relationLoaded('category'))->toBeTrue();
});

it('lists the newest published courses first', function () {
    Course::factory()->published()->create(['title' => 'Lama', 'published_at' => now()->subDays(3)]);
    Course::factory()->published()->create(['title' => 'Baru', 'published_at' => now()->subDay()]);
    Course::factory()->published()->create(['title' => 'Tengah', 'published_at' => now()->subDays(2)]);

    expect(app(CourseQuery::class)->latest(2)->pluck('title')->all())->toBe(['Baru', 'Tengah']);
});

it('falls back to the newest published course when the featured one is missing or a draft', function () {
    $newest = Course::factory()->published()->create(['published_at' => now()]);
    $older = Course::factory()->published()->create(['published_at' => now()->subWeek()]);
    $draft = Course::factory()->create();
    $query = app(CourseQuery::class);

    expect($query->featured()->id)->toBe($newest->id);

    Settings::set(['featured_course_id' => $older->id]);
    expect($query->featured()->id)->toBe($older->id)
        ->and($query->featured()->relationLoaded('segments'))->toBeTrue();

    Settings::set(['featured_course_id' => $draft->id]);
    expect($query->featured()->id)->toBe($newest->id);
});

it('returns no featured course when nothing is published', function () {
    Course::factory()->create();

    expect(app(CourseQuery::class)->featured())->toBeNull();
});

it('plucks the course ids a user owns', function () {
    $user = User::factory()->create();
    $owned = Enrollment::factory()->for($user)->create();

    expect(app(CourseQuery::class)->ownedIds($user))->toBe([$owned->course_id])
        ->and(app(CourseQuery::class)->ownedIds(null))->toBe([]);
});

it('computes the discount percent from the compare price', function () {
    expect((new Course(['price' => 125000, 'compare_at_price' => 175000]))->discountPercent())->toBe(29)
        ->and((new Course(['price' => 125000, 'compare_at_price' => null]))->discountPercent())->toBe(0)
        ->and((new Course(['price' => 125000, 'compare_at_price' => 100000]))->discountPercent())->toBe(0);
});
