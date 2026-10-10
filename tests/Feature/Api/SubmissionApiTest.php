<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_enrolled_mahasiswa_can_submit_file(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'published',
            'due_at'     => now()->addDays(5),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $file = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
            'file' => $file,
            'note' => 'Ini tugas pengantar saya.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.original_name', 'laporan.pdf')
            ->assertJsonPath('data.student.id', $mahasiswa->id)
            ->assertJsonPath('data.is_late', false);

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id'       => $mahasiswa->id,
            'is_late'       => false,
        ]);
    }

    public function test_unenrolled_mahasiswa_cannot_submit(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'published',
            'due_at'     => now()->addDays(5),
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $file = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
            'file' => $file,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_duplicate_submission_is_prevented(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'published',
            'due_at'     => now()->addDays(5),
        ]);

        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $mahasiswa->id,
            'file_path'     => 'submissions/old.pdf',
            'original_name' => 'old.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $file = UploadedFile::fake()->create('laporan_baru.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'Anda sudah mengumpulkan tugas ini.');
    }

    public function test_submission_past_deadline_rejected_if_late_not_allowed(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'published',
            'due_at'     => now()->subDays(2),
            'allow_late' => false,
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $file = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'Tugas ini sudah melewati batas waktu dan tidak menerima pengumpulan terlambat.');
    }

    public function test_submission_past_deadline_accepted_as_late_if_allowed(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($mahasiswa->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'published',
            'due_at'     => now()->subDays(2),
            'allow_late' => true,
        ]);

        Sanctum::actingAs($mahasiswa, ['*']);

        $file = UploadedFile::fake()->create('laporan_telat.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_late', true);
    }

    public function test_dosen_grading_first_time_returns_201_and_update_returns_200(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'max_score'  => 100,
        ]);

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'file_path'     => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        Sanctum::actingAs($dosen, ['*']);

        // 1. Pertama kali dinilai -> 201 Created
        $response1 = $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score'    => 85,
            'feedback' => 'Analisis cukup mendalam, pertahankan.',
        ]);

        $response1->assertStatus(201)
            ->assertJsonPath('data.score', 85)
            ->assertJsonPath('data.feedback', 'Analisis cukup mendalam, pertahankan.')
            ->assertJsonPath('data.grader.id', $dosen->id);

        // 2. Pembaruan nilai (upsert) -> 200 OK
        $response2 = $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score'    => 92,
            'feedback' => 'Revisi nilai setelah konfirmasi rubrik.',
        ]);

        $response2->assertStatus(200)
            ->assertJsonPath('data.score', 92)
            ->assertJsonPath('data.feedback', 'Revisi nilai setelah konfirmasi rubrik.');
    }

    public function test_other_dosen_cannot_grade_submission(): void
    {
        $dosenA = User::factory()->dosen()->create();
        $dosenB = User::factory()->dosen()->create();
        $courseA = Course::factory()->create(['lecturer_id' => $dosenA->id]);
        $assignmentA = Assignment::factory()->create([
            'course_id'  => $courseA->id,
            'created_by' => $dosenA->id,
        ]);

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $submission = Submission::create([
            'assignment_id' => $assignmentA->id,
            'user_id'       => $student->id,
            'file_path'     => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        Sanctum::actingAs($dosenB, ['*']);

        $response = $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 80,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }

    public function test_mahasiswa_cannot_grade_submission(): void
    {
        $dosen = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'file_path'     => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        Sanctum::actingAs($student, ['*']);

        $response = $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 100,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);
    }
}
