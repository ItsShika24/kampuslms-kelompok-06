<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Dosen: form buat tugas baru pada MK yang diampu.
     */
    public function create(Course $course)
    {
        return view('assignments.create', compact('course'));
    }

    /**
     * Dosen: simpan tugas baru.
     */
    public function store(Request $request, Course $course)
    {
        $this->ensureCanManageCourse($request, $course);
        $dosen = $request->user();

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at'       => ['required', 'date'],
            'max_score'    => ['required', 'numeric', 'min:1', 'max:1000'],
            'allow_late'   => ['nullable', 'boolean'],
            'status'       => ['required', 'in:draft,active,closed'],
        ]);

        $validated['allow_late'] = $request->boolean('allow_late');

        Assignment::create([
            'course_id'    => $course->id,
            'created_by'   => $dosen->id,
            'title'        => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'due_at'       => $validated['due_at'],
            'max_score'    => $validated['max_score'],
            'allow_late'   => $validated['allow_late'],
            'status'       => $validated['status'],
        ]);

        return redirect()
            ->route('mata-kuliah.show', $course->id)
            ->with('success', 'Tugas berhasil ditambahkan.');
    }

    /**
     * Mahasiswa: lihat detail tugas + form submit.
     */
    public function show(Assignment $assignment)
    {
        $assignment->load(['course', 'creator']);

        $mahasiswa = auth()->user()->role === 'mahasiswa' ? auth()->user() : null;
        $submission = $mahasiswa
            ? $mahasiswa->submissions()
                ->where('assignment_id', $assignment->id)
                ->with('grade')
                ->first()
            : null;

        return view('assignments.show', compact('assignment', 'mahasiswa', 'submission'));
    }

    /**
     * Mahasiswa: simpan submission (text note saja, tanpa upload file untuk kesederhanaan).
     */
    public function submit(Request $request, Assignment $assignment)
    {
        $mahasiswa = $request->user();

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $isLate = now()->isAfter($assignment->due_at);

        // Upsert — jika sudah pernah submit, update catatan
        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $mahasiswa->id],
            [
                'note'         => $validated['note'],
                'submitted_at' => now(),
                'is_late'      => $isLate,
            ]
        );

        return redirect()
            ->route('tugas.show', $assignment->id)
            ->with('success', $isLate ? 'Tugas dikumpulkan (terlambat).' : 'Tugas berhasil dikumpulkan!');
    }

    /**
     * Dosen: form edit tugas.
     */
    public function edit(Request $request, Assignment $assignment)
    {
        $assignment->load('course');
        $this->ensureCanManageCourse($request, $assignment->course);

        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Dosen: update data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $assignment->load('course');
        $this->ensureCanManageCourse($request, $assignment->course);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at'       => ['required', 'date'],
            'max_score'    => ['required', 'numeric', 'min:1', 'max:1000'],
            'allow_late'   => ['nullable', 'boolean'],
            'status'       => ['required', 'in:draft,active,closed'],
        ]);

        $assignment->update([
            'title'        => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'due_at'       => $validated['due_at'],
            'max_score'    => $validated['max_score'],
            'allow_late'   => $request->boolean('allow_late'),
            'status'       => $validated['status'],
        ]);

        return redirect()
            ->route('mata-kuliah.show', $assignment->course_id)
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    private function ensureCanManageCourse(Request $request, Course $course): void
    {
        abort_unless(
            $request->user()->role === 'admin' || $course->lecturer_id === $request->user()->id,
            403
        );
    }
}
