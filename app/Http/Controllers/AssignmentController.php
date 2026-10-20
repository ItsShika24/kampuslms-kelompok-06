<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
     */
    public function create(Course $course)
    {
        $this->syncAuthUser();

        // Otorisasi via AssignmentPolicy
        Gate::authorize('create', [Assignment::class, $course]);

        return view('assignments.create', compact('course'));
    }

    /**
     * Dosen: simpan tugas baru.
     */
    public function store(Request $request, Course $course)
    {
        $user = $this->syncAuthUser();

        // Otorisasi via AssignmentPolicy
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
     * Mendukung pemanggilan standalone show(?Assignment $assignment)
     * maupun nested scoped show(?Course $course, ?Assignment $assignment).
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

        // Otorisasi via AssignmentPolicy (Mencegah Mahasiswa melihat tugas draft / unenrolled)
        Gate::authorize('view', $assignment);

        $assignment->load(['course.lecturer', 'creator']);

        $mahasiswa = ($user && $user->role === 'mahasiswa') ? $user : User::where('role', 'mahasiswa')->first();

        // Ambil submission milik pengguna aktif (Mahasiswa)
        $submission = $mahasiswa
            ? Submission::where('assignment_id', $assignment->id)
                        ->where('user_id', $mahasiswa->id)
                        ->with('grade')
                        ->first()
            : null;

        // Jika Dosen pengampu atau Admin, muat daftar seluruh pengumpulan mahasiswa untuk tugas ini
        $allSubmissions = collect();
        if ($user && ($user->role === 'admin' || ($user->role === 'dosen' && optional($assignment->course)->lecturer_id === $user->id))) {
            $allSubmissions = Submission::with(['student', 'grade'])
                ->where('assignment_id', $assignment->id)
                ->latest('submitted_at')
                ->get();
        }

        return view('assignments.show', compact('assignment', 'mahasiswa', 'submission', 'allSubmissions'));
    }

    /**
     * Mahasiswa: kumpulkan jawaban tugas.
     */
    public function submit(Request $request, Assignment $assignment)
    {
        $user = $this->syncAuthUser();

        // Otorisasi via AssignmentPolicy (Cegah IDOR / pengumpulan tugas oleh mahasiswa yang tidak terdaftar)
        Gate::authorize('submit', $assignment);

        // Validasi tenggat waktu & status tugas di server
        $isLate = now()->gt($assignment->due_at);

        if ($isLate && ! $assignment->allow_late) {
            return back()->with('error', 'Tenggat waktu pengumpulan telah berakhir.');
        }

        if ($assignment->status === 'closed' && ! $assignment->allow_late) {
            return back()->with('error', 'Tugas ini sudah ditutup dan tidak menerima pengumpulan baru.');
        }

        // Validasi berkas fisik dan catatan
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,zip', 'max:10240'], // Maksimal 10 MB
            'note' => ['nullable', 'string', 'max:5000'],
        ], [
            'file.required' => 'Berkas pengumpulan tugas wajib diunggah.',
            'file.file'     => 'Berkas yang diunggah tidak valid.',
            'file.mimes'    => 'Format berkas harus berupa PDF, DOC, DOCX, atau ZIP.',
            'file.max'      => 'Ukuran berkas maksimal adalah 10 MB (10.240 KB).',
            'note.max'      => 'Catatan maksimal 5.000 karakter.',
        ]);

        $file = $request->file('file');

        // Cari submission lama jika mahasiswa mengumpulkan ulang
        $existingSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        // Jika mahasiswa upload ulang, pastikan berkas fisik lama dihapus dari storage agar tidak menjadi file yatim
        if ($existingSubmission && $existingSubmission->file_path && Storage::disk('local')->exists($existingSubmission->file_path)) {
            Storage::disk('local')->delete($existingSubmission->file_path);
        }

        // Simpan berkas fisik ke disk privat 'local' dengan nama acak
        $path = $file->store("submissions/{$assignment->id}", 'local');

        // Upsert — jika sudah pernah submit, perbarui data submission dan berkas fisik
        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            [
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size'     => $file->getSize(),
                'note'          => $validated['note'] ?? null,
                'submitted_at'  => now(),
                'is_late'       => $isLate,
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
        $this->syncAuthUser();

        Gate::authorize('update', $assignment);

        $assignment->load('course');
        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Dosen: update data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $this->syncAuthUser();

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
        $this->syncAuthUser();

        Gate::authorize('delete', $assignment);

        $courseId = $assignment->course_id;

        // Modul 9.1: Hapus berkas fisik submission sebelum cascade delete database
        foreach ($assignment->submissions as $sub) {
            if ($sub->file_path && Storage::disk('local')->exists($sub->file_path)) {
                Storage::disk('local')->delete($sub->file_path);
            }
        }

        $assignment->delete();

        return redirect()
            ->route('mata-kuliah.show', ['course' => $courseId, 'tab' => 'tugas'])
            ->with('success', 'Tugas berhasil dihapus beserta berkas pengumpulannya.');
    }
}
