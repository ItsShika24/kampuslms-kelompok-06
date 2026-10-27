<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_student_cannot_download_other_student_submission_file_prevents_idor(): void
    {
        Storage::fake('local');
        [, $student] = $this->createEnrolledAssignment();
        $otherStudent = User::factory()->mahasiswa()->create();
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/private-answer.pdf',
            'original_name' => 'jawaban.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, "%PDF-1.4\nPrivate answer.");

        $this->actingAs($otherStudent)
            ->get(route('submissions.download', $submission))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_download_submission_file(): void
    {
        Storage::fake('local');
        [, $student] = $this->createEnrolledAssignment();
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/private-answer.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, "%PDF-1.4\nPrivate answer.");

        $this->get(route('submissions.download', $submission))
            ->assertRedirect('/login');
    }

    public function test_file_with_fake_pdf_extension_is_rejected(): void
    {
        [, $student] = $this->createEnrolledAssignment();

        $this->actingAs($student)
            ->post(route('tugas.submit', $this->assignment), [
                'file' => UploadedFile::fake()
                    ->createWithContent('malicious.pdf', '<?php echo 123; ?>')
                    ->mimeType('application/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_deleting_submission_removes_physical_file_from_storage(): void
    {
        Storage::fake('local');
        [, $student] = $this->createEnrolledAssignment();
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/to-delete.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, "%PDF-1.4\nTemporary file.");

        $submission->delete();

        Storage::disk('local')->assertMissing('submissions/to-delete.pdf');
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }

    public function test_deleting_material_removes_physical_file_from_storage(): void
    {
        Storage::fake('local');
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi yang dihapus',
            'type' => 'file',
            'file_path' => 'materials/to-delete.pdf',
            'original_name' => 'materi.pdf',
        ]);
        Storage::disk('local')->put($material->file_path, "%PDF-1.4\nTemporary material.");

        $this->actingAs($lecturer)
            ->delete(route('materi.destroy', $material))
            ->assertRedirect();

        Storage::disk('local')->assertMissing('materials/to-delete.pdf');
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_deleting_assignment_cleans_submission_file_before_cascade(): void
    {
        Storage::fake('local');
        [$lecturer, $student] = $this->createEnrolledAssignment();
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/assignment-delete.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, "%PDF-1.4\nTemporary file.");

        $this->actingAs($lecturer)
            ->delete(route('tugas.destroy', $this->assignment))
            ->assertRedirect();

        Storage::disk('local')->assertMissing('submissions/assignment-delete.pdf');
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }

    public function test_deleting_course_cleans_material_and_submission_files_before_cascade(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/course-delete.pdf',
        ]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi terkait',
            'type' => 'file',
            'file_path' => 'materials/course-delete.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, "%PDF-1.4\nSubmission.");
        Storage::disk('local')->put($material->file_path, "%PDF-1.4\nMaterial.");

        $this->actingAs($admin)
            ->delete(route('mata-kuliah.destroy', $course))
            ->assertRedirect();

        Storage::disk('local')->assertMissing('submissions/course-delete.pdf');
        Storage::disk('local')->assertMissing('materials/course-delete.pdf');
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_resubmitting_replaces_the_private_file_without_duplicate_submission(): void
    {
        Storage::fake('local');
        [, $student] = $this->createEnrolledAssignment();

        $submit = fn (string $filename) => $this->actingAs($student)
            ->post(route('tugas.submit', $this->assignment), [
                'file' => $this->validPdf($filename),
            ])
            ->assertRedirect(route('tugas.show', $this->assignment));

        $submit('first.pdf');
        $firstSubmission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $student->id)
            ->firstOrFail();
        $firstPath = $firstSubmission->file_path;

        $submit('replacement.pdf');
        $updatedSubmission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        $this->assertSame($firstSubmission->id, $updatedSubmission->id);
        $this->assertNotSame($firstPath, $updatedSubmission->file_path);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($updatedSubmission->file_path);
        $this->assertSame(1, Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $student->id)
            ->count());
    }

    public function test_material_upload_is_stored_on_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->post(route('materi.store', $course), [
                'title' => 'Materi PDF',
                'type' => 'file',
                'file' => $this->validPdf('materi.pdf'),
            ])
            ->assertRedirect(route('mata-kuliah.show', $course->id));

        $material = Material::where('course_id', $course->id)->firstOrFail();
        $this->assertStringStartsWith('materials/', $material->file_path);
        Storage::disk('local')->assertExists($material->file_path);
        Storage::disk('public')->assertMissing($material->file_path);
    }

    public function test_lecturer_can_save_external_material_link_without_uploading_a_file(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->post(route('materi.store', $course), [
                'title' => 'Video Materi',
                'type' => 'link',
                'external_url' => 'https://example.com/materi',
            ])
            ->assertRedirect(route('mata-kuliah.show', $course->id));

        $this->assertDatabaseHas('materials', [
            'course_id' => $course->id,
            'title' => 'Video Materi',
            'type' => 'link',
            'external_url' => 'https://example.com/materi',
            'file_path' => null,
        ]);
    }

    public function test_material_rejects_invalid_external_link(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->from(route('materi.create', $course))
            ->post(route('materi.store', $course), [
                'title' => 'Tautan Tidak Valid',
                'type' => 'link',
                'external_url' => 'bukan-alamat-url',
            ])
            ->assertSessionHasErrors('external_url');

        $this->assertDatabaseMissing('materials', [
            'course_id' => $course->id,
            'title' => 'Tautan Tidak Valid',
        ]);
    }

    public function test_material_upload_rejects_files_over_20_mb(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $oversizedPdf = UploadedFile::fake()
            ->createWithContent('materi-besar.pdf', "%PDF-1.4\nContoh materi.")
            ->size(20481);

        $this->actingAs($lecturer)
            ->from(route('materi.create', $course))
            ->post(route('materi.store', $course), [
                'title' => 'Materi Terlalu Besar',
                'type' => 'file',
                'file' => $oversizedPdf,
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('materials', [
            'course_id' => $course->id,
            'title' => 'Materi Terlalu Besar',
        ]);
    }

    public function test_unenrolled_student_cannot_download_course_material(): void
    {
        Storage::fake('local');
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi Terbatas',
            'type' => 'file',
            'file_path' => 'materials/private.pdf',
            'original_name' => 'materi.pdf',
        ]);
        Storage::disk('local')->put($material->file_path, "%PDF-1.4\nMateri privat.");

        $this->actingAs($student)
            ->get(route('materi.download', $material))
            ->assertForbidden();
    }

    private function createEnrolledAssignment(): array
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $this->assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'active',
            'due_at' => now()->addDays(5),
            'allow_late' => false,
        ]);

        return [$lecturer, $student];
    }

    private function validPdf(string $filename): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, "%PDF-1.4\nTest PDF content.");
    }
}
