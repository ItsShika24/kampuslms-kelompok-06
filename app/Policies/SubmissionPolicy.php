<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Boleh melihat daftar pengumpulan tugas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna boleh melihat detail submission (KUNCI PENUTUP IDOR).
     * - Admin: Boleh melihat semua submission.
     * - Dosen: Hanya boleh melihat submission tugas dari MK yang diampunya.
     * - Mahasiswa: HANYA boleh melihat submission miliknya sendiri (user_id === $user->id).
     *   Mahasiswa dilarang keras melihat submission milik mahasiswa lain!
     */
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
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
     * Menentukan apakah pengguna boleh membuat pengumpulan tugas.
     */
    public function create(User $user, ?Assignment $assignment = null): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        if ($assignment) {
            $course = $assignment->course;
            return $course && $course->students()->whereKey($user->id)->exists();
        }

        return true;
    }

    /**
     * Menentukan apakah pengguna boleh menilai pengumpulan tugas.
     * Hanya Admin atau Dosen pengampu MK yang boleh menilai. Mahasiswa dilarang menilai!
     */
    public function grade(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            $course = $submission->assignment?->course;
            return $course && $course->lecturer_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna boleh menghapus submission.
     */
    public function delete(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Mahasiswa hanya boleh menghapus submission miliknya jika belum dinilai
        if ($user->role === 'mahasiswa') {
            return $submission->user_id === $user->id && $submission->grade === null;
        }

        return false;
    }
}
