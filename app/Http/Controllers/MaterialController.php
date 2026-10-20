<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
     * Dosen: simpan materi baru ke disk privat 'local' (Modul 9.1).
     */
    public function store(Request $request, Course $course)
    {
        $user = $this->syncAuthUser();

        // Otorisasi via MaterialPolicy
        Gate::authorize('create', [Material::class, $course]);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'type'         => ['required', 'in:file,link,text'],
            'external_url' => ['nullable', 'url', 'required_if:type,link'],
            // Maks 20 MB (20480 KB) dengan validasi isi magic bytes (Modul 9 BUILD)
            'file'         => ['nullable', 'file', 'mimes:pdf,pptx,ppt,docx,doc', 'max:20480', 'required_if:type,file'],
        ], [
            'file.mimes'   => 'Format berkas harus berupa PDF, PPTX, PPT, DOCX, atau DOC.',
            'file.max'     => 'Ukuran berkas materi maksimal 20 MB.',
            'file.required_if' => 'Berkas materi wajib diunggah untuk tipe file.',
            'external_url.required_if' => 'Tautan eksternal wajib diisi untuk tipe link.',
        ]);

        $filePath     = null;
        $originalName = null;
        $fileSize     = null;
        $mimeType     = null;

        if ($validated['type'] === 'file' && $request->hasFile('file')) {
            $uploaded     = $request->file('file');
            // Simpan ke disk privat 'local' dengan nama acak (Modul 9.1)
            $filePath     = $uploaded->store("materials/{$course->id}", 'local');
            $originalName = $uploaded->getClientOriginalName();
            $fileSize     = $uploaded->getSize();
            $mimeType     = $uploaded->getMimeType();
        }

        Material::create([
            'course_id'    => $course->id,
            'uploaded_by'  => $user->id,
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
            ->with('success', 'Materi berhasil ditambahkan ke penyimpanan privat.');
    }

    /**
     * Mahasiswa terdaftar & Dosen pengampu: unduh berkas materi melalui otorisasi Policy (Modul 9.1).
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
            // Pemeriksaan disk privat 'local'
            if (Storage::disk('local')->exists($material->file_path)) {
                return Storage::disk('local')->download(
                    $material->file_path,
                    $material->original_name ?? basename($material->file_path)
                );
            }

            // Kompatibilitas mundur jika ada file di disk public
            if (Storage::disk('public')->exists($material->file_path)) {
                return Storage::disk('public')->download(
                    $material->file_path,
                    $material->original_name ?? basename($material->file_path)
                );
            }

            abort(404, 'Berkas fisik materi tidak ditemukan di penyimpanan server.');
        }

        return back()->with('error', 'Berkas materi tidak ditemukan.');
    }

    /**
     * Dosen pengampu / Admin: hapus materi dan berkas fisik (Modul 9.1).
     */
    public function destroy(Material $material)
    {
        $this->syncAuthUser();

        // Otorisasi via MaterialPolicy
        Gate::authorize('delete', $material);

        $courseId = $material->course_id;

        // Berkas fisik akan otomatis terhapus oleh event booted deleting di model Material
        $material->delete();

        return redirect()
            ->route('mata-kuliah.show', $courseId)
            ->with('success', 'Materi berhasil dihapus beserta berkas fisiknya.');
    }
}
