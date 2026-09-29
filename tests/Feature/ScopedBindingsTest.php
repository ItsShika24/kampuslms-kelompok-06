<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScopedBindingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_valid_nested_scoped_assignment_returns_200(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $response = $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get("/dosen/courses/{$course->id}/assignments/{$assignment->id}");

        $response->assertStatus(200);
    }

    public function test_mismatched_nested_scoped_assignment_returns_404(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course1 = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course2 = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment2 = Assignment::factory()->create([
            'course_id'  => $course2->id,
            'created_by' => $dosen->id,
        ]);

        $response = $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get("/dosen/courses/{$course1->id}/assignments/{$assignment2->id}");

        $response->assertStatus(404);
    }

    public function test_alias_nested_scoped_route(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course1 = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course2 = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment1 = Assignment::factory()->create([
            'course_id'  => $course1->id,
            'created_by' => $dosen->id,
        ]);
        $assignment2 = Assignment::factory()->create([
            'course_id'  => $course2->id,
            'created_by' => $dosen->id,
        ]);

        $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get("/courses/{$course1->id}/assignments/{$assignment1->id}")
            ->assertStatus(200);

        $this->actingAs($dosen)
            ->withSession(['demo_role' => 'dosen'])
            ->get("/courses/{$course1->id}/assignments/{$assignment2->id}")
            ->assertStatus(404);
    }
}

