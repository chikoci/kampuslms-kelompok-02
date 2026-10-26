# Hasil Implementasi Tahap BUILD (Minggu 10)
**Milestone M3: Alur Akademik Penuh (Tugas 3 - Bobot 7%)**

## Ringkasan Pencapaian BUILD Minggu 10
Sesuai modul Minggu 10 dan rubrik penilaian Tugas 3, seluruh 8 deliverable Milestone M3 telah berhasil diimplementasikan, diverifikasi, dan diuji secara komprehensif pada aplikasi KampusLMS (Laravel 12):

1. **Event + Listener + Notification untuk Dua Peristiwa:**
   * **Peristiwa 1 (Tugas Baru):** Publikasi tugas memicu `AssignmentPublished` -> diproses di latar belakang oleh `SendAssignmentNotification` -> mengirim `NewAssignmentNotification` ke database setiap mahasiswa di kelas tersebut.
   * **Peristiwa 2 (Nilai Keluar):** Dosen memberi nilai pengumpulan memicu `SubmissionGraded` -> diproses di latar belakang oleh `SendGradeNotification` -> mengirim `SubmissionGradedNotification` ke database mahasiswa pemilik submission.

2. **Antrean Database Aktif & Terdokumentasi:**
   * Menggunakan konfigurasi `QUEUE_CONNECTION=database` pada `.env` dan `.env.example`.
   * Struktur tabel antrean `jobs`, `job_batches`, dan `failed_jobs` aktif.
   * Panduan menjalankan *queue worker* (`php artisan queue:work`) telah didokumentasikan di `README.md`.

3. **Penanganan Kegagalan Job Terpasang:**
   * Setiap listener queue memiliki properti `$tries = 3`, `$backoff = 10` detik, dan method `failed(\Throwable $exception)` yang mencatat kegagalan ke berkas log.
   * Job yang gagal melebihi batas percobaan otomatis dicatat ke tabel `failed_jobs`.

4. **Lonceng Notifikasi di Navbar & Polling Otomatis:**
   * Navbar dilengkapi ikon lonceng 🔔 interaktif dengan lencana (*badge*) jumlah pesan belum dibaca (`unreadNotifications->count()`).
   * Menggunakan mekanisme polling AJAX otomatis setiap 30 detik via JavaScript tanpa perlu me-refresh halaman.

5. **Halaman Daftar Notifikasi & Proteksi Anti-IDOR:**
   * Mahasiswa dapat melihat daftar riwayat notifikasi di `/notifications`.
   * Fitur "Tandai Sudah Dibaca" dan "Hapus Notifikasi" dilindungi otorisasi Anti-IDOR yang ketat berakar dari akun pengguna yang sedang login (`auth()->user()->notifications()->where('id', $id)->firstOrFail()`).
   * Tersedia tombol praktis "Tandai Semua Sudah Dibaca".

6. **Alur Penilaian Lengkap:**
   * Dosen pengampu melihat daftar pengumpulan tugas mahasiswa di kelasnya.
   * Dosen menginput nilai angka (*score*) dan catatan evaluasi (*feedback*).
   * Sistem menyimpan nilai ke tabel `grades` dan secara otomatis memicu event asinkron ke antrean database.

7. **Rekapitulasi Nilai Akademik:**
   * **Rekap Nilai Kelas (Dosen/Admin):** Tersedia di `/courses/{course}/grades` yang menampilkan matriks nilai seluruh mahasiswa pada setiap tugas di kelas tersebut beserta rata-ratanya.
   * **Rekap Nilai Pribadi (Mahasiswa):** Tersedia di `/my-grades` ("Nilai Saya") yang menyajikan transkrip nilai seluruh tugas dari semua mata kuliah yang diikuti mahasiswa.

8. **Keamanan Payload Notifikasi:**
   * Kolom `data` pada tabel `notifications` bebas dari angka nilai maupun isi feedback dosen untuk mencegah kebocoran data akademik. Mahasiswa diarahkan ke halaman detail yang dilindungi otorisasi Policy.

---

## Daftar Berkas yang Dibuat & Dimodifikasi

| No | Berkas | Status | Deskripsi |
|---|---|---|---|
| 1 | [AssignmentPublished.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Events/AssignmentPublished.php) | Baru | Event saat tugas dipublikasikan oleh dosen |
| 2 | [SubmissionGraded.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Events/SubmissionGraded.php) | Baru | Event saat dosen memberi nilai pengumpulan tugas |
| 3 | [SendAssignmentNotification.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Listeners/SendAssignmentNotification.php) | Baru | Listener queue pengiriman notifikasi tugas baru dengan retry policy |
| 4 | [SendGradeNotification.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Listeners/SendGradeNotification.php) | Baru | Listener queue pengiriman notifikasi nilai tugas dengan retry policy |
| 5 | [NewAssignmentNotification.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Notifications/NewAssignmentNotification.php) | Baru | Notifikasi database tugas baru dengan payload aman |
| 6 | [SubmissionGradedNotification.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Notifications/SubmissionGradedNotification.php) | Baru | Notifikasi database nilai tugas tanpa membocorkan skor di payload |
| 7 | [NotificationController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/NotificationController.php) | Baru | Controller daftar notifikasi, polling unread count, dan markAsRead anti-IDOR |
| 8 | [GradeController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/GradeController.php) | Baru | Controller rekap nilai kelas (dosen) dan transkrip nilai mahasiswa |
| 9 | [notifications/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/notifications/index.blade.php) | Baru | Tampilan daftar notifikasi dengan tombol aksi dibaca dan hapus |
| 10 | [grades/course.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/grades/course.blade.php) | Baru | Tampilan matriks rekap nilai kelas untuk dosen pengampu |
| 11 | [grades/student.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/grades/student.blade.php) | Baru | Tampilan rekap transkrip nilai seluruh mata kuliah untuk mahasiswa |
| 12 | [NotificationQueueTest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/tests/Feature/NotificationQueueTest.php) | Baru | 9 skenario uji otomatis PHPUnit untuk queue, event, dan otorisasi |
| 13 | [layout.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/components/layout.blade.php) | Dimodifikasi | Penambahan komponen lonceng navbar, link nilai saya, dan polling JS |
| 14 | [courses/show.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/show.blade.php) | Dimodifikasi | Tombol tautan menuju rekap nilai kelas untuk dosen dan admin |
| 15 | [AssignmentController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/AssignmentController.php) | Dimodifikasi | Pemicu dispatch event AssignmentPublished pada store dan update |
| 16 | [SubmissionController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php) | Dimodifikasi | Pemicu dispatch event SubmissionGraded pada method grade |
| 17 | [routes/web.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/web.php) | Dimodifikasi | Rute untuk rekap nilai dan manajemen notifikasi |
| 18 | [AppServiceProvider.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Providers/AppServiceProvider.php) | Dimodifikasi | Pendaftaran Event Listeners asinkron di method boot |
| 19 | [README.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/README.md) | Dimodifikasi | Dokumentasi langkah menjalankan antrean worker di komputer lokal |

---

## Hasil Pengujian Otomatis

Seluruh pengujian unit dan fitur telah dieksekusi menggunakan PHPUnit:
* **Total Pengujian:** 73 passed
* **Total Assertions:** 210 assertions
* **Status:** 100% Lolos (Green)
