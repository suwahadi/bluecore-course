<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSegment;
use App\Services\Courses\CourseQuery;

function cardCourse(): Course
{
    $course = Course::factory()->published()->for(Category::factory()->create(['name' => 'Lele']))->create([
        'title' => 'Lele Galon', 'price' => 125000, 'compare_at_price' => 175000,
    ]);
    CourseSegment::factory()->for($course)->create(['duration_seconds' => 1500]);
    CourseSegment::factory()->for($course)->create(['duration_seconds' => 2400]);

    return app(CourseQuery::class)->cards()->firstOrFail();
}

it('shows category, title, counts, price and discount to visitors', function () {
    $course = cardCourse();

    $html = (string) $this->blade('<x-course-card :course="$course" :owned="false" />', ['course' => $course]);

    expect($html)->toContain('href="'.route('courses.show', $course).'"')
        ->toContain('Lele')
        ->toContain('Lele Galon')
        ->toContain('2 video')
        ->toContain('1 jam 5 menit')
        ->toContain('Rp 125.000')
        ->toContain('Rp 175.000')
        ->toContain('−29%')
        ->not->toContain('Dimiliki');
});

it('shows Dimiliki instead of the price to owners', function () {
    $course = cardCourse();

    $html = (string) $this->blade('<x-course-card :course="$course" :owned="true" />', ['course' => $course]);

    expect($html)->toContain('Dimiliki')->not->toContain('−29%')->not->toContain('Rp 125.000');
});

it('renders the price block with a savings badge', function () {
    $course = new Course(['price' => 125000, 'compare_at_price' => 175000]);

    expect((string) $this->blade('<x-price :course="$course" />', ['course' => $course]))->toContain('Hemat 29%')
        ->and((string) $this->blade('<x-price :course="$course" :badge="false" />', ['course' => $course]))->not->toContain('Hemat');
});

it('lists course facts and hides participants when there are none', function () {
    $course = cardCourse();
    $course->setAttribute('materials_count', 2)->setAttribute('enrollments_count', 0);

    $html = (string) $this->blade('<x-course-meta :course="$course" :participants="true" />', ['course' => $course]);
    expect($html)->toContain('2 video')->toContain('2 materi PDF')->toContain('Akses selamanya')->not->toContain('Sudah diikuti');

    $course->setAttribute('enrollments_count', 120);
    expect((string) $this->blade('<x-course-meta :course="$course" :participants="true" />', ['course' => $course]))->toContain('Sudah diikuti 120 orang');
});
