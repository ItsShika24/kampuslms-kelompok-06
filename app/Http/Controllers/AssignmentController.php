<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    /**
     * Memastikan auth guard tersinkronisasi jika menggunakan demo switcher.
     */
    protected function syncAuthUser(): ?User
    {
        if (! auth()->check() && session()->has('demo_role')) {
            $demoUser = User::where('role', session('demo_role'))->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        return auth()->user();
    }

    /**
     * Dosen: form buat tugas baru pada MK yang diampu.
     * Dilindungi AssignmentPolicy::create (Admin & Dosen Pengampu MK).
     */
    public function create(Course $course)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', [Assignment::class, $course]);

        return view('assignments.create', compact('course'));
    }

    /**
     * Dosen: simpan tugas baru.
     */
    public function store(Request $request, Course $course)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', [Assignment::class, $course]);

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
            'created_by'   => $user->id,
            'title'        => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'due_at'       => $validated['due_at'],
            'max_score'    => $validated['max_score'],
            'allow_late'   => $validated['allow_late'],
            'status'       => $validated['status'],
        ]);

        return redirect()
            ->route('mata-kuliah.show', ['course' => $course->id, 'tab' => 'tugas'])
            ->with('success', 'Tugas berhasil ditambahkan.');
    }

    /**
     * Mahasiswa / Dosen / Admin: lihat detail tugas + form submit.
     * Dilindungi AssignmentPolicy::view (Admin, Dosen Pengampu, Mahasiswa Enrolled).
     */
    public function show(?Course $course = null, ?Assignment $assignment = null)
    {
        // Jika dipanggil via route tunggal (/tugas/{assignment} atau /assignments/{assignment})
        if ($course instanceof Assignment && $assignment === null) {
            $assignment = $course;
            $course = null;
        }

        // Validasi scoping jika diakses lewat nested route /courses/{course}/assignments/{assignment}
        if ($course && $assignment && $assignment->course_id !== $course->id) {
            abort(404, 'Tugas tidak ditemukan pada mata kuliah ini.');
        }

        if (! $assignment) {
            abort(404, 'Tugas tidak ditemukan.');
        }

        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('view', $assignment);

        $assignment->load(['course.lecturer', 'creator']);

        $mahasiswa = ($user->role === 'mahasiswa') ? $user : User::where('role', 'mahasiswa')->first();

        // Ambil submission milik pengguna aktif jika Mahasiswa
        $submission = ($user->role === 'mahasiswa')
            ? Submission::where('assignment_id', $assignment->id)
                        ->where('user_id', $user->id)
                        ->with('grade')
                        ->first()
            : null;

        // Jika Dosen pengampu atau Admin, muat daftar seluruh pengumpulan mahasiswa
        $allSubmissions = collect();
        if (Gate::allows('viewSubmissions', $assignment)) {
            $allSubmissions = Submission::with(['student', 'grade'])
                ->where('assignment_id', $assignment->id)
                ->latest('submitted_at')
                ->get();
        }

        return view('assignments.show', compact('assignment', 'mahasiswa', 'submission', 'allSubmissions'));
    }

    /**
     * Mahasiswa: kumpulkan jawaban tugas.
     * Dilindungi AssignmentPolicy::submit.
     */
    public function submit(Request $request, Assignment $assignment)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('submit', $assignment);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $isLate = now()->isAfter($assignment->due_at);

        if ($isLate && ! $assignment->allow_late) {
            return back()->with('error', 'Tenggat waktu pengumpulan tugas ini telah berakhir dan tidak menerima pengumpulan terlambat.');
        }

        // Upsert — jika sudah pernah submit, update catatan
        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
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
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $assignment);

        $assignment->load('course');
        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Dosen: update data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $assignment);

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
            ->route('mata-kuliah.show', ['course' => $assignment->course_id, 'tab' => 'tugas'])
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * Dosen / Admin: hapus tugas.
     */
    public function destroy(Assignment $assignment)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('delete', $assignment);

        $courseId = $assignment->course_id;
        $assignment->delete();

        return redirect()
            ->route('mata-kuliah.show', ['course' => $courseId, 'tab' => 'tugas'])
            ->with('success', 'Tugas berhasil dihapus.');
    }
}
