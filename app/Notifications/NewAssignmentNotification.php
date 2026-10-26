<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification
{
    use Queueable;

    /**
     * Membuat instance notifikasi tugas baru.
     */
    public function __construct(public Assignment $assignment)
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
     * Payload aman: hanya informasi publik tugas, tanpa data sensitif.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'course_id'     => $this->assignment->course_id,
            'course_name'   => $this->assignment->course->name,
            'title'         => $this->assignment->title,
            'due_at'        => $this->assignment->due_at?->toIso8601String(),
            'url'           => route('assignments.show', $this->assignment),
            'type'          => 'assignment_published',
        ];
    }
}
