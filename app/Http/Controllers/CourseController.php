<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
        $role = $user?->role ?? session('demo_role', 'mahasiswa');

        // Penyaringan di level query sesuai peran login (Modul 7.1)
        $query = (match ($role) {
            'admin'     => Course::query(),
            'dosen'     => ($user && $request->boolean('my_courses', false))
                            ? Course::where('lecturer_id', $user->id)
                            : Course::query(),
            'mahasiswa' => ($user && $request->boolean('enrolled_only', false))
                            ? $user->courses()
                            : Course::query(),
            default     => Course::query(),
        })->with('lecturer');

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

        // Otorisasi via CoursePolicy di Laravel 12
        Gate::authorize('view', $course);

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
        $this->syncAuthUser();
        Gate::authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    // Menyimpan mata kuliah baru ke database menggunakan Form Request dan pola PRG.
    public function store(StoreCourseRequest $request)
    {
        $this->syncAuthUser();
        Gate::authorize('create', Course::class);

        Course::create($request->validated());

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengubah mata kuliah (Admin atau Dosen Pengampu MK bersangkutan).
    public function edit(Course $course)
    {
        $this->syncAuthUser();
        Gate::authorize('update', $course);

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    // Memperbarui data mata kuliah di database menggunakan Form Request dan pola PRG.
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->syncAuthUser();
        Gate::authorize('update', $course);

        $course->update($request->validated());

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    // Menghapus mata kuliah dari database menggunakan pola PRG (Hanya Admin).
    public function destroy(Course $course)
    {
        $this->syncAuthUser();
        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}