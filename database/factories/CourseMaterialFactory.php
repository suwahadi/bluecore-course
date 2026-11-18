<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => 'Modul '.fake()->words(2, true),
            'file_path' => 'materials/'.Str::lower(Str::random(14)).'.pdf',
            'original_name' => 'modul.pdf',
            'file_size' => fake()->numberBetween(50_000, 5_000_000),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
