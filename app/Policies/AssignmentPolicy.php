<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Boleh melihat daftar tugas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna boleh melihat detail tugas.
     * - Admin: Boleh melihat semua tugas.
     * - Dosen: Boleh melihat tugas di mata kuliah yang diampunya.
     * - Mahasiswa: Hanya boleh melihat tugas bukan draft pada mata kuliah yang diikutinya.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $assignment->course;
        if (!$course) {
            return false;
        }

        if ($user->role === 'dosen') {
            return $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            // Mahasiswa dilarang melihat tugas yang masih berstatus draft
            if ($assignment->status === 'draft') {
                return false;
            }

            return $course->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh membuat tugas baru.
     * - Admin: Boleh.
     * - Dosen: Hanya boleh jika mengampu mata kuliah tersebut.
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
     * Menentukan apakah pengguna boleh memperbarui tugas.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $assignment->course && $assignment->course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh menghapus tugas.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    /**
     * Menentukan apakah mahasiswa boleh mengumpulkan tugas (submit).
     */
    public function submit(User $user, Assignment $assignment): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        // Mahasiswa dilarang mengumpulkan tugas yang berstatus draft
        if ($assignment->status === 'draft') {
            return false;
        }

        // Jika status tugas ditutup dan tidak mengizinkan pengumpulan terlambat
        if ($assignment->status === 'closed' && ! $assignment->allow_late) {
            return false;
        }

        $course = $assignment->course;
        return $course && $course->students()->whereKey($user->id)->exists();
    }
}
