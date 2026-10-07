# Hasil Implementasi Tahap BUILD (Minggu 6)

## Ringkasan Pencapaian BUILD Minggu 6
Sesuai modul Minggu 6 sub-bab 6.3 (Tahap BUILD) dan spesifikasi proyek KampusLMS Bagian 5, seluruh persyaratan arsitektur REST API telah dibangun dan diverifikasi:

1. **Aktivasi API & Laravel Sanctum (BUILD 1):**
   * Berkas `routes/api.php` diaktifkan via `php artisan install:api`.
   * Pustaka Laravel Sanctum terpasang dan tabel `personal_access_tokens` berhasil dimigrasikan.
   * Model `User` telah dilengkapi trait `Laravel\Sanctum\HasApiTokens`.

2. **Seluruh Endpoint Kontrak Bagian 5 Selesai (BUILD 2):**
   * Modul Autentikasi: `login`, `logout`, `me`.
   * Modul Mata Kuliah: `courses` (filter peran otomatis), detail MK ber-counter, materi, dan tugas.
   * Modul Tugas: `POST` (201), `PUT/PATCH` (200), `DELETE` (204), serta daftar submission.
   * Modul Pengumpulan & Penilaian: unggah file tugas (201) dan upsert nilai tugas (201 baru, 200 update).
   * Modul Notifikasi: `notifications` dan `notifications/{id}/read`.

3. **API Resource dengan Whitelist Atribut (BUILD 3):**
   * Dibuat kelas resource: `UserResource`, `CourseResource`, `MaterialResource`, `AssignmentResource`, `SubmissionResource`, `GradeResource`, dan `NotificationResource`.
   * Model mentah dilarang dikembalikan langsung guna mengeliminasi risiko kebocoran hash kata sandi dan token privat.

4. **Eager Loading Bebas N+1 (BUILD 4):**
   * Setiap query relasi koleksi dipasangi eager load (`with(...)`).
   * Pada level Resource diproteksi menggunakan method `whenLoaded()` dan `whenCounted()`.

5. **Rate Limiting / Pembatas Frekuensi (BUILD 5):**
   * Proteksi `throttle:5,1` diterapkan ketat pada endpoint login untuk mencegah brute force.
   * Proteksi `throttle:60,1` diterapkan pada seluruh endpoint privat terautentikasi.

6. **Dokumentasi `docs/api.md` (BUILD 6):**
   * Selesai dibuat di `docs/api.md` sesuai kontrak Bagian 5 spesifikasi proyek, lengkap dengan parameter, contoh curl, response sukses, dan response gagal untuk seluruh 16 endpoint.

7. **Skrip Uji Otomatis `scripts/test-api.sh` (BUILD 7):**
   * Selesai dibuat di `scripts/test-api.sh` dan telah diuji via Git Bash. Seluruh 15 skenario otorisasi (401, 403, 200, 201) lulus 100%.

8. **Pendaftaran Jalur Frontend (BUILD 8):**
   * Kelompok 02 secara resmi memilih dan mendaftarkan jalur **(b) Blade + Livewire 3** untuk pengembangan antarmuka minggu 7 sampai 16.
   * Alasan pemilihan: Memberikan pengalaman interaktif reaktif modern tanpa reload halaman penuh, tetap terintegrasi aman di dalam ekosistem Laravel (tanpa kerumitan token klien/CORS), dan didukung modul praktikum resmi pada minggu 9.

---

## Daftar Berkas yang Dibuat & Dimodifikasi

| No | Berkas | Status | Deskripsi |
|---|---|---|---|
| 1 | [User.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/User.php) | Dimodifikasi | Menambahkan trait `HasApiTokens` untuk penerbitan token Sanctum |
| 2 | [Assignment.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/Assignment.php) | Dimodifikasi | Menambahkan `created_by` pada array `$fillable` |
| 3 | [Grade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/Grade.php) | Dimodifikasi | Menambahkan `graded_by` pada array `$fillable` |
| 4 | [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php) | Baru | Mengonfigurasi prefix `/api/v1`, throttle, Sanctum middleware, dan 16 rute |
| 5 | [UserResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/UserResource.php) | Baru | Whitelist atribut user tanpa membocorkan hash password |
| 6 | [CourseResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/CourseResource.php) | Baru | Serialisasi mata kuliah dengan eager loading lecturer dan counter |
| 7 | [MaterialResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/MaterialResource.php) | Baru | Serialisasi berkas materi dan uploader |
| 8 | [AssignmentResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/AssignmentResource.php) | Baru | Serialisasi tugas kuliah dan creator |
| 9 | [SubmissionResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/SubmissionResource.php) | Baru | Serialisasi data pengumpulan tugas mahasiswa |
| 10 | [GradeResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/GradeResource.php) | Baru | Serialisasi nilai tugas dan grader |
| 11 | [NotificationResource.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Resources/NotificationResource.php) | Baru | Serialisasi notifikasi pengguna |
| 12 | [AuthController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/AuthController.php) | Baru | Controller API untuk login, logout, dan profil me |
| 13 | [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/CourseController.php) | Baru | Controller API untuk daftar mata kuliah, materi, dan tugas |
| 14 | [AssignmentController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/AssignmentController.php) | Baru | Controller API untuk CRUD tugas dan unggah submission |
| 15 | [SubmissionController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/SubmissionController.php) | Baru | Controller API untuk detail submission dan upsert nilai |
| 16 | [NotificationController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/NotificationController.php) | Baru | Controller API untuk notifikasi in-app |
| 17 | [ApiTest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/tests/Feature/ApiTest.php) | Baru | Feature test untuk verifikasi otomatis seluruh kontrak API |
| 18 | [Prompt Pack A.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Walkthrough/Materi%206/Prompt%20Pack%20A.md) | Baru | Dokumentasi logbook implementasi Prompt Pack A |
| 19 | [Prompt Pack B.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Walkthrough/Materi%206/Prompt%20Pack%20B.md) | Baru | Dokumentasi audit keamanan dan kebocoran data Prompt Pack B |
| 20 | [scripts/test-api.sh](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/scripts/test-api.sh) | Baru | Skrip bash pengujian otomatis 4 kondisi otorisasi API berbasis curl |
| 21 | [Prompt Pack C.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Walkthrough/Materi%206/Prompt%20Pack%20C.md) | Baru | Dokumentasi implementasi skrip uji otorisasi Prompt Pack C |
| 22 | [docs/api.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/docs/api.md) | Baru | Dokumentasi lengkap arsitektur dan endpoint REST API KampusLMS |
| 23 | [Prompt Pack D.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Walkthrough/Materi%206/Prompt%20Pack%20D.md) | Baru | Dokumentasi logbook penyusunan panduan API Prompt Pack D |
