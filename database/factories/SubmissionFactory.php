<?php

namespace Database\Factories;

use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => \App\Models\Assignment::factory(),
            'user_id' => \App\Models\User::factory(),
            'file_path' => 'submissions/jawaban.pdf',
            'original_name' => 'jawaban.pdf',
            'file_size' => fake()->numberBetween(10000, 500000),
            'note' => fake()->optional()->sentence(),
            'submitted_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'is_late' => fake()->boolean(),
        ];
    }
}