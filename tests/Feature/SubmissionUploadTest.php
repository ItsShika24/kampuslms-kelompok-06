<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionUploadTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_student_can_submit_assignment_with_valid_file_to_private_storage(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'    => $course->id,
            'created_by'   => $dosen->id,
            'status'       => 'active',
            'due_at'       => now()->addDays(3),
            'allow_late'   => false,
        ]);

        $file = UploadedFile::fake()->create('laporan_akhir.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($student)->post(route('tugas.submit', $assignment), [
            'file' => $file,
            'note' => 'Pengumpulan tugas praktikum minggu 9.',
        ]);

        $response->assertRedirect(route('tugas.show', $assignment));
        $response->assertSessionHas('success');

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertNotNull($submission);
        $this->assertEquals('laporan_akhir.pdf', $submission->original_name);
        $this->assertFalse($submission->is_late);
        $this->assertNotNull($submission->file_path);
        $this->assertStringStartsWith("submissions/{$assignment->id}/", $submission->file_path);

        // Verifikasi berkas fisik benar tersimpan di disk privat 'local'
        Storage::disk('local')->assertExists($submission->file_path);
    }

    public function test_resubmitting_assignment_deletes_old_file_and_updates_submission(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'    => $course->id,
            'created_by'   => $dosen->id,
            'status'       => 'active',
            'due_at'       => now()->addDays(3),
            'allow_late'   => false,
        ]);

        // 1. Pengumpulan pertama
        $firstFile = UploadedFile::fake()->create('draft_jawaban.pdf', 500, 'application/pdf');
        $this->actingAs($student)->post(route('tugas.submit', $assignment), [
            'file' => $firstFile,
            'note' => 'Versi 1',
        ]);

        $firstSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();

        $oldFilePath = $firstSubmission->file_path;
        Storage::disk('local')->assertExists($oldFilePath);

        // 2. Pengumpulan kedua (re-submit / perbarui jawaban)
        $secondFile = UploadedFile::fake()->create('final_jawaban.pdf', 800, 'application/pdf');
        $this->actingAs($student)->post(route('tugas.submit', $assignment), [
            'file' => $secondFile,
            'note' => 'Versi Final Revisi',
        ]);

        // Verifikasi hanya ada 1 baris submission (updateOrCreate)
        $this->assertEquals(1, Submission::where('assignment_id', $assignment->id)->where('user_id', $student->id)->count());

        $updatedSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertEquals('final_jawaban.pdf', $updatedSubmission->original_name);
        $this->assertEquals('Versi Final Revisi', $updatedSubmission->note);

        // Verifikasi berkas lama terhapus dari storage (tidak ada orphan file)
        Storage::disk('local')->assertMissing($oldFilePath);

        // Verifikasi berkas baru ada di storage
        Storage::disk('local')->assertExists($updatedSubmission->file_path);
    }

    public function test_submission_past_deadline_rejected_if_allow_late_is_false(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'    => $course->id,
            'created_by'   => $dosen->id,
            'status'       => 'active',
            'due_at'       => now()->subDays(1), // Lewat tenggat
            'allow_late'   => false,
        ]);

        $file = UploadedFile::fake()->create('telat.pdf', 500, 'application/pdf');

        $response = $this->actingAs($student)->post(route('tugas.submit', $assignment), [
            'file' => $file,
        ]);

        $response->assertSessionHas('error', 'Tenggat waktu pengumpulan telah berakhir.');
        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
        ]);
    }

    public function test_submission_past_deadline_accepted_as_late_if_allow_late_is_true(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'    => $course->id,
            'created_by'   => $dosen->id,
            'status'       => 'active',
            'due_at'       => now()->subDays(1), // Lewat tenggat
            'allow_late'   => true,
        ]);

        $file = UploadedFile::fake()->create('telat_tapi_boleh.pdf', 500, 'application/pdf');

        $response = $this->actingAs($student)->post(route('tugas.submit', $assignment), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('tugas.show', $assignment));
        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'is_late'       => true,
        ]);
    }

    public function test_submission_rejects_disallowed_file_extension(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'    => $course->id,
            'created_by'   => $dosen->id,
            'status'       => 'active',
            'due_at'       => now()->addDays(2),
            'allow_late'   => false,
        ]);

        $invalidFile = UploadedFile::fake()->create('exploit.exe', 500);

        $response = $this->actingAs($student)->post(route('tugas.submit', $assignment), [
            'file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
        ]);
    }

    public function test_student_can_download_own_submission_file(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $filePath = "submissions/{$assignment->id}/test_file.pdf";
        Storage::disk('local')->put($filePath, 'konten berkas jawaban');

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'file_path'     => $filePath,
            'original_name' => 'laporan_asli.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        $response = $this->actingAs($student)->get(route('submissions.download', $submission));

        $response->assertOk();
        $this->assertStringContainsString(
            'laporan_asli.pdf',
            $response->headers->get('content-disposition')
        );
    }

    public function test_dosen_can_download_student_submission_file(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $filePath = "submissions/{$assignment->id}/jawaban_mhs.pdf";
        Storage::disk('local')->put($filePath, 'konten berkas mahasiswa');

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'file_path'     => $filePath,
            'original_name' => 'jawaban_mhs.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        $response = $this->actingAs($dosen)->get(route('submissions.download', $submission));

        $response->assertOk();
    }

    public function test_other_student_cannot_download_submission_prevents_idor(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->dosen()->create();
        $studentA = User::factory()->mahasiswa()->create();
        $studentB = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach([$studentA->id, $studentB->id], ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $filePath = "submissions/{$assignment->id}/jawaban_a.pdf";
        Storage::disk('local')->put($filePath, 'konten rahasia mahasiswa A');

        $submissionA = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $studentA->id,
            'file_path'     => $filePath,
            'original_name' => 'jawaban_a.pdf',
            'file_size'     => 1024,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        // Mahasiswa B mencoba mengunduh berkas jawaban Mahasiswa A via URL langsung -> 403 Forbidden
        $response = $this->actingAs($studentB)->get(route('submissions.download', $submissionA));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_download_submission(): void
    {
        $dosen = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create([
            'course_id'  => $course->id,
            'created_by' => $dosen->id,
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $student->id,
            'file_path'     => "submissions/{$assignment->id}/dummy.pdf",
            'original_name' => 'dummy.pdf',
            'file_size'     => 512,
            'submitted_at'  => now(),
            'is_late'       => false,
        ]);

        $response = $this->get(route('submissions.download', $submission));

        $response->assertRedirect(route('login'));
    }
}
