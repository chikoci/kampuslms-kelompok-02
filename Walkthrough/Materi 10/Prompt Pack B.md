# Implementasi Event, Queue, dan Notifikasi Asinkron (Minggu 10 - Prompt Pack B)
**Proyek: KampusLMS (Laravel 12) | Milestone M3 (Tugas 3)**

Dokumen ini mencatat detail teknis implementasi arsitektur asinkron di Laravel 12 sesuai spesifikasi Prompt Pack B dan rubrik evaluasi Tugas 3.

---

## 1. Konfigurasi Antrean Database (`QUEUE_CONNECTION=database`)

Antrean dikonfigurasi menggunakan driver database pada `.env` dan `.env.example`:
```env
QUEUE_CONNECTION=database
```

Tabel antrean dan pencatatan kegagalan telah disiapkan melalui migrasi bawaan Laravel 12:
1. `0001_01_01_000002_create_jobs_table.php`: Menghasilkan tabel `jobs`, `job_batches`, dan `failed_jobs`.
2. `2026_09_14_000007_create_notifications_table.php`: Menghasilkan tabel `notifications` untuk menyimpan riwayat notifikasi.

Perintah artisan yang setara pada Laravel 12:
```bash
php artisan make:queue-table
php artisan make:queue-failed-table
php artisan make:notifications-table
php artisan migrate
```

Cara menjalankan worker pemroses antrean:
```bash
php artisan queue:work
```

---

## 2. Event dan Listener dengan `implements ShouldQueue`

Terdapat 2 pasang Event dan Listener yang bertugas menangani alur asinkron:

### A. Peristiwa Tugas Baru Diterbitkan
* **Event:** `App\Events\AssignmentPublished`
* **Listener:** `App\Listeners\SendAssignmentNotification` (`implements ShouldQueue`)
* **Pencegahan N+1:** Mengambil seluruh mahasiswa terdaftar sekaligus menggunakan eager querying:
  ```php
  $students = $event->assignment->course->students()->get();

  if ($students->isNotEmpty()) {
      Notification::send($students, new NewAssignmentNotification($event->assignment));
  }
  ```

### B. Peristiwa Pengumpulan Tugas Dinilai
* **Event:** `App\Events\SubmissionGraded`
* **Listener:** `App\Listeners\SendGradeNotification` (`implements ShouldQueue`)
* **Pengiriman Notifikasi:**
  ```php
  $student = $event->submission->student ?? $event->submission->user;

  if ($student) {
      $student->notify(new SubmissionGradedNotification($event->submission));
  }
  ```

---

## 3. Penanganan Kegagalan Job (*Failure Handling*)

Setiap Listener dilengkapi dengan kebijakan percobaan ulang (*retry policy*) dan pencatatan log kegagalan:
```php
public int $tries = 3;    // Mengulang maksimal 3 kali jika terjadi exception
public int $backoff = 10; // Jeda 10 detik antar percobaan

public function failed(\Throwable $exception): void
{
    Log::error('Deskripsi kegagalan job...', [
        'error' => $exception->getMessage(),
        'file'  => $exception->getFile(),
        'line'  => $exception->getLine(),
    ]);
}
```
Jika job gagal sebanyak 3 kali, Laravel otomatis memindahkannya ke tabel `failed_jobs` agar dapat diaudit atau dijalankan ulang secara manual lewat `php artisan queue:retry all`.

---

## 4. Keamanan Payload Notifikasi (Bebas Data Sensitif)

Payload notifikasi yang disimpan pada kolom `data` di tabel `notifications` **secara ketat tidak memuat nilai angka (`score`) maupun catatan evaluasi dosen (`feedback`)**:

```php
// App\Notifications\SubmissionGradedNotification
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
```
Mahasiswa diarahkan membuka halaman detail tugas berotorisasi untuk melihat nilai dan catatan umpan balik.

---

## 5. Komponen Lonceng Navbar & Penandaan Sudah Dibaca

### A. Lonceng Interaktif di Navbar (`resources/views/components/layout.blade.php`)
Navbar menampilkan ikon lonceng 🔔 lengkap dengan badge jumlah pesan belum dibaca (`unreadNotifications->count()`). Polling otomatis berjalan setiap 30 detik via JavaScript tanpa perlu me-refresh halaman:
```javascript
setInterval(function () {
    fetch('{{ route('notifications.unreadCount') }}')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            if (badge && typeof data.unread_count !== 'undefined') {
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}, 30000);
```

### B. Proteksi Anti-IDOR pada Penandaan Notifikasi (`NotificationController`)
Mahasiswa hanya dapat menandai atau menghapus notifikasi miliknya sendiri:
```php
public function markAsRead(string $id): RedirectResponse
{
    // Menggunakan query berakar dari user yang sedang login
    $notification = auth()->user()->notifications()->where('id', $id)->firstOrFail();
    $notification->markAsRead();

    return back()->with('success', 'Notifikasi berhasil ditandai sebagai sudah dibaca.');
}
```
Upaya memanggil ID notifikasi milik mahasiswa lain akan menghasilkan respons **HTTP 404 Not Found**.
