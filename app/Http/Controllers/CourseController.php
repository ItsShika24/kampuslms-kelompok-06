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
     * Memastikan user terautentikasi (mendukung session login nyata & demo).
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
     * Menampilkan daftar mata kuliah dengan penyaringan ketat di LEVEL QUERY (Bukan di View).
     * - Admin: melihat seluruh mata kuliah.
     * - Dosen: melihat HANYA mata kuliah yang diajarkannya ($user->taughtCourses()).
     * - Mahasiswa: melihat HANYA mata kuliah yang diikutinya ($user->courses()).
     */
    public function index(Request $request)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('viewAny', Course::class);

        // Menutup kebocoran di level query sesuai spesifikasi Modul Minggu 7
        $query = (match ($user->role) {
            'admin'     => Course::query(),
            'dosen'     => $user->taughtCourses(),
            'mahasiswa' => $user->courses(),
            default     => abort(403),
        })->with('lecturer');

        $courses = $query
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('code', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    /**
     * Menampilkan detail satu mata kuliah berdasarkan model Course (Route Model Binding).
     * Dilindungi CoursePolicy::view (Admin, Dosen Pengampu, Mahasiswa Terdaftar).
     */
    public function show(Course $course)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('view', $course);

        $role = $user->role;
        $course->load('lecturer');

        $assignments = $course->assignments()
            ->when($role === 'mahasiswa', fn ($q) => $q->whereIn('status', ['active', 'published']))
            ->with(['submissions' => function ($q) use ($user, $role) {
                if ($role === 'mahasiswa') {
                    $q->where('user_id', $user->id)->with('grade');
                } else {
                    $q->with(['student', 'grade']);
                }
            }])
            ->orderBy('due_at')
            ->get();

        $materials = $course->materials()->orderBy('created_at', 'desc')->get();

        return view('courses.show', compact('course', 'assignments', 'materials'));
    }

    /**
     * Menampilkan form untuk menambahkan mata kuliah (Hanya Admin).
     */
    public function create()
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    /**
     * Menyimpan mata kuliah baru ke database menggunakan Form Request dan pola PRG.
     */
    public function store(StoreCourseRequest $request)
    {
        $this->syncAuthUser();

        Gate::authorize('create', Course::class);

        $course = Course::create($request->validated());

        return redirect()
            ->route('mata-kuliah.show', $course)
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengubah mata kuliah (Admin atau Dosen Pengampu MK bersangkutan).
     * Dilindungi CoursePolicy::update (Menutup IDOR Dosen A ke MK Dosen B).
     */
    public function edit(Course $course)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        Gate::authorize('update', $course);

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    /**
     * Memperbarui data mata kuliah di database menggunakan Form Request dan pola PRG.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->syncAuthUser();

        Gate::authorize('update', $course);

        $course->update($request->validated());

        return redirect()
            ->route('mata-kuliah.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    /**
     * Menghapus mata kuliah dari database menggunakan pola PRG (Hanya Admin).
     */
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