<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Listener antrean untuk mengirim notifikasi tugas baru kepada seluruh mahasiswa terdaftar.
 * Berjalan asinkron agar request pembuatan tugas dari dosen tidak lambat / terhambat.
 */
class SendAssignmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Jumlah percobaan maksimal jika terjadi kegagalan pengiriman.
     */
    public int $tries = 3;

    /**
     * Waktu tunda (detik) sebelum job dicoba kembali.
     */
    public int $backoff = 10;

    /**
     * Menangani event publikasi tugas.
     */
    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment;
        $course = $assignment->course;

        if (! $course) {
            return;
        }

        // Hindari query N+1: Ambil seluruh mahasiswa terdaftar dalam satu query
        $students = $course->students()->get();

        if ($students->isNotEmpty()) {
            Notification::send($students, new NewAssignmentNotification($assignment));
        }
    }

    /**
     * Ditangani saat job gagal setelah seluruh percobaan habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAssignmentNotification gagal diproses di worker queue', [
            'error'   => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ]);
    }
}
