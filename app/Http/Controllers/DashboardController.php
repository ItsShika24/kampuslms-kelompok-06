<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard sesuai role pengguna aktif.
     * MINGGU 11 (Optimisasi Performa - Shabrina Putri Azzahra):
     * - Mengganti count() in-memory dengan withCount() untuk menghemat RAM & kueri SQL.
     * - Menerapkan Cache::remember() untuk ringkasan agregasi statistik (TTL: 10 menit).
     * - Memastikan data submission & nilai TIDAK di-cache demi konsistensi data akademik.
     */
    public function index(Request $request)
    {
        // Ambil pengguna yang terotentikasi nyata (Auth Guard Laravel)
        $activeUser = auth()->user();
        if (!$activeUser) {
            $role = session('demo_role', 'mahasiswa');
            $activeUser = User::where('role', $role)->first() ?? User::first();
        }

        $role = $activeUser?->role ?? session('demo_role', 'mahasiswa');
        session(['demo_role' => $role]);

        // Hitung statistik & data sesuai role
        $stats  = [];
        $courses = collect();
        $recentSubmissions = collect();

        if ($role === 'admin') {
            $allCourses = Course::with('lecturer')->withCount('students')->get();

            // MINGGU 11: Caching statistik ringkasan admin selama 10 menit
            $stats = Cache::remember("dashboard.stats.user.{$activeUser->id}", now()->addMinutes(10), function () use ($allCourses) {
                // Semester unik: digit ratusan dari kode MK (SI101 → 1, SI201 → 2, dst.)
                $semesterCount = $allCourses->map(function ($c) {
                    preg_match('/\d+/', $c->code, $m);
                    return $m ? (int) floor((int) $m[0] / 100) : 0;
                })->unique()->filter()->count();

                return [
                    'mata_kuliah'     => $allCourses->count(),
                    'semester'        => 'Ganjil',
                    'total_sks'       => $allCourses->sum('sks'),
                    'total_dosen'     => User::where('role', 'dosen')->count(),
                    'total_mahasiswa' => User::where('role', 'mahasiswa')->count(),
                ];
            });

            $courses  = $allCourses;
            $allUsers = User::orderBy('role')->orderBy('name')->get();
            $recentSubmissions = Submission::with(['student', 'assignment.course', 'grade'])
                ->latest('submitted_at')
                ->take(5)
                ->get();

        } elseif ($role === 'dosen' && $activeUser) {
            // MINGGU 11: Gunakan withCount('students') bukan with(['students']) agar tidak memuat ribuan model ke RAM
            $myCourses = Course::withCount('students')
                ->where('lecturer_id', $activeUser->id)
                ->get();

            // MINGGU 11: Caching statistik ringkasan dosen selama 10 menit
            $stats = Cache::remember("dashboard.stats.user.{$activeUser->id}", now()->addMinutes(10), function () use ($myCourses) {
                $semesterCount = $myCourses->map(function ($c) {
                    preg_match('/\d+/', $c->code, $m);
                    return $m ? (int) floor((int) $m[0] / 100) : 0;
                })->unique()->filter()->count();

                return [
                    'mata_kuliah'     => $myCourses->count(),
                    'semester'        => 'Ganjil',
                    'total_sks'       => $myCourses->sum('sks'),
                    'total_mahasiswa' => $myCourses->sum('students_count'),
                ];
            });

            $courses  = $myCourses;
            $allUsers = collect();

            $courseIds = $myCourses->pluck('id');
            // MINGGU 11: Submission & nilai DILARANG di-cache (wajib realtime agar update nilai dosen langsung terlihat)
            $recentSubmissions = Submission::whereHas('assignment', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })->with(['student', 'assignment.course', 'grade'])
              ->latest('submitted_at')
              ->take(5)
              ->get();

        } else {
            // Mahasiswa
            // MINGGU 11: Eager loading withCount tugas aktif agar tidak ada N+1 query loop di Blade
            $myCourses = $activeUser
                ? $activeUser->courses()
                    ->with('lecturer')
                    ->withCount([
                        'assignments as active_assignments_count' => function ($q) {
                            $q->where('status', 'active');
                        },
                        'assignments as pending_assignments_count' => function ($q) use ($activeUser) {
                            $q->where('status', 'active')
                              ->whereDoesntHave('submissions', function ($sub) use ($activeUser) {
                                  $sub->where('user_id', $activeUser->id);
                              });
                        },
                    ])
                    ->get()
                : collect();

            // MINGGU 11: Caching statistik ringkasan mahasiswa selama 10 menit
            $cacheKey = $activeUser ? "dashboard.stats.user.{$activeUser->id}" : 'dashboard.stats.guest';
            $stats = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($myCourses) {
                $semesterCount = $myCourses->map(function ($c) {
                    preg_match('/\d+/', $c->code, $m);
                    return $m ? (int) floor((int) $m[0] / 100) : 0;
                })->unique()->filter()->count();

                return [
                    'mata_kuliah' => $myCourses->count(),
                    'semester'    => 'Ganjil',
                    'total_sks'   => $myCourses->sum('sks'),
                    'tugas_aktif' => $myCourses->sum('pending_assignments_count'),
                ];
            });

            $courses  = $myCourses;
            $allUsers = collect();

            // MINGGU 11: Nilai mahasiswa DILARANG di-cache demi konsistensi data akademik
            $recentSubmissions = $activeUser
                ? Submission::where('user_id', $activeUser->id)
                    ->with(['assignment.course', 'grade'])
                    ->latest('submitted_at')
                    ->take(5)
                    ->get()
                : collect();
        }

        return view('dashboard', compact('role', 'activeUser', 'stats', 'courses', 'allUsers', 'recentSubmissions'));
    }

}
