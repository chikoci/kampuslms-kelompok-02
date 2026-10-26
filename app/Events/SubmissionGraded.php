<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionGraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Membuat instance event baru saat tugas dinilai oleh dosen.
     */
    public function __construct(public Submission $submission)
    {
    }
}
