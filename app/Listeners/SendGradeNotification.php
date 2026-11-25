<?php

namespace App\Listeners;

use App\Events\SubmissionGraded;
use App\Notifications\SubmissionGradedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendGradeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Jumlah percobaan maksimal jika job mengalami kegagalan.
     */
    public int $tries = 3;

    /**
     * Waktu tunda (dalam detik) sebelum mencoba kembali job yang gagal.
     */
    public int $backoff = 10;

    /**
     * Menangani event penilaian submission.
     * Mengirim notifikasi database ke mahasiswa pemilik submission.
     */
    public function handle(SubmissionGraded $event): void
    {
        $student = $event->submission->student ?? $event->submission->user;

        if ($student) {
            $student->notify(new SubmissionGradedNotification($event->submission));
        }
    }

    /**
     * Menangani kasus ketika job gagal setelah seluruh percobaan habis.
     */
    public function failed(SubmissionGraded $event, \Throwable $exception): void
    {
        Log::error('SendGradeNotification: Pengiriman notifikasi nilai submission gagal diproses worker.', [
            'submission_id' => $event->submission->id ?? null,
            'error'         => $exception->getMessage(),
            'file'          => $exception->getFile(),
            'line'          => $exception->getLine(),
        ]);
    }
}
