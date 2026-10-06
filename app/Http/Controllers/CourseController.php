<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // Menampilkan daftar seluruh mata kuliah dari database dengan pencarian, filter, dan pagination.
    public function index(Request $request)
    {
        $courses = Course::query()
            ->with('lecturer')
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

    // Menampilkan detail satu mata kuliah berdasarkan model Course.
    public function show(Course $course)
    {
        $course->load('lecturer');

        $assignments = $course->assignments()->orderBy('due_at')->get();
        $materials   = $course->materials()->orderBy('created_at', 'desc')->get();

        return view('courses.show', compact('course', 'assignments', 'materials'));
    }

    // Menampilkan form untuk menambahkan mata kuliah.
    public function create()
    {
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    // Menyimpan mata kuliah baru ke database menggunakan Form Request dan pola PRG.
    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        if ($request->user()->role === 'dosen') {
            $validated['lecturer_id'] = $request->user()->id;
        }

        Course::create($validated);

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengubah mata kuliah.
    public function edit(Request $request, Course $course)
    {
        $this->ensureCanManageCourse($request, $course);
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    // Memperbarui data mata kuliah di database menggunakan Form Request dan pola PRG.
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->ensureCanManageCourse($request, $course);
        $validated = $request->validated();

        if ($request->user()->role === 'dosen') {
            $validated['lecturer_id'] = $request->user()->id;
        }

        $course->update($validated);

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    // Menghapus mata kuliah dari database menggunakan pola PRG.
    public function destroy(Request $request, Course $course)
    {
        $this->ensureCanManageCourse($request, $course);
        $course->delete();

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }

    private function ensureCanManageCourse(Request $request, Course $course): void
    {
        abort_unless(
            $request->user()->role === 'admin' || $course->lecturer_id === $request->user()->id,
            403
        );
    }
}