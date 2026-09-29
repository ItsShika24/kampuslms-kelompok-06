<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Menampilkan daftar pengumpulan tugas (Submission Center):
     * - Mahasiswa: Riwayat tugas yang telah dikumpulkan dan nilai/umpan balik.
     * - Dosen: Seluruh pengumpulan mahasiswa pada mata kuliah yang diampu (bisa difilter status nilai).
     * - Admin: Seluruh pengumpulan pada sistem.
     */
    public function index(Request $request)
    {
        if (! auth()->check() && session()->has('demo_role')) {
            $demoUser = User::where('role', session('demo_role'))->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        $user = auth()->user();
        $role = session('demo_role', $user?->role ?? 'mahasiswa');

        $query = Submission::with(['student', 'assignment.course', 'grade']);

        if ($role === 'mahasiswa') {
            $query->where('user_id', $user?->id);
        } elseif ($role === 'dosen') {
            $query->whereHas('assignment.course', function ($q) use ($user) {
                $q->where('lecturer_id', $user?->id);
            });
        }

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
     * Dilengkapi mitigasi IDOR (Lapis 1 - abort_unless) sesuai Modul Minggu 5.
     */
    public function show(Submission $submission)
    {
        // Pastikan auth guard tersinkronisasi jika menggunakan simulasi demo_role
        if (! auth()->check() && session()->has('demo_role')) {
            $demoUser = User::where('role', session('demo_role'))->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        $user = auth()->user();

        // Pemeriksaan kepemilikan sementara (Mitigasi IDOR):
        // Boleh diakses jika:
        // 1. Pemilik submission itu sendiri (Mahasiswa)
        // 2. Administrator
        // 3. Dosen pengampu dari mata kuliah tugas terkait
        abort_unless(
            $user && (
                $submission->user_id === $user->id
                || $user->role === 'admin'
                || (optional(optional($submission->assignment)->course)->lecturer_id === $user->id)
            ),
            403,
            'Akses Ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
        );

        $submission->load(['student', 'assignment.course', 'grade.grader']);

        return view('submissions.show', compact('submission'));
    }

    /**
     * Dosen/Admin: Menyimpan atau memperbarui nilai untuk submission tertentu.
     */
    public function grade(Request $request, Submission $submission)
    {
        if (! auth()->check() && session()->has('demo_role')) {
            $demoUser = User::where('role', session('demo_role'))->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        $user = auth()->user();

        // Hanya Admin atau Dosen pengampu MK yang boleh menilai
        abort_unless(
            $user && (
                $user->role === 'admin'
                || ($user->role === 'dosen' && optional(optional($submission->assignment)->course)->lecturer_id === $user->id)
            ),
            403,
            'Akses Ditolak: Hanya dosen pengampu mata kuliah ini yang dapat memberikan nilai.'
        );

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
