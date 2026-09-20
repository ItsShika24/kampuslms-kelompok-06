<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
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
        // Ambil user dosen secara dinamis berdasarkan role
        $dosen = User::where('role', 'dosen')->firstOrFail();

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

        // Ambil mahasiswa secara dinamis berdasarkan role
        $mahasiswa = User::where('role', 'mahasiswa')->first();
        $submission = $mahasiswa
            ? Submission::where('assignment_id', $assignment->id)
                        ->where('user_id', $mahasiswa->id)
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
        // Ambil mahasiswa secara dinamis berdasarkan role
        $mahasiswa = User::where('role', 'mahasiswa')->firstOrFail();

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
    public function edit(Assignment $assignment)
    {
        $assignment->load('course');
        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Dosen: update data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
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
}
