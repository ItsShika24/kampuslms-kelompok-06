<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Siapa saja yang terautentikasi dapat melihat daftar mata kuliah.
     * Catatan: Penyaringan daftar dilakukan di level query di Controller (CourseController@index)
     * agar mahasiswa hanya melihat MK yang diikutinya dan dosen hanya melihat MK miliknya.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail mata kuliah tertentu.
     * - Admin: Boleh melihat seluruh mata kuliah.
     * - Dosen: Boleh melihat jika merupakan dosen pengampu mata kuliah tersebut.
     * - Mahasiswa: Boleh melihat jika terdaftar (enrolled) pada mata kuliah tersebut.
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
     * Menentukan apakah pengguna dapat membuat mata kuliah baru.
     * - Berdasarkan Kontrak Spesifikasi Bagian 3: Hanya Administrator yang berhak membuat mata kuliah.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data mata kuliah.
     * - Admin: Boleh mengubah seluruh mata kuliah.
     * - Dosen: Boleh mengubah HANYA mata kuliah yang diampunya sendiri (mencegah IDOR Dosen A ke Dosen B).
     */
    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus mata kuliah.
     * - Hanya Administrator yang berhak menghapus mata kuliah.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }
}
