<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Dosen: form tambah materi pada MK yang diampu.
     */
    public function create(Course $course)
    {
        return view('materials.create', compact('course'));
    }

    /**
     * Dosen: simpan materi baru.
     */
    public function store(Request $request, Course $course)
    {
        // Ambil user dosen secara dinamis berdasarkan role
        $dosen = User::where('role', 'dosen')->firstOrFail();

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'type'         => ['required', 'in:file,link,text'],
            'external_url' => ['nullable', 'url', 'required_if:type,link'],
            'file'         => ['nullable', 'file', 'max:51200', 'required_if:type,file'], // maks 50 MB
        ]);

        $filePath     = null;
        $originalName = null;
        $fileSize     = null;
        $mimeType     = null;

        if ($validated['type'] === 'file' && $request->hasFile('file')) {
            $uploaded     = $request->file('file');
            $filePath     = $uploaded->store('materials', 'public');
            $originalName = $uploaded->getClientOriginalName();
            $fileSize     = $uploaded->getSize();
            $mimeType     = $uploaded->getMimeType();
        }

        Material::create([
            'course_id'    => $course->id,
            'uploaded_by'  => $dosen->id,
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'type'         => $validated['type'],
            'file_path'    => $filePath,
            'original_name'=> $originalName,
            'file_size'    => $fileSize,
            'mime_type'    => $mimeType,
            'external_url' => $validated['type'] === 'link' ? ($validated['external_url'] ?? null) : null,
        ]);

        return redirect()
            ->route('mata-kuliah.show', $course->id)
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    /**
     * Mahasiswa / Semua role: lihat / download file materi.
     */
    public function download(Material $material)
    {
        if ($material->type === 'link' && $material->external_url) {
            return redirect($material->external_url);
        }

        if ($material->type === 'file' && $material->file_path) {
            return Storage::disk('public')->download(
                $material->file_path,
                $material->original_name ?? basename($material->file_path)
            );
        }

        return back()->with('error', 'File tidak tersedia.');
    }

    /**
     * Dosen: hapus materi.
     */
    public function destroy(Material $material)
    {
        $courseId = $material->course_id;

        if ($material->file_path) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return redirect()
            ->route('mata-kuliah.show', $courseId)
            ->with('success', 'Materi berhasil dihapus.');
    }
}
