<?php

namespace App\Notifications;

use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi untuk mahasiswa ketika ada tugas baru yang dipublikasikan.
 * Mengimplementasikan ShouldQueue agar pengiriman berjalan asinkron via database queue.
 */
class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Assignment $assignment) {}

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
     * KEAMANAN PAYLOAD (Modul Minggu 10 Poin 8):
     * Hanya menyimpan identitas publik tugas tanpa membocorkan data sensitif.
     */
    public function toDatabase(object $notifiable): array
    {
        $courseName = $this->assignment->course?->name ?? 'Mata Kuliah';
        $dueAtFormatted = $this->assignment->due_at
            ? Carbon::parse($this->assignment->due_at)->translatedFormat('d M Y, H:i')
            : null;

        return [
            'assignment_id' => $this->assignment->id,
            'course_name'   => $courseName,
            'title'         => $this->assignment->title,
            'due_at'        => $dueAtFormatted,
            'type'          => 'assignment_published',
            'message'       => "Tugas baru '{$this->assignment->title}' telah diterbitkan pada mata kuliah {$courseName}.",
            'url'           => route('tugas.show', $this->assignment->id, false),
        ];
    }
}
