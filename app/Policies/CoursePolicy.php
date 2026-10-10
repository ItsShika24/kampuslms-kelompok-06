<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Semua pengguna terotentikasi dapat melihat index daftar mata kuliah.
     * Catatan: Data yang ditampilkan wajib difilter di level query controller.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna boleh melihat detail mata kuliah tertentu.
     * - Admin: Boleh melihat semua MK.
     * - Dosen: Hanya boleh melihat MK yang diampunya.
     * - Mahasiswa: Hanya boleh melihat MK yang diikutinya (enrolled).
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
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
     * Menentukan apakah pengguna boleh membuat mata kuliah baru.
     * Hanya Admin dan Dosen.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dosen']);
    }

    /**
     * Menentukan apakah pengguna boleh memperbarui mata kuliah.
     * - Admin: Boleh memperbarui semua MK.
     * - Dosen: Hanya boleh memperbarui MK yang diampunya sendiri.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin' || $course->lecturer_id === $user->id;
    }

    /**
     * Menentukan apakah pengguna boleh menghapus mata kuliah.
     * Hanya Admin.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }
}
