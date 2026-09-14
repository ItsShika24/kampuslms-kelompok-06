<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard sesuai role pengguna aktif.
     * Role dibaca dari session (simulasi login).
     */
    public function index(Request $request)
    {
        // Ambil role dari session; default 'mahasiswa'
        $role = session('demo_role', 'mahasiswa');

        // Ambil user demo sesuai role dari session
        $demoEmails = [
            'admin'     => 'admin@kampuslms.test',
            'dosen'     => 'dosen@kampuslms.test',
            'mahasiswa' => 'mahasiswa@kampuslms.test',
        ];
        $activeUser = User::where('email', $demoEmails[$role] ?? $demoEmails['mahasiswa'])->first();

        // Hitung statistik & data sesuai role
        $stats  = [];
        $courses = collect();

        if ($role === 'admin') {
            $allCourses = Course::with('lecturer')->get();

            // Semester unik: digit ratusan dari kode MK (SI101 → 1, SI201 → 2, dst.)
            $semesterCount = $allCourses->map(function ($c) {
                preg_match('/\d+/', $c->code, $m);
                return $m ? (int) floor((int) $m[0] / 100) : 0;
            })->unique()->filter()->count();

            $stats = [
                'mata_kuliah'  => $allCourses->count(),
                'semester'     => $semesterCount,
                'total_sks'    => $allCourses->sum('sks'),
                'total_dosen'  => User::where('role', 'dosen')->count(),
                'total_mahasiswa' => User::where('role', 'mahasiswa')->count(),
            ];
            $courses  = $allCourses;
            $allUsers = User::orderBy('role')->orderBy('name')->get();

        } elseif ($role === 'dosen' && $activeUser) {
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
            $courses  = $myCourses;
            $allUsers = collect();

        } else {
            // Mahasiswa
            $myCourses = $activeUser
                ? $activeUser->courses()->with('lecturer')->get()
                : collect();

            $semesterCount = $myCourses->map(function ($c) {
                preg_match('/\d+/', $c->code, $m);
                return $m ? (int) floor((int) $m[0] / 100) : 0;
            })->unique()->filter()->count();

            // Hitung tugas aktif (assignment dengan status active) dari semua MK yg diikuti
            $activeAssignments = 0;
            foreach ($myCourses as $c) {
                $activeAssignments += $c->assignments()->where('status', 'active')->count();
            }

            $stats = [
                'mata_kuliah'      => $myCourses->count(),
                'semester'         => $semesterCount,
                'total_sks'        => $myCourses->sum('sks'),
                'tugas_aktif'      => $activeAssignments,
            ];
            $courses  = $myCourses;
            $allUsers = collect();
        }

        return view('dashboard', compact('role', 'activeUser', 'stats', 'courses', 'allUsers'));
    }

    /**
     * Menyetel role simulasi ke session lalu redirect ke dashboard.
     * Hanya untuk demo/development — tidak dipakai di production.
     */
    public function setRole(string $role)
    {
        $allowed = ['admin', 'dosen', 'mahasiswa'];
        if (!in_array($role, $allowed)) {
            abort(404);
        }

        session(['demo_role' => $role]);

        return redirect()->route('dashboard')
            ->with('success', "Role berhasil diubah ke: {$role}");
    }
}
