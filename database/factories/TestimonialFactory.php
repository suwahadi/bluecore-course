<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'note' => fake()->city(),
            'quote' => fake()->sentence(12),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_visible' => true,
        ];
    }
}
