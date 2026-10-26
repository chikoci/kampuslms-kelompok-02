# Perancangan Alur Asinkron & Notifikasi (Minggu 10 - Prompt Pack A)
**Proyek: KampusLMS (Laravel 12) | Milestone M3**

## 1. Konteks & Tujuan Perancangan
Pada aplikasi KampusLMS, terdapat 2 peristiwa bisnis akademik yang memerlukan pengiriman notifikasi:
1. **Dosen mempublikasikan tugas baru:** Seluruh mahasiswa yang terdaftar pada mata kuliah terkait harus menerima notifikasi.
2. **Dosen memberikan nilai pada pengumpulan tugas (submission):** Mahasiswa pemilik tugas harus menerima pemberitahuan bahwa tugasnya telah dinilai.

Dokumen ini merancang arsitektur asinkron berbasis **Event, Listener, Queue, dan Notification** di Laravel 12 agar proses pengiriman notifikasi tidak menyandera (*blocking*) request pengguna, serta memetakan titik kegagalan senyap (*silent failure*) ketika queue worker mati.

---

## 2. Diagram Alur Teks Asinkron

### Alur 1: Dosen Mempublikasikan Tugas Baru
```
[Dosen Klik "Publikasikan Tugas"]
               │
               ▼
   [AssignmentController@store / publish]
               │  1. Simpan baris tugas ke tabel `assignments`
               │  2. AssignmentPublished::dispatch($assignment)
               │  3. Return HTTP 302 Redirect (Cepat: < 50 ms)
               ▼
[Laravel Queue Manager (Driver: database)]
               │
               │  Membuat baris antrean baru di tabel `jobs`
               ▼
========================================================================
 ⚠️ TITIK RAWAN KEGAGALAN SENYAP (SILENT FAILURE):
 Jika Queue Worker MATI:
 - Record membeku di tabel `jobs`
 - Tidak ada error di browser dosen, tidak ada error di storage/logs/laravel.log
 - Mahasiswa TIDAK PERNAH menerima notifikasi
========================================================================
               │
               ▼ (Jika Worker Aktif: `php artisan queue:work`)
  [SendAssignmentNotification (Listener)]
               │  1. Ambil daftar mahasiswa kelas: $assignment->course->students
               │  2. Notification::send($students, new NewAssignmentNotification($assignment))
               ▼
[Tabel `notifications`]
               │  Setiap mahasiswa mendapat 1 baris di tabel `notifications`
               ▼
[Browser Mahasiswa]
   Lonceng notifikasi di navbar mendeteksi unread count > 0 (via polling/reverb)
```

---

### Alur 2: Dosen Memberikan Nilai pada Pengumpulan Tugas
```
[Dosen Input Nilai & Feedback -> Klik "Simpan Nilai"]
               │
               ▼
       [GradeController@store / update]
               │  1. Simpan/update data di tabel `grades`
               │  2. SubmissionGraded::dispatch($submission)
               │  3. Return HTTP 302 Redirect ke daftar submission (Cepat)
               ▼
[Laravel Queue Manager (Driver: database)]
               │
               │  Membuat baris antrean baru di tabel `jobs`
               ▼
========================================================================
 ⚠️ TITIK RAWAN KEGAGALAN SENYAP (SILENT FAILURE):
 Jika Worker MATI:
 - Nilai sudah aman di tabel `grades`, tetapi mahasiswa tidak tahu nilainya keluar
========================================================================
               │
               ▼ (Jika Worker Aktif)
     [SendGradeNotification (Listener)]
               │  $submission->user->notify(new SubmissionGradedNotification($submission))
               ▼
[Tabel `notifications`]
               │  1 baris notifikasi tersimpan untuk mahasiswa pemilik
               ▼
[Browser Mahasiswa]
   Lonceng notifikasi menampilkan pemberitahuan tugas telah dinilai (tanpa bocor nilai)
```

---

## 3. Tabel Perancangan Komponen Event, Listener, & Notification

