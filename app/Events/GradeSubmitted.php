<?php

namespace App\Events;

use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event yang dipicu ketika dosen memberikan atau memperbarui nilai submission mahasiswa.
 * Membawa model Grade dan Submission terkait.
 */
class GradeSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Grade $grade,
        public Submission $submission
    ) {}
}
