<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dosen_cannot_edit_or_update_other_dosen_course(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);

        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id]);

        // Dosen A mencoba akses edit MK milik Dosen B -> harus 403
        $response = $this->actingAs($dosenA)->get("/mata-kuliah/{$courseB->id}/edit");
        $response->assertStatus(403);

        // Dosen A mencoba kirim PUT update ke MK milik Dosen B -> harus 403
        $putResponse = $this->actingAs($dosenA)->put("/mata-kuliah/{$courseB->id}", [
            'code'        => $courseB->code,
            'name'        => 'Manipulasi Nama MK',
            'sks'         => 3,
            'lecturer_id' => $dosenA->id,
            'status'      => 'active',
        ]);
        $putResponse->assertStatus(403);
    }

    public function test_dosen_can_update_own_course(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($dosen)->get("/mata-kuliah/{$course->id}/edit");
        $response->assertStatus(200);

        $putResponse = $this->actingAs($dosen)->put("/mata-kuliah/{$course->id}", [
            'code'        => $course->code,
            'name'        => 'Nama MK Terupdate',
            'sks'         => $course->sks,
            'lecturer_id' => $dosen->id,
            'status'      => 'active',
        ]);
        $putResponse->assertRedirect("/mata-kuliah/{$course->id}");
    }

    public function test_mahasiswa_cannot_view_other_student_submission(): void
    {
        $mhsA = User::factory()->create(['role' => 'mahasiswa']);
        $mhsB = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);

        $submissionB = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $mhsB->id,
        ]);

        // Mahasiswa A mencoba akses submission milik Mahasiswa B -> harus 403
        $response = $this->actingAs($mhsA)->get("/submissions/{$submissionB->id}");
        $response->assertStatus(403);

        // Mahasiswa B mengakses submission miliknya sendiri -> harus 200
        $validResponse = $this->actingAs($mhsB)->get("/submissions/{$submissionB->id}");
        $validResponse->assertStatus(200);
    }

    public function test_dosen_cannot_grade_other_dosen_submission(): void
    {
        $dosenA = User::factory()->create(['role' => 'dosen']);
        $dosenB = User::factory()->create(['role' => 'dosen']);
        $mhs = User::factory()->create(['role' => 'mahasiswa']);

        $courseB = Course::factory()->create(['lecturer_id' => $dosenB->id]);
        $assignmentB = Assignment::factory()->create(['course_id' => $courseB->id, 'created_by' => $dosenB->id]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignmentB->id,
            'user_id'       => $mhs->id,
        ]);

        // Dosen A mencoba menilai tugas mata kuliah Dosen B -> harus 403
        $response = $this->actingAs($dosenA)->post("/submissions/{$submission->id}/grade", [
            'score' => 95,
        ]);
        $response->assertStatus(403);

        // Dosen B (pengampu sah) menilai tugas mata kuliahnya -> sukses
        $validResponse = $this->actingAs($dosenB)->post("/submissions/{$submission->id}/grade", [
            'score' => 90,
        ]);
        $validResponse->assertSessionHas('success');
    }

    public function test_mahasiswa_cannot_grade_submission(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $mhs->id,
        ]);

        $response = $this->actingAs($mhs)->post("/submissions/{$submission->id}/grade", [
            'score' => 100,
        ]);
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $mhs = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($mhs)->get('/pengguna')->assertStatus(403);
        $this->actingAs($dosen)->get('/pengguna')->assertStatus(403);
        $this->actingAs($admin)->get('/pengguna')->assertStatus(200);
    }

    public function test_admin_has_full_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $this->actingAs($admin)->get("/mata-kuliah/{$course->id}/edit")->assertStatus(200);
        $this->actingAs($admin)->get('/mata-kuliah/create')->assertStatus(200);
    }
}
