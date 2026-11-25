<?php

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->course = Course::factory()->published()->create(['slug' => 'tanpa-durasi']);
    $this->segments = collect([1, 2])->map(fn (int $n) => CourseSegment::factory()->for($this->course)->create(['sort_order' => $n, 'duration_seconds' => null]));
    Enrollment::factory()->for($this->user)->for($this->course)->create();
});

it('never shows 0 menit for unknown durations', function (string $page) {
    $url = $page === 'learn' ? '/learn/tanpa-durasi/'.$this->segments[0]->id : $page;

    $this->actingAs($this->user)->get($url)->assertOk()->assertDontSeeText('0 menit');
})->with(['/', '/courses', '/courses/tanpa-durasi', 'learn']);

it('keeps showing durations that are known', function () {
    $this->segments[0]->update(['duration_seconds' => 300]);

    $this->actingAs($this->user)->get('/courses/tanpa-durasi')->assertSeeText('5 menit')->assertDontSeeText('0 menit');
    $this->get('/learn/tanpa-durasi/'.$this->segments[0]->id)->assertSeeText('Durasi 5 menit.');
});
