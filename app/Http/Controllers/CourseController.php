<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
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

    // Menampilkan daftar seluruh mata kuliah dari database dengan pencarian, filter, dan pagination.
    public function index(Request $request)
    {
        $user = $this->syncAuthUser();

        $query = Course::query()->with('lecturer');

        // Jika mahasiswa, hanya tampilkan mata kuliah yang diikutinya (atau semua jika tidak dibatasi)
        if ($user && $user->role === 'mahasiswa' && $request->boolean('enrolled_only', false)) {
            $query->whereHas('students', fn($q) => $q->where('users.id', $user->id));
        }

        // Jika dosen dan memfilter MK miliknya
        if ($user && $user->role === 'dosen' && $request->boolean('my_courses', false)) {
            $query->where('lecturer_id', $user->id);
        }

        $courses = $query
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('code', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('status'), fn ($query) =>
                $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    // Menampilkan detail satu mata kuliah berdasarkan model Course (Route Model Binding).
    public function show(Course $course)
    {
        $user = $this->syncAuthUser();
        $role = session('demo_role', $user?->role ?? 'mahasiswa');

        $course->load('lecturer');

        $assignments = $course->assignments()
            ->with(['submissions' => function ($q) use ($user, $role) {
                if ($role === 'mahasiswa' && $user) {
                    $q->where('user_id', $user->id)->with('grade');
                } else {
                    $q->with(['student', 'grade']);
                }
            }])
            ->orderBy('due_at')
            ->get();

        $materials   = $course->materials()->orderBy('created_at', 'desc')->get();

        return view('courses.show', compact('course', 'assignments', 'materials'));
    }

    // Menampilkan form untuk menambahkan mata kuliah (Hanya Admin).
    public function create()
    {
        $user = $this->syncAuthUser();

        abort_unless(
            $user && $user->role === 'admin',
            403,
            'Akses Ditolak: Hanya administrator yang berhak menambahkan mata kuliah baru.'
        );

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    // Menyimpan mata kuliah baru ke database menggunakan Form Request dan pola PRG.
    public function store(StoreCourseRequest $request)
    {
        $user = $this->syncAuthUser();

        abort_unless(
            $user && $user->role === 'admin',
            403,
            'Akses Ditolak: Hanya administrator yang berhak menambahkan mata kuliah baru.'
        );

        Course::create($request->validated());

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengubah mata kuliah (Admin atau Dosen Pengampu MK bersangkutan).
    public function edit(Course $course)
    {
        $user = $this->syncAuthUser();

        // Mitigasi IDOR Lapis 1 (abort_unless): Mencegah Dosen A mengedit MK milik Dosen B
        abort_unless(
            $user && ($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id)),
            403,
            'Akses Ditolak: Anda tidak memiliki wewenang untuk mengedit mata kuliah ini.'
        );

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    // Memperbarui data mata kuliah di database menggunakan Form Request dan pola PRG.
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $user = $this->syncAuthUser();

        // Mitigasi IDOR Lapis 1 (abort_unless): Mencegah Dosen A memperbarui MK milik Dosen B
        abort_unless(
            $user && ($user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id)),
            403,
            'Akses Ditolak: Anda tidak memiliki wewenang untuk memperbarui mata kuliah ini.'
        );

        $course->update($request->validated());

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    // Menghapus mata kuliah dari database menggunakan pola PRG (Hanya Admin).
    public function destroy(Course $course)
    {
        $user = $this->syncAuthUser();

        abort_unless(
            $user && $user->role === 'admin',
            403,
            'Akses Ditolak: Hanya administrator yang berhak menghapus mata kuliah.'
        );

        $course->delete();

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}