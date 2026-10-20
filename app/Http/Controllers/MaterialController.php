<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MaterialController extends Controller
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

    /**
     * Dosen: form tambah materi pada MK yang diampu.
     */
    public function create(Course $course)
    {
        $this->syncAuthUser();

        // Otorisasi via MaterialPolicy
        Gate::authorize('create', [Material::class, $course]);

        return view('materials.create', compact('course'));
    }

    /**
     * Dosen: simpan materi baru.
     */
    public function store(StoreMaterialRequest $request, Course $course)
    {
        $user = $this->syncAuthUser();

        // Otorisasi via MaterialPolicy
        Gate::authorize('create', [Material::class, $course]);

        $validated = $request->validated();

        $filePath = null;
        $originalName = null;
        $fileSize = null;
        $mimeType = null;

        if ($validated['type'] === 'file' && $request->hasFile('file')) {
            $uploaded = $request->file('file');
            $filePath = $uploaded->store('materials', 'local');
            if (! $filePath) {
                throw new RuntimeException('Berkas materi gagal disimpan.');
            }

            $originalName = $uploaded->getClientOriginalName();
            $fileSize = $uploaded->getSize();
            $mimeType = $uploaded->getMimeType();
        }

        Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'file_path' => $filePath,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
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
        $this->syncAuthUser();

        // Otorisasi via MaterialPolicy
        Gate::authorize('download', $material);

        if ($material->type === 'link' && $material->external_url) {
            return redirect($material->external_url);
        }

        if ($material->type === 'file' && $material->file_path) {
            if (Storage::disk('local')->exists($material->file_path)) {
                return Storage::disk('local')->download(
                    $material->file_path,
                    $material->original_name ?? basename($material->file_path)
                );
            }

            return back()->with('error', 'Berkas fisik materi tidak ditemukan atau belum diunggah ke server.');
        }

        return back()->with('error', 'File materi tidak ditemukan.');
    }

    /**
     * Dosen / Admin: hapus materi.
     */
    public function destroy(Material $material)
    {
        $this->syncAuthUser();

        // Otorisasi via MaterialPolicy
        Gate::authorize('delete', $material);

        $courseId = $material->course_id;

        $material->delete();

        return redirect()
            ->route('mata-kuliah.show', $courseId)
            ->with('success', 'Materi berhasil dihapus.');
    }
}
