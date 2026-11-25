<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendAssignmentNotification implements ShouldQueue
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
     * Menangani event publikasi tugas baru.
     * Memastikan status tugas masih published di database sebelum mengirim notifikasi ke mahasiswa terdaftar.
     */
    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment->fresh(['course']);

        if (! $assignment || $assignment->status !== 'published' || ! $assignment->course) {
            return;
        }

        // Ambil mahasiswa terdaftar dari course terkait secara efisien
        $students = $assignment->course->students()->get();

        if ($students->isNotEmpty()) {
            Notification::send($students, new NewAssignmentNotification($assignment));
        }
    }

    /**
     * Menangani kasus ketika job gagal setelah seluruh percobaan habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAssignmentNotification: Pengiriman notifikasi tugas baru gagal diproses worker.', [
            'error'     => $exception->getMessage(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
        ]);
    }
}
