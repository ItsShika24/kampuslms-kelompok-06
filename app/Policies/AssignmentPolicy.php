<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar tugas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail tugas.
     * - Admin: Boleh melihat semua tugas.
     * - Dosen: Boleh jika tugas milik mata kuliah yang diampu.
     * - Mahasiswa: Boleh jika terdaftar di mata kuliah terkait DAN tugas berstatus aktif/published
     *   (Mahasiswa dilarang melihat tugas yang masih berstatus draft).
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $assignment->course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            $isEnrolled = $assignment->course->students()->whereKey($user->id)->exists();
            $isPublished = in_array($assignment->status, ['active', 'published']);

            return $isEnrolled && $isPublished;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat membuat tugas baru pada suatu mata kuliah.
     * - Admin: Boleh.
     * - Dosen: Boleh HANYA untuk mata kuliah yang diampu (mencegah Dosen A membuat tugas di MK Dosen B).
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna dapat mengubah data/tenggat tugas.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus tugas.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah mahasiswa berhak mengumpulkan jawaban tugas.
     * Syarat: Peran mahasiswa dan terdaftar (enrolled) di kelas mata kuliah terkait.
     */
    public function submit(User $user, Assignment $assignment): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        return $assignment->course->students()->whereKey($user->id)->exists();
    }

    /**
     * Menentukan apakah pengguna boleh melihat seluruh submission mahasiswa pada tugas ini.
     * - Admin: Boleh.
     * - Dosen: Boleh jika pengampu mata kuliah terkait.
     * - Mahasiswa: Dilarang (mencegah kebocoran jawaban mahasiswa lain).
     */
    public function viewSubmissions(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $assignment->course->lecturer_id === $user->id);
    }
}
