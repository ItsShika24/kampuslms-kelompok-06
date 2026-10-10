<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Boleh melihat daftar materi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna boleh melihat detail materi.
     * - Admin: Boleh melihat semua materi.
     * - Dosen: Boleh melihat materi pada mata kuliah yang diampunya.
     * - Mahasiswa: Hanya boleh melihat materi pada mata kuliah yang diikutinya.
     */
    public function view(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $material->course;
        if (!$course) {
            return false;
        }

        if ($user->role === 'dosen') {
            return $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh mengunggah / membuat materi baru.
     * - Admin: Boleh.
     * - Dosen: Hanya boleh jika ia mengampu mata kuliah terkait.
     */
    public function create(User $user, ?Course $course = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $course ? $course->lecturer_id === $user->id : true;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh memperbarui materi.
     * - Admin: Boleh.
     * - Dosen: Hanya boleh jika materi milik mata kuliah yang diampunya.
     */
    public function update(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $material->course && $material->course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh menghapus materi.
     * - Admin: Boleh.
     * - Dosen: Hanya boleh jika materi milik mata kuliah yang diampunya.
     */
    public function delete(User $user, Material $material): bool
    {
        return $this->update($user, $material);
    }

    /**
     * Menentukan apakah pengguna boleh mengunduh materi.
     */
    public function download(User $user, Material $material): bool
    {
        return $this->view($user, $material);
    }
}
