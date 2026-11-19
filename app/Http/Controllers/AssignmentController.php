<?php

namespace App\Http\Controllers;

use App\Events\AssignmentPublished;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'allow_late' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,active,closed'],
        ]);

        $validated['allow_late'] = $request->boolean('allow_late');

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $user->id,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'],
            'status' => $validated['status'],
        ]);

        // MINGGU 10: Dispatch event saat tugas diterbitkan (status active)
        if ($assignment->status === 'active') {
            AssignmentPublished::dispatch($assignment);
        }

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

        $assignment->loadMissing(['course.lecturer', 'creator']);

        // Otorisasi via AssignmentPolicy (Mencegah Mahasiswa melihat tugas draft / unenrolled)
        Gate::authorize('view', $assignment);

        // Optimasi Tanggapan Review Zaldy (PR #25):
        // 1. Muat submission & mahasiswa hanya jika pengguna aktif adalah mahasiswa
        $isMahasiswa = $user && $user->role === 'mahasiswa';
        $mahasiswa = $isMahasiswa
            ? $user
            : (app()->environment(['local', 'testing']) && session('demo_role') === 'mahasiswa'
                ? User::where('role', 'mahasiswa')->first()
                : null);

        // Ambil submission milik pengguna aktif (Mahasiswa)
        $submission = $mahasiswa
            ? Submission::where('assignment_id', $assignment->id)
                ->where('user_id', $mahasiswa->id)
                ->with('grade')
                ->first()
            : null;

        // Jika Dosen pengampu atau Admin, muat daftar seluruh pengumpulan mahasiswa untuk tugas ini
        $allSubmissions = collect();
        $gradedCount = 0;
        $ungradedCount = 0;

        if ($user && ($user->role === 'admin' || ($user->role === 'dosen' && optional($assignment->course)->lecturer_id === $user->id))) {
            $allSubmissions = Submission::with(['student', 'grade'])
                ->where('assignment_id', $assignment->id)
                ->latest('submitted_at')
                ->get();

            // 2. Hitung statistik dinilai & belum dinilai sekali saja agar tidak berulang di view Blade
            $gradedCount = $allSubmissions->whereNotNull('grade')->count();
            $ungradedCount = $allSubmissions->count() - $gradedCount;
        }

        return view('assignments.show', compact('assignment', 'mahasiswa', 'submission', 'allSubmissions', 'gradedCount', 'ungradedCount'));
    }

    /**
     * Mahasiswa: kumpulkan jawaban tugas.
     */
    public function submit(StoreSubmissionRequest $request, Assignment $assignment)
    {
        $user = $this->syncAuthUser();

        $assignment->loadMissing('course');

        // Otorisasi via AssignmentPolicy (Cegah IDOR / pengumpulan tugas oleh mahasiswa yang tidak terdaftar)
        Gate::authorize('submit', $assignment);

        $validated = $request->validated();
        $submittedAt = now();
        $isLate = $submittedAt->isAfter($assignment->due_at);

        if ($isLate && ! $assignment->allow_late) {
            return back()->with('error', 'Tenggat waktu pengumpulan telah berakhir.');
        }

        if ($assignment->status === 'closed' && ! $assignment->allow_late) {
            return back()->with('error', 'Tugas ini sudah ditutup dan tidak menerima pengumpulan baru.');
        }

        $existingSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        $file = $request->file('file');
        $filePath = $existingSubmission?->file_path;
        $origName = $existingSubmission?->original_name;
        $fileSize = $existingSubmission?->file_size;

        if ($file) {
            $newPath = $file->store("submissions/{$assignment->id}", 'local');
            if (! $newPath) {
                throw new RuntimeException('Berkas pengumpulan tugas gagal disimpan.');
            }
            if ($filePath && $filePath !== $newPath) {
                Storage::disk('local')->delete($filePath);
            }
            $filePath = $newPath;
            $origName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        } elseif (! $existingSubmission) {
            return back()->with('error', 'Berkas pengumpulan tugas wajib diunggah.');
        }

        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            [
                'file_path' => $filePath,
                'original_name' => $origName,
                'file_size' => $fileSize,
                'note' => $validated['note'] ?? null,
                'submitted_at' => $submittedAt,
                'is_late' => $isLate,
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

        $assignment->loadMissing('course');

        Gate::authorize('update', $assignment);

        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Dosen: update data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $this->syncAuthUser();

        $assignment->loadMissing('course');

        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'allow_late' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,active,closed'],
        ]);

        $wasDraft = $assignment->status === 'draft';

        $assignment->update([
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $request->boolean('allow_late'),
            'status' => $validated['status'],
        ]);

        // MINGGU 10: Dispatch event jika tugas baru diaktifkan dari draft
        if ($wasDraft && $assignment->status === 'active') {
            AssignmentPublished::dispatch($assignment);
        }

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

        $assignment->loadMissing('course');

        Gate::authorize('delete', $assignment);

        $courseId = $assignment->course_id;

        DB::transaction(function () use ($assignment) {
            // Modul 9.1: Hapus berkas fisik submission sebelum cascade delete database
            foreach ($assignment->submissions as $sub) {
                if ($sub->file_path && Storage::disk('local')->exists($sub->file_path)) {
                    Storage::disk('local')->delete($sub->file_path);
                }
            }
            $assignment->delete();
        });

        return redirect()
            ->route('mata-kuliah.show', ['course' => $courseId, 'tab' => 'tugas'])
            ->with('success', 'Tugas berhasil dihapus beserta berkas pengumpulannya.');
    }
}
