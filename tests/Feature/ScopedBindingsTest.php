<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Tests\TestCase;

class ScopedBindingsTest extends TestCase
{
    public function test_valid_nested_scoped_assignment_returns_200(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        $response = $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get('/dosen/courses/1/assignments/1');

        $response->assertStatus(200);
    }

    public function test_mismatched_nested_scoped_assignment_returns_404(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        // Tugas 4 adalah milik Course 2, bukan Course 1
        $response = $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get('/dosen/courses/1/assignments/4');

        $response->assertStatus(404);
    }

    public function test_alias_nested_scoped_route(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        // Valid: Tugas 1 milik Course 1
        $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get('/courses/1/assignments/1')
            ->assertStatus(200);

        // Invalid: Tugas 4 bukan milik Course 1 -> 404
        $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get('/courses/1/assignments/4')
            ->assertStatus(404);
    }
}
