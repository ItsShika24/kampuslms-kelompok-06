<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthorizationWebTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_student_cannot_view_another_student_submission_prevents_idor(): void
    {
        $dosen = User::factory()->dosen()->create();
        $studentA = User::factory()->mahasiswa()->create();
        $studentB = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'active',
        ]);

        $submissionA = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $studentA->id,
        ]);

        // Student A can view their own submission
        $responseA = $this->actingAs($studentA)->get(route('submissions.show', $submissionA));
        $responseA->assertStatus(200);

        // IDOR Test: Student B attempts to access Student A's submission -> MUST RETURN 403
        $responseB = $this->actingAs($studentB)->get(route('submissions.show', $submissionA));
        $responseB->assertStatus(403);
    }

    public function test_dosen_cannot_edit_another_dosen_course(): void
    {
        $dosen1 = User::factory()->dosen()->create();
        $dosen2 = User::factory()->dosen()->create();

        $course1 = Course::factory()->create(['lecturer_id' => $dosen1->id]);

        // Dosen 2 tries to access edit form of Dosen 1's course -> MUST RETURN 403
        $response = $this->actingAs($dosen2)->get(route('mata-kuliah.edit', $course1));
        $response->assertStatus(403);

        // Dosen 2 tries to PUT update to Dosen 1's course -> MUST RETURN 403
        $updateResponse = $this->actingAs($dosen2)->put(route('mata-kuliah.update', $course1), [
            'code'        => $course1->code,
            'name'        => 'Hacked Title',
            'sks'         => 3,
            'lecturer_id' => $dosen2->id,
            'status'      => 'active',
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_student_cannot_access_create_course_page(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($student)->get(route('mata-kuliah.create'));
        $response->assertStatus(403);
    }

    public function test_student_cannot_grade_submission(): void
    {
        $dosen = User::factory()->dosen()->create();
        $studentA = User::factory()->mahasiswa()->create();
        $studentB = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $submissionA = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $studentA->id,
        ]);

        // Student B tries to grade Student A's submission -> MUST RETURN 403
        $response = $this->actingAs($studentB)->post(route('submissions.grade', $submissionA), [
            'score'    => 100,
            'feedback' => 'Curang',
        ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_view_draft_assignment(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $draftAssignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'draft',
        ]);

        $response = $this->actingAs($student)->get(route('tugas.show', $draftAssignment));
        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($dosen)->get(route('pengguna.index'))->assertStatus(403);
        $this->actingAs($student)->get(route('pengguna.index'))->assertStatus(403);
    }
}
