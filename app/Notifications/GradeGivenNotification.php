<?php

namespace App\Notifications;

use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi untuk mahasiswa ketika dosen memberikan penilaian pada tugasnya.
 * Berjalan di antrean latar belakang (ShouldQueue).
 */
class GradeGivenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Grade $grade,
        public Submission $submission
    ) {}

    /**
     * Saluran pengiriman notifikasi (database).
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Payload notifikasi yang disimpan ke tabel `notifications`.
     * 
     * KEAMANAN & PRIVASI (Modul Minggu 10 Poin 8):
     * DILARANG KERAS menyimpan nilai angka ('score') atau isi umpan balik ('feedback') di sini!
     * Kolom data notifikasi bersifat unencrypted. Mahasiswa diarahkan untuk melihat nilai
     * secara aman di halaman detail submission yang dilindungi oleh Gate & Policy.
     */
    public function toDatabase(object $notifiable): array
    {
        $assignment = $this->submission->assignment;
        $courseName = $assignment?->course?->name ?? 'Mata Kuliah';
        $assignmentTitle = $assignment?->title ?? 'Tugas';

        return [
            'submission_id'    => $this->submission->id,
            'assignment_id'    => $assignment?->id,
            'course_name'      => $courseName,
            'assignment_title' => $assignmentTitle,
            'type'             => 'grade_submitted',
            'message'          => "Nilai untuk tugas '{$assignmentTitle}' pada mata kuliah {$courseName} telah diperbarui oleh dosen.",
            'url'              => route('submissions.show', $this->submission->id),
        ];
    }
}
