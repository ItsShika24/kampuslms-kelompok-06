<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Menentukan apakah pengguna boleh melihat daftar submission.
     * Penyaringan daftar dilakukan di query controller.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail satu submission jawaban.
     * INI MENUTUP LUBANG IDOR UTAMA:
     * - Mahasiswa HANYA boleh melihat jawaban miliknya sendiri (user_id === user->id).
     * - Dosen HANYA boleh melihat submission pada mata kuliah yang diampunya.
     * - Admin boleh melihat semua submission.
     * - Mahasiswa lain yang mencoba menebak URL ID akan DITOLAK (403 Forbidden).
     */
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($submission->user_id === $user->id) {
            return true;
        }

        if ($user->role === 'dosen') {
            return $submission->assignment->course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah mahasiswa berhak membuat submission.
     */
    public function create(User $user, Assignment $assignment): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        return $assignment->course->students()->whereKey($user->id)->exists();
    }

    /**
     * Menentukan apakah pengguna berhak memberi/mengubah nilai pada submission ini.
     * Menutup IDOR: Dosen A dilarang menilai tugas pada mata kuliah milik Dosen B.
     */
    public function grade(User $user, Submission $submission): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'dosen' && $submission->assignment->course->lecturer_id === $user->id);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus submission.
     */
    public function delete(User $user, Submission $submission): bool
    {
        return $user->role === 'admin';
    }
}
