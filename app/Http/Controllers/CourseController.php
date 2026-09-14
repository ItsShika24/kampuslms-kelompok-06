<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // Menampilkan daftar seluruh mata kuliah dari database.
    public function index()
    {
        $courses = Course::with('lecturer')->get();

        return view('courses.index', compact('courses'));
    }

    // Menampilkan detail satu mata kuliah berdasarkan ID.
    public function show($mataKuliah)
    {
        $course = Course::with('lecturer')
            ->findOrFail($mataKuliah);

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

    // Menyimpan mata kuliah baru ke database.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'lecturer_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:draft,active,archived'],
        ]);

        Course::create($validated);

        return redirect()
            ->route('mata-kuliah.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengubah mata kuliah.
    public function edit($mataKuliah)
    {
        $course = Course::findOrFail($mataKuliah);

        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

// Memperbarui data mata kuliah di database.
public function update(Request $request, $mataKuliah)
{
    $course = Course::findOrFail($mataKuliah);

    $validated = $request->validate([
        'code' => [
            'required',
            'string',
            'max:255',
            'unique:courses,code,' . $course->id,
        ],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'sks' => ['required', 'integer', 'min:1', 'max:6'],
        'lecturer_id' => ['required', 'exists:users,id'],
        'status' => ['required', 'in:draft,active,archived'],
    ]);

    $course->update($validated);

    return redirect()
        ->route('mata-kuliah.index')
        ->with('success', 'Mata kuliah berhasil diperbarui.');
}

// Menghapus mata kuliah dari database.
public function destroy($mataKuliah)
{
    $course = Course::findOrFail($mataKuliah);

    $course->delete();

    return redirect()
        ->route('mata-kuliah.index')
        ->with('success', 'Mata kuliah berhasil dihapus.');
}
}