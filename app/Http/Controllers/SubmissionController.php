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
     * Penyaringan ketat di LEVEL QUERY:
     * - Mahasiswa: HANYA riwayat tugas yang telah dikumpulkannya sendiri.
     * - Dosen: Seluruh pengumpulan mahasiswa pada mata kuliah yang diampu.
     * - Admin: Seluruh pengumpulan pada sistem.
     */
    public function index(Request $request)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('viewAny', Submission::class);

        // Penyaringan query berbasis peran untuk mencegah kebocoran daftar
        $query = match ($user->role) {
            'admin'     => Submission::with(['student', 'assignment.course', 'grade']),
            'dosen'     => Submission::whereHas('assignment.course', fn($q) => $q->where('lecturer_id', $user->id))
                ->with(['student', 'assignment.course', 'grade']),
            'mahasiswa' => Submission::where('user_id', $user->id)
                ->whereHas('assignment', fn($q) => $q->where('status', '!=', 'draft'))
                ->with(['student', 'assignment.course', 'grade']),
            default     => abort(403),
        };

        // Filter status penilaian
        if ($request->filled('status')) {
            if ($request->status === 'graded') {
                $query->has('grade');
            } elseif ($request->status === 'ungraded') {
                $query->doesntHave('grade');
            }
        }

        $submissions = $query->latest('submitted_at')->paginate(10)->withQueryString();
        $role = $user->role;

        return view('submissions.index', compact('submissions', 'role', 'user'));
    }

    /**
     * Menampilkan detail satu submission.
     * Dilindungi SubmissionPolicy::view (Admin, Dosen MK terkait, Mahasiswa Pemilik).
     * Mencegah Mahasiswa A melihat submission Mahasiswa B via tebak ID!
     */
    public function show(Submission $submission)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('view', $submission);

        $submission->load(['student', 'assignment.course', 'grade.grader']);

        return view('submissions.show', compact('submission'));
    }

    /**
     * Dosen/Admin: Menyimpan atau memperbarui nilai untuk submission tertentu.
     * Dilindungi SubmissionPolicy::grade (Admin & Dosen MK bersangkutan).
     * Mencegah Dosen A menilai tugas mata kuliah Dosen B!
     */
    public function grade(Request $request, Submission $submission)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

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
