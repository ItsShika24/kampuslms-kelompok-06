<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    /**
     * Menentukan apakah pengguna boleh melihat detail nilai.
     * - Admin: Boleh melihat semua nilai.
     * - Mahasiswa: HANYA boleh melihat nilainya sendiri (tidak boleh melihat nilai mahasiswa lain).
     * - Dosen: Boleh melihat nilai pada mata kuliah miliknya.
     */
    public function view(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($grade->submission->user_id === $user->id) {
            return true;
        }

        if ($user->role === 'dosen') {
            return $grade->submission->assignment->course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh membuat nilai baru.
     */
    public function create(User $user, Submission $submission): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $submission->assignment->course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna boleh memperbarui nilai.
     */
    public function update(User $user, Grade $grade): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $grade->submission->assignment->course->lecturer_id === $user->id);
    }
}
