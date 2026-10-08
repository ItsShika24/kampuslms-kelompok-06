<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard sesuai role pengguna aktif yang terautentikasi.
     * Menggunakan akun autentikasi nyata dari Auth::user().
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $activeUser = Auth::user();
        $role       = $activeUser->role;

        // Hitung statistik & data sesuai role pengguna yang login
        $stats             = [];
        $courses           = collect();
        $recentSubmissions = collect();
        $allUsers          = collect();

        if ($role === 'admin') {
            $allCourses = Course::with('lecturer')->get();

            // Semester unik: digit ratusan dari kode MK (SI101 → 1, SI201 → 2, dst.)
            $semesterCount = $allCourses->map(function ($c) {
                preg_match('/\d+/', $c->code, $m);
                return $m ? (int) floor((int) $m[0] / 100) : 0;
            })->unique()->filter()->count();

            $stats = [
                'mata_kuliah'     => $allCourses->count(),
                'semester'        => $semesterCount,
                'total_sks'       => $allCourses->sum('sks'),
                'total_dosen'     => User::where('role', 'dosen')->count(),
                'total_mahasiswa' => User::where('role', 'mahasiswa')->count(),
            ];
            $courses  = $allCourses;
            $allUsers = User::orderBy('role')->orderBy('name')->get();
            $recentSubmissions = Submission::with(['student', 'assignment.course', 'grade'])
                ->latest('submitted_at')
                ->take(5)
                ->get();

        } elseif ($role === 'dosen') {
            $myCourses = Course::with(['students'])
                ->where('lecturer_id', $activeUser->id)
                ->get();

            $semesterCount = $myCourses->map(function ($c) {
                preg_match('/\d+/', $c->code, $m);
                return $m ? (int) floor((int) $m[0] / 100) : 0;
            })->unique()->filter()->count();

            $stats = [
                'mata_kuliah'     => $myCourses->count(),
                'semester'        => $semesterCount,
                'total_sks'       => $myCourses->sum('sks'),
                'total_mahasiswa' => $myCourses->sum(fn($c) => $c->students->count()),
            ];
            $courses = $myCourses;

            $courseIds = $myCourses->pluck('id');
            $recentSubmissions = Submission::whereHas('assignment', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })->with(['student', 'assignment.course', 'grade'])
              ->latest('submitted_at')
              ->take(5)
              ->get();

        } else {
            // Mahasiswa
            $myCourses = $activeUser->courses()->with('lecturer')->get();

            $semesterCount = $myCourses->map(function ($c) {
                preg_match('/\d+/', $c->code, $m);
                return $m ? (int) floor((int) $m[0] / 100) : 0;
            })->unique()->filter()->count();

            // Hitung tugas aktif (assignment dengan status active) dari semua MK yg diikuti
            $activeAssignments = 0;
            foreach ($myCourses as $c) {
                $activeAssignments += $c->assignments()->whereIn('status', ['active', 'published'])->count();
            }

            $stats = [
                'mata_kuliah' => $myCourses->count(),
                'semester'    => $semesterCount,
                'total_sks'   => $myCourses->sum('sks'),
                'tugas_aktif' => $activeAssignments,
            ];
            $courses = $myCourses;

            $recentSubmissions = Submission::where('user_id', $activeUser->id)
                ->with(['assignment.course', 'grade'])
                ->latest('submitted_at')
                ->take(5)
                ->get();
        }

        return view('dashboard', compact('role', 'activeUser', 'stats', 'courses', 'allUsers', 'recentSubmissions'));
    }

    /**
     * Menyetel role simulasi ke session lalu redirect ke dashboard (Khusus Demo / Testing).
     */
    public function setRole(string $role)
    {
        $allowed = ['admin', 'dosen', 'mahasiswa'];
        if (! in_array($role, $allowed)) {
            abort(404);
        }

        $user = User::where('role', $role)->first();
        if ($user) {
            Auth::login($user);
            session(['demo_role' => $role]);
        }

        return redirect()->route('dashboard')
            ->with('success', "Akun aktif dialihkan ke peran: {$role} ({$user->name})");
    }
}
