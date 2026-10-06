<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dosen_only_sees_taught_courses(): void
    {
        $dosen1 = User::factory()->dosen()->create();
        $dosen2 = User::factory()->dosen()->create();

        $course1 = Course::factory()->create(['lecturer_id' => $dosen1->id]);
        $course2 = Course::factory()->create(['lecturer_id' => $dosen2->id]);

        Sanctum::actingAs($dosen1, ['*']);

        $response = $this->getJson('/api/v1/courses');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $course1->id);
    }

    public function test_mahasiswa_only_sees_enrolled_courses(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->dosen()->create();

        $course1 = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course2 = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $course1->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $response = $this->getJson('/api/v1/courses');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $course1->id);
    }

    public function test_course_show_returns_detail_with_counts(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        Material::create([
            'course_id'   => $course->id,
            'uploaded_by' => $dosen->id,
            'title'       => 'Slide Pertemuan 1',
            'type'        => 'file',
            'file_path'   => 'materials/dummy.pdf',
        ]);

        Sanctum::actingAs($dosen, ['*']);

        $response = $this->getJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.lecturer.id', $dosen->id)
            ->assertJsonPath('data.counts.materials', 1)
            ->assertJsonPath('data.counts.assignments', 1);
    }

    public function test_dosen_cannot_access_other_lecturers_course(): void
    {
        $dosen1 = User::factory()->dosen()->create();
        $dosen2 = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen2->id]);

        Sanctum::actingAs($dosen1, ['*']);

        $response = $this->getJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_unenrolled_mahasiswa_cannot_access_course(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $response = $this->getJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_mahasiswa_cannot_see_draft_assignments(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        $published = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'published',
            'title'      => 'Tugas Published',
        ]);

        $draft = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'draft',
            'title'      => 'Tugas Draft',
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $response = $this->getJson("/api/v1/courses/{$course->id}/assignments");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $published->id);
    }
}
