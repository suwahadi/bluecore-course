<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseSegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(4),
            'youtube_video_id' => Str::random(11),
            'duration_seconds' => fake()->numberBetween(120, 1800),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
