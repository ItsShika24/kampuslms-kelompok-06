<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
            'admin'     => Submission::query(),
            'dosen'     => Submission::whereHas('assignment.course', fn($q) => $q->where('lecturer_id', $user?->id)),
            'mahasiswa' => Submission::where('user_id', $user?->id),
            default     => abort(403),
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
     * Dosen/Admin: Menyimpan atau memperbarui nilai untuk submission tertentu.
     */
    public function grade(Request $request, Submission $submission)
    {
        $user = $this->syncAuthUser();

        // Otorisasi penilaian melalui SubmissionPolicy
        Gate::authorize('grade', $submission);

        $maxScore = optional($submission->assignment)->max_score ?? 100;

        $validated = $request->validate([
            'score'    => ['required', 'numeric', 'min:0', 'max:' . $maxScore],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->grade()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => $user->id,
                'score'     => $validated['score'],
                'feedback'  => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        return back()->with('success', 'Nilai dan umpan balik berhasil disimpan.');
    }
}
