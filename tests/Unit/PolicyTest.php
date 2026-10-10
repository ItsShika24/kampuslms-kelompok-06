<?php

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_course_policy_authorization(): void
    {
        $admin = User::factory()->admin()->create();
        $dosen1 = User::factory()->dosen()->create();
        $dosen2 = User::factory()->dosen()->create();
        $mahasiswa1 = User::factory()->mahasiswa()->create();
        $mahasiswa2 = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen1->id]);
        $course->students()->attach($mahasiswa1->id, ['enrolled_at' => now()]);

        // Admin: can do everything
        $this->assertTrue(Gate::forUser($admin)->allows('view', $course));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $course));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $course));

        // Dosen 1 (owner): can view and update, cannot delete
        $this->assertTrue(Gate::forUser($dosen1)->allows('view', $course));
        $this->assertTrue(Gate::forUser($dosen1)->allows('update', $course));
        $this->assertFalse(Gate::forUser($dosen1)->allows('delete', $course));

        // Dosen 2 (other): cannot view or update
        $this->assertFalse(Gate::forUser($dosen2)->allows('view', $course));
        $this->assertFalse(Gate::forUser($dosen2)->allows('update', $course));

        // Mahasiswa 1 (enrolled): can view, cannot update or delete
        $this->assertTrue(Gate::forUser($mahasiswa1)->allows('view', $course));
        $this->assertFalse(Gate::forUser($mahasiswa1)->allows('update', $course));

        // Mahasiswa 2 (not enrolled): cannot view
        $this->assertFalse(Gate::forUser($mahasiswa2)->allows('view', $course));
    }

    public function test_submission_policy_prevents_idor(): void
    {
        $dosen = User::factory()->dosen()->create();
        $otherDosen = User::factory()->dosen()->create();
        $studentA = User::factory()->mahasiswa()->create();
        $studentB = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'created_by' => $dosen->id]);

        $submissionA = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $studentA->id,
        ]);

        // Student A can view their own submission
        $this->assertTrue(Gate::forUser($studentA)->allows('view', $submissionA));

        // IDOR CHECK: Student B CANNOT view Student A's submission!
        $this->assertFalse(Gate::forUser($studentB)->allows('view', $submissionA));

        // Student A cannot grade
        $this->assertFalse(Gate::forUser($studentA)->allows('grade', $submissionA));

        // Dosen course owner can view and grade
        $this->assertTrue(Gate::forUser($dosen)->allows('view', $submissionA));
        $this->assertTrue(Gate::forUser($dosen)->allows('grade', $submissionA));

        // Other dosen cannot view or grade
        $this->assertFalse(Gate::forUser($otherDosen)->allows('view', $submissionA));
        $this->assertFalse(Gate::forUser($otherDosen)->allows('grade', $submissionA));
    }

    public function test_assignment_policy_draft_protection_for_students(): void
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

        $activeAssignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
            'status'     => 'active',
        ]);

        // Student cannot view draft assignment even if enrolled
        $this->assertFalse(Gate::forUser($student)->allows('view', $draftAssignment));

        // Student can view active assignment if enrolled
        $this->assertTrue(Gate::forUser($student)->allows('view', $activeAssignment));

        // Dosen owner can view both
        $this->assertTrue(Gate::forUser($dosen)->allows('view', $draftAssignment));
        $this->assertTrue(Gate::forUser($dosen)->allows('view', $activeAssignment));
    }
}