| Komponen | Peristiwa 1: Tugas Baru Dipublikasikan | Peristiwa 2: Nilai Tugas Diberikan |
|---|---|---|
| **Nama Event** | `AssignmentPublished` | `SubmissionGraded` |
| **Data yang Dibawa** | Objek model `public Assignment $assignment` | Objek model `public Submission $submission` |
| **Kapan Dipicu** | Saat status tugas diubah menjadi `published` pada `AssignmentController` | Saat dosen menyimpan nilai di `GradeController` |
| **Nama Listener** | `SendAssignmentNotification` | `SendGradeNotification` |
| **Perlu ShouldQueue?** | **YA (Wajib implements ShouldQueue)** | **YA (Wajib implements ShouldQueue)** |
| **Alasan ShouldQueue** | Satu kelas bisa berisi 40-100 mahasiswa. Pengiriman notifikasi satu per satu secara sinkron akan membuat request dosen lambat/freeze (3-15 detik) bahkan timeout. Dengan queue, response langsung kembali dalam hitungan milidetik. | Memisahkan transaksi penyimpanan nilai dengan notifikasi. Jika di masa depan notifikasi diperluas ke email/push, error koneksi pihak ketiga tidak membatalkan penyimpanan nilai. |
| **Retry & Failure Policy** | `$tries = 3`, `$backoff = 10`, method `failed()` | `$tries = 3`, `$backoff = 10`, method `failed()` |
| **Nama Notification** | `NewAssignmentNotification` | `SubmissionGradedNotification` |
| **Saluran (Via)** | `['database']` | `['database']` |
| **Penerima Notifikasi** | Seluruh mahasiswa terdaftar pada mata kuliah terkait (`$assignment->course->students`) | Mahasiswa pemilik submission (`$submission->user`) |
| **Cara Menentukan Penerima** | Diambil dari relasi course: `$event->assignment->course->students()->get()` | Diambil dari relasi submission: `$event->submission->user` |
| **Isi Payload (Kolom `data`)** | `assignment_id`, `course_id`, `course_name`, `title`, `due_at`, `url`, `type` | `submission_id`, `assignment_id`, `course_name`, `assignment_title`, `url`, `type` |
| **Aturan Keamanan Payload** | Bebas dari data sensitif. | **DILARANG memuat angka nilai (`score`) maupun isi `feedback`.** Mahasiswa harus membuka URL berotorisasi untuk melihat nilai. |

---

## 4. Struktur Payload Notifikasi Database (Kolom `data`)

### Payload Notifikasi Tugas Baru (`NewAssignmentNotification`):
```json
{
  "assignment_id": 5,
  "course_id": 2,
  "course_name": "Pemrograman Web",
  "title": "Tugas 3: Realtime, Queue, dan Notifikasi",
  "due_at": "2026-11-10T23:59:00+08:00",
  "url": "/assignments/5",
  "type": "assignment_published"
}
```

### Payload Notifikasi Nilai Tugas (`SubmissionGradedNotification`):
```json
{
  "submission_id": 14,
  "assignment_id": 5,
  "course_name": "Pemrograman Web",
  "assignment_title": "Tugas 3: Realtime, Queue, dan Notifikasi",
  "graded_at": "2026-10-26T14:30:00+08:00",
  "url": "/assignments/5",
  "type": "submission_graded"
}
```

> **Catatan Keamanan Kritis:**  
> Kolom `data` di tabel `notifications` berformat teks terbuka (JSON tanpa enkripsi). Menyimpan nilai atau catatan evaluasi dosen di kolom ini akan mengakibatkan kebocoran data akademik ke pihak yang tidak berhak (misalnya administrator database atau kebocoran query log). Nilai hanya ditampilkan di view yang diproteksi `SubmissionPolicy`.

---

## 5. Analisis Titik Kegagalan Senyap (Silent Failure)

Ketika menggunakan `QUEUE_CONNECTION=database`:

1. **Titik Terjadinya:**
   Tepat setelah Event di-dispatch dan baris job masuk ke tabel `jobs`.
2. **Kondisi Kegagalan:**
   Jika proses latar belakang worker (`php artisan queue:work`) tidak aktif atau mati (misalnya server restart atau proses crash).
3. **Mengapa Disebut "Gagal Senyap" (*Silent Failure*)?**
   * Controller Laravel tetap mengembalikan respons HTTP 200/302 sukses.
   * Dosen melihat notifikasi sukses: *"Tugas berhasil diterbitkan"* atau *"Nilai berhasil disimpan"*.
   * Berkas log `storage/logs/laravel.log` **sama sekali tidak mencatat error** karena penyimpanan database tugas/nilai memang berhasil 100%.
   * Namun, antrean tugas menumpuk tanpa pernah diproses, sehingga mahasiswa tidak pernah mendapatkan notifikasi.
4. **Cara Mengetahui & Mendiagnosis:**
   * Cek jumlah baris di tabel `jobs`: jika ada baris yang mengendap lama, berarti worker mati.
   * Cek status proses sistem operasi / supervisor yang mengawal worker.
   * Periksa tabel `failed_jobs` untuk memastikan apakah job mati senyap atau gagal karena exception.
