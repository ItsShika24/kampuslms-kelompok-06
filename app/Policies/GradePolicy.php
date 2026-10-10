<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    /**
     * Menentukan apakah pengguna boleh melihat nilai tertentu.
     * - Admin: Boleh.
     * - Dosen: Boleh jika MK diampu dirinya.
     * - Mahasiswa: Hanya boleh melihat nilai dari submission miliknya sendiri.
     */
    public function view(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $submission = $grade->submission;
        if (!$submission) {
            return false;
        }

        if ($user->role === 'dosen') {
            $course = $submission->assignment?->course;
            return $course && $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $submission->user_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh memberi nilai.
     * Hanya Admin atau Dosen pengampu.
     */
    public function create(User $user, ?Submission $submission = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            if (!$submission) {
                return true;
            }
            $course = $submission->assignment?->course;
            return $course && $course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh mengubah nilai.
     */
    public function update(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            $course = $grade->submission?->assignment?->course;
            return $course && $course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh menghapus nilai.
     */
    public function delete(User $user, Grade $grade): bool
    {
        return $this->update($user, $grade);
    }
}
