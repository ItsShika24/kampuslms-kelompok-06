<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    /**
     * Mahasiswa mengumpulkan tugas (multipart, berkas).
     */
    public function store(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        // Hanya mahasiswa yang terdaftar pada mata kuliah yang boleh submit
        if ($user->role !== 'mahasiswa') {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $course = $assignment->course;
        $isEnrolled = $course && $course->students()->where('users.id', $user->id)->exists();

        if (! $isEnrolled || ! in_array($assignment->status, ['published', 'active'])) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        // Cegah pengumpulan ganda (constraint: 1 submission aktif per mahasiswa)
        if ($assignment->submissions()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'file' => ['Anda sudah mengumpulkan tugas ini.'],
            ]);
        }

        // Validasi tenggat waktu
        $isLate = false;
        if (now()->gt($assignment->due_at)) {
            if (! $assignment->allow_late) {
                throw ValidationException::withMessages([
                    'file' => ['Tugas ini sudah melewati batas waktu dan tidak menerima pengumpulan terlambat.'],
                ]);
            }
            $isLate = true;
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // Maksimal 10 MB
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $filePath = $file->store('submissions');

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id'       => $user->id,
            'file_path'     => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'note'          => $request->note,
            'submitted_at'  => now(),
            'is_late'       => $isLate,
        ]);

        $submission->load(['student', 'assignment']);

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Dosen memberi atau memperbarui nilai submission (upsert).
     * Mengembalikan 201 saat pertama kali dibuat, 200 saat diperbarui.
     */
    public function grade(Request $request, Submission $submission): JsonResponse
    {
        $user = $request->user();
        $assignment = $submission->assignment;

        $this->authorizeGrading($user, $assignment);

        $request->validate([
            'score'    => ['required', 'numeric', 'min:0', 'max:' . $assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => $user->id,
                'score'     => $request->score,
                'feedback'  => $request->feedback,
                'graded_at' => now(),
            ]
        );

        $grade->load('grader');
        $statusCode = $grade->wasRecentlyCreated ? 201 : 200;

        return (new GradeResource($grade))
            ->response()
            ->setStatusCode($statusCode);
    }

    /**
     * Validasi hak dosen/admin dalam menilai submission.
     */
    private function authorizeGrading(User $user, Assignment $assignment): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen') {
            $isCreator = $assignment->created_by === $user->id;
            $isCourseLecturer = $assignment->course && $assignment->course->lecturer_id === $user->id;

            if ($isCreator || $isCourseLecturer) {
                return;
            }
        }

        abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
    }
}
