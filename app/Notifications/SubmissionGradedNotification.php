<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionGradedNotification extends Notification
{
    use Queueable;

    /**
     * Membuat instance notifikasi nilai pengumpulan tugas.
     */
    public function __construct(public Submission $submission)
    {
    }

    /**
     * Tentukan saluran pengiriman (database).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Simpan representasi array notifikasi ke database (kolom data).
     * ATURAN KEAMANAN KRITIS:
     * Nilai (score) dan catatan feedback DILARANG dimasukkan ke payload ini
     * agar data akademik terlindungi dari akses langsung tabel database.
     * Mahasiswa diarahkan membuka halaman detail yang terproteksi Policy.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'submission_id'    => $this->submission->id,
            'assignment_id'    => $this->submission->assignment_id,
            'course_name'      => $this->submission->assignment->course->name,
            'assignment_title' => $this->submission->assignment->title,
            'graded_at'        => now()->toIso8601String(),
            'url'              => route('assignments.show', $this->submission->assignment_id),
            'type'             => 'submission_graded',
        ];
    }
}
