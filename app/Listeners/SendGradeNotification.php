<?php

namespace App\Listeners;

use App\Events\GradeSubmitted;
use App\Notifications\GradeGivenNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener antrean untuk mengirim notifikasi penilaian kepada mahasiswa pemilik tugas.
 */
class SendGradeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Jumlah percobaan maksimal jika terjadi kegagalan.
     */
    public int $tries = 3;

    /**
     * Waktu tunda (detik) sebelum job dicoba kembali.
     */
    public int $backoff = 10;

    /**
     * Menangani event penilaian tugas.
     */
    public function handle(GradeSubmitted $event): void
    {
        $submission = $event->submission;
        $student = $submission->student ?? $submission->user;

        if ($student) {
            $student->notify(new GradeGivenNotification($event->grade, $submission));
        }
    }

    /**
     * Ditangani saat job gagal setelah seluruh percobaan habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendGradeNotification gagal diproses di worker queue', [
            'error'   => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ]);
    }
}
