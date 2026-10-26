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
     * Mengambil mahasiswa terdaftar sekaligus (eager loading) untuk menghindari N+1.
     */
    public function handle(AssignmentPublished $event): void
    {
        // Ambil mahasiswa terdaftar dari course terkait secara efisien
        $students = $event->assignment->course->students()->get();

        if ($students->isNotEmpty()) {
            Notification::send($students, new NewAssignmentNotification($event->assignment));
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
