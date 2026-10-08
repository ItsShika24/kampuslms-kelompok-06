<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar materi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail materi.
     * - Admin: Boleh melihat semua materi.
     * - Dosen: Boleh jika materi milik mata kuliah yang diampu.
     * - Mahasiswa: Boleh jika terdaftar pada mata kuliah materi bersangkutan.
     */
    public function view(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $material->course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $material->course->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menambahkan materi baru ke dalam mata kuliah.
     * - Admin: Boleh.
     * - Dosen: Boleh HANYA untuk mata kuliah miliknya sendiri.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data materi.
     */
    public function update(User $user, Material $material): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $material->course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus materi.
     */
    public function delete(User $user, Material $material): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $material->course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna berhak mengunduh file materi pembelajaran.
     * Mencegah mahasiswa luar kelas mengunduh materi privat/soal ujian.
     */
    public function download(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $material->course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $material->course->students()->whereKey($user->id)->exists();
        }

        return false;
    }
}
