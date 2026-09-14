<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('MK###'),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'sks' => fake()->randomElement([2, 3, 4]),
            'lecturer_id' => null,
            'status' => fake()->randomElement(['draft', 'active', 'archived']),
        ];
    }
}