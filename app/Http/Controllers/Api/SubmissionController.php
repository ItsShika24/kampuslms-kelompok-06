<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\GradeResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubmissionController extends Controller
{
    /**
     * Mahasiswa mengumpulkan tugas (multipart, berkas).
     */
    public function store(StoreSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();
        Gate::authorize('submit', $assignment);

        // Validasi tenggat waktu
        $submittedAt = now();
        $isLate = false;
        if ($submittedAt->gt($assignment->due_at)) {
            if (! $assignment->allow_late) {
                throw ValidationException::withMessages([
                    'file' => ['Tugas ini sudah melewati batas waktu dan tidak menerima pengumpulan terlambat.'],
                ]);
            }
            $isLate = true;
        }

        $validated = $request->validated();
        $oldFilePath = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->value('file_path');
        $file = $request->file('file');
        $filePath = $file->store("submissions/{$assignment->id}", 'local');
        if (! $filePath) {
            throw new RuntimeException('Berkas pengumpulan tugas gagal disimpan.');
        }

        $submission = Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            [
                'file_path' => $filePath,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'note' => $validated['note'] ?? null,
                'submitted_at' => $submittedAt,
                'is_late' => $isLate,
            ]
        );

        if ($oldFilePath && $oldFilePath !== $filePath) {
            Storage::disk('local')->delete($oldFilePath);
        }

        $submission->load(['student', 'assignment']);

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode($submission->wasRecentlyCreated ? 201 : 200);
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
            'score' => ['required', 'numeric', 'min:0', 'max:'.$assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => $user->id,
                'score' => $request->score,
                'feedback' => $request->feedback,
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
