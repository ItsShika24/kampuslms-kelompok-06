<?php

namespace App\Http\Controllers;

use App\Events\GradeSubmitted;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
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
     * Menampilkan daftar pengumpulan tugas (Submission Center):
     * Penyaringan ketat di level query sesuai peran login (Modul 7.1).
     */
    public function index(Request $request)
    {
        $user = $this->syncAuthUser();
        $role = $user?->role ?? session('demo_role', 'mahasiswa');

        // Scoping di level query (Modul 7.1)
        $query = (match ($role) {
            'admin' => Submission::query(),
            'dosen' => Submission::whereHas('assignment.course', fn ($q) => $q->where('lecturer_id', $user?->id)),
            'mahasiswa' => Submission::where('user_id', $user?->id),
            default => abort(403),
        })->with(['student', 'assignment.course', 'grade']);

        // Filter status penilaian
        if ($request->filled('status')) {
            if ($request->status === 'graded') {
                $query->has('grade');
            } elseif ($request->status === 'ungraded') {
                $query->doesntHave('grade');
            }
        }

        $submissions = $query->latest('submitted_at')->paginate(10)->withQueryString();

        return view('submissions.index', compact('submissions', 'role', 'user'));
    }

    /**
     * Menampilkan detail satu submission.
     * Menggunakan Gate::authorize('view', $submission) via SubmissionPolicy (Penutup Celah IDOR).
     */
    public function show(Submission $submission)
    {
        $this->syncAuthUser();

        // Otorisasi melalui SubmissionPolicy (Menutup celah IDOR dari Minggu 5)
        Gate::authorize('view', $submission);

        $submission->load(['student', 'assignment.course', 'grade.grader']);

        return view('submissions.show', compact('submission'));
    }

    /**
     * Mengunduh berkas fisik submission tugas dari disk privat 'local'.
     * Otorisasi ketat via SubmissionPolicy (Modul 9.1 & 9.3).
     */
    public function download(Submission $submission)
    {
        $this->syncAuthUser();

        // Otorisasi: hanya pemilik submission, dosen pengampu MK, atau admin
        Gate::authorize('view', $submission);

        if (! $submission->file_path) {
            return back()->with('error', 'Tidak ada berkas yang diunggah untuk pengumpulan tugas ini.');
        }

        // Cek disk privat 'local'
        if (Storage::disk('local')->exists($submission->file_path)) {
            return Storage::disk('local')->download(
                $submission->file_path,
                $submission->original_name ?? basename($submission->file_path)
            );
        }

        // Fallback disk public jika ada data lama
        if (Storage::disk('public')->exists($submission->file_path)) {
            return Storage::disk('public')->download(
                $submission->file_path,
                $submission->original_name ?? basename($submission->file_path)
            );
        }

        abort(404, 'Berkas fisik submission tidak ditemukan di penyimpanan server.');
    }

    /**
     * Dosen/Admin: Menyimpan atau memperbarui nilai untuk submission tertentu.
     */
    public function grade(Request $request, Submission $submission)
    {
        $user = $this->syncAuthUser();

        // Otorisasi penilaian melalui SubmissionPolicy
        Gate::authorize('grade', $submission);

        $maxScore = optional($submission->assignment)->max_score ?? 100;

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$maxScore],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $grade = $submission->grade()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => $user->id,
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        // MINGGU 10: Dispatch event saat nilai disimpan/diperbarui
        GradeSubmitted::dispatch($grade, $submission);

        return back()->with('success', 'Nilai dan umpan balik berhasil disimpan.');
    }
}
