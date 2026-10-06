<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    /**
     * Buat tugas baru untuk mata kuliah (hanya dosen pengampu atau admin).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $validated = $request->validate([
            'course_id'    => ['required', 'exists:courses,id'],
            'title'        => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'due_at'       => ['required', 'date'],
            'max_score'    => ['nullable', 'numeric', 'min:1', 'max:100'],
            'allow_late'   => ['nullable', 'boolean'],
            'status'       => ['required', 'in:draft,published,active,closed'],
            'week_number'  => ['nullable', 'integer', 'between:1,16'],
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if ($user->role === 'dosen' && $course->lecturer_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
        }

        $assignment = Assignment::create([
            ...$validated,
            'created_by' => $user->id,
            'max_score'  => $request->input('max_score', 100),
            'allow_late' => $request->boolean('allow_late', true),
        ]);

        $assignment->load(['course', 'creator']);

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Perbarui tugas (hanya dosen pemilik tugas atau admin).
     */
    public function update(Request $request, Assignment $assignment): AssignmentResource
    {
        $this->authorizeAssignmentManagement($request->user(), $assignment);

        $validated = $request->validate([
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['sometimes', 'required', 'string'],
            'due_at'       => ['sometimes', 'required', 'date'],
            'max_score'    => ['sometimes', 'numeric', 'min:1', 'max:100'],
            'allow_late'   => ['sometimes', 'boolean'],
            'status'       => ['sometimes', 'required', 'in:draft,published,active,closed'],
            'week_number'  => ['nullable', 'integer', 'between:1,16'],
        ]);

        $assignment->update($validated);
        $assignment->load(['course', 'creator']);

        return new AssignmentResource($assignment);
    }

    /**
     * Hapus tugas (hanya dosen pemilik tugas atau admin).
     */
    public function destroy(Request $request, Assignment $assignment): Response
    {
        $this->authorizeAssignmentManagement($request->user(), $assignment);

        $assignment->delete();

        return response()->noContent();
    }

    /**
     * Tampilkan daftar pengumpulan (submissions) tugas (hanya dosen pemilik tugas atau admin).
     */
    public function submissions(Request $request, Assignment $assignment): AnonymousResourceCollection
    {
        $this->authorizeAssignmentManagement($request->user(), $assignment);

        $submissions = $assignment->submissions()
            ->with(['student', 'grade.grader'])
            ->latest('submitted_at')
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }

    /**
     * Validasi hak kelola dosen/admin terhadap tugas.
     */
    private function authorizeAssignmentManagement(User $user, Assignment $assignment): void
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
