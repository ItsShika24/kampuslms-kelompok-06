<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dosen_can_create_assignment(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        Sanctum::actingAs($dosen, ['*']);

        $payload = [
            'course_id'    => $course->id,
            'title'        => 'Tugas 1: Analisis Basis Data',
            'instructions' => 'Buatlah ERD dan normalisasi hingga 3NF.',
            'due_at'       => now()->addDays(7)->toDateTimeString(),
            'max_score'    => 100,
            'allow_late'   => true,
            'status'       => 'published',
            'week_number'  => 1,
        ];

        $response = $this->postJson('/api/v1/assignments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Tugas 1: Analisis Basis Data')
            ->assertJsonPath('data.course.id', $course->id)
            ->assertJsonPath('data.creator.id', $dosen->id);
    }

    public function test_mahasiswa_cannot_create_assignment(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id'    => $course->id,
            'title'        => 'Tugas Ilegal',
            'instructions' => 'Tidak boleh dibuat mahasiswa',
            'due_at'       => now()->addDays(3)->toDateTimeString(),
            'status'       => 'published',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_dosen_cannot_create_assignment_for_other_dosen_course(): void
    {
        $dosenA = User::factory()->dosen()->create();
        $dosenB = User::factory()->dosen()->create();
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id]);

        Sanctum::actingAs($dosenA, ['*']);

        $response = $this->postJson('/api/v1/assignments', [
            'course_id'    => $courseB->id,
            'title'        => 'Tugas Liar',
            'instructions' => 'Mencoba membuat tugas di MK dosen lain',
            'due_at'       => now()->addDays(3)->toDateTimeString(),
            'status'       => 'published',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_dosen_can_update_own_assignment(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'title'      => 'Judul Awal',
        ]);

        Sanctum::actingAs($dosen, ['*']);

        $response = $this->putJson("/api/v1/assignments/{$assignment->id}", [
            'title' => 'Judul Revisi',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Judul Revisi');
    }

    public function test_dosen_cannot_update_other_dosen_assignment(): void
    {
        $dosenA = User::factory()->dosen()->create();
        $dosenB = User::factory()->dosen()->create();
        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id]);
        $assignmentB = Assignment::factory()->create([
            'course_id'  => $courseB->id,
            'created_by' => $dosenB->id,
        ]);

        Sanctum::actingAs($dosenA, ['*']);

        $response = $this->putJson("/api/v1/assignments/{$assignmentB->id}", [
            'title' => 'Upaya Pembajakan Tugas',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_dosen_can_delete_own_assignment(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen, ['*']);

        $response = $this->deleteJson("/api/v1/assignments/{$assignment->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_dosen_can_view_submissions_for_own_assignment(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $student = User::factory()->create(['role' => 'mahasiswa']);
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'file_path'     => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        Sanctum::actingAs($dosen, ['*']);

        $response = $this->getJson("/api/v1/assignments/{$assignment->id}/submissions");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.student.id', $student->id);
    }

    public function test_mahasiswa_cannot_view_submissions(): void
    {
        $dosen = User::factory()->dosen()->create();
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $response = $this->getJson("/api/v1/assignments/{$assignment->id}/submissions");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }
}
