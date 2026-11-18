<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'content' => '## '.fake()->sentence(3)."\n\n".fake()->paragraph(),
            'is_published' => true,
            'show_in_footer' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
