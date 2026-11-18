<?php

namespace Database\Factories;

use App\Models\CourseSegment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_segment_id' => CourseSegment::factory(),
            'last_position_seconds' => 0,
            'percent' => 0,
        ];
    }
}
