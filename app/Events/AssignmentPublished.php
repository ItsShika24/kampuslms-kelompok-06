<?php

namespace App\Events;

use App\Models\Assignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event yang dipicu ketika dosen menerbitkan tugas baru pada mata kuliah tertentu.
 * Membawa model Assignment untuk diproses oleh listener di latar belakang (queue).
 */
class AssignmentPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Assignment $assignment) {}
}
