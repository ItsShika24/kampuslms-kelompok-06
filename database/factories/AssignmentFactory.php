<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => \App\Models\Course::factory(),
            'created_by' => \App\Models\User::factory(),
            'title' => fake()->sentence(4),
            'instructions' => fake()->paragraph(),
            'due_at' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'max_score' => 100,
            'allow_late' => fake()->boolean(),
            'status' => fake()->randomElement(['draft', 'active', 'closed']),
        ];
    }
}