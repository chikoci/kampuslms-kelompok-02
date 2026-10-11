# Hasil Implementasi Tahap BUILD (Minggu 7)

## Ringkasan Pencapaian BUILD Minggu 7: Otorisasi & Pertahanan IDOR
Sesuai modul Minggu 7 dan spesifikasi proyek KampusLMS Bagian Keamanan & Otorisasi, seluruh sistem otorisasi objek (Fine-Grained Authorization) telah berhasil diimplementasikan, diaudit, dan diverifikasi 100%:

1. **Implementasi Laravel Policies Lengkap:**
   * `CoursePolicy`: Mengatur otorisasi mata kuliah (Admin dapat membuat MK; Dosen hanya dapat mengubah MK yang diampunya; Mahasiswa hanya dapat melihat MK yang diikutinya).
   * `MaterialPolicy`: Membatasi pengelolaan materi perkuliahan hanya untuk Dosen pengampu mata kuliah terkait.
   * `AssignmentPolicy`: Membatasi pembuatan dan pembaruan tugas hanya untuk Dosen pengampu mata kuliah.
   * `SubmissionPolicy`: Menolak keras serangan IDOR; Mahasiswa hanya dapat melihat dan mengunggah tugas miliknya sendiri; Dosen hanya dapat melihat submission pada mata kuliah yang diampunya; Admin memiliki akses audit penuh.
   * `GradePolicy`: Membatasi pemberian dan pengubahan nilai hanya untuk Dosen pengampu mata kuliah terkait (Admin dilarang menilai secara langsung sesuai matriks spesifikasi).
   * `UserPolicy`: Melindungi data pengguna; hanya Admin yang dapat melihat direktori pengguna dan mendaftarkan akun baru; membatasi edit profil agar pengguna tidak dapat mengubah data pengguna lain.

2. **Mitigasi Privilege Escalation (Eskalasi Hak Akses):**
   * Atribut `role` telah dikeluarkan dari `$fillable` pada model `User`.
   * Form Request `StoreUserRequest` dan `UpdateUserRequest` diverifikasi ketat sehingga peran hanya dapat diubah oleh Administrator sistem.

3. **Scoping Kueri Tingkat Index (Query Scoping):**
   * Di `CourseController@index`, kueri mata kuliah disaring secara otomatis:
     * Admin: melihat seluruh 5 mata kuliah.
     * Dosen: hanya melihat mata kuliah yang diampunya.
     * Mahasiswa: hanya melihat mata kuliah yang diikutinya melalui relasi pivot `course_user`.

4. **Tabel Titik Rawan IDOR di `docs/keamanan.md`:**
   * Dokumentasi lengkap seluruh 10+ titik rawan IDOR pada KampusLMS, peran yang berpotensi menyerang, parameter rentan, mekanisme pertahanan server-side, dan kode status respons HTTP (403 Forbidden).

5. **Pengujian Otorisasi Otomatis (`scripts/test-authz.sh`):**
   * Skrip bash otomatis yang mengeksekusi 25 skenario otorisasi nyata melalui `curl` menggunakan sesi autentikasi resmi (Admin, Dosen, Mahasiswa).
   * Menghasilkan tingkat kelulusan 100% (25/25 skenario Passed).

6. **Automated Feature Test (`tests/Feature/AuthzTest.php`):**
   * 32 metode pengujian PHPUnit komprehensif yang menguji matriks keamanan dan IDOR di tingkat kernel aplikasi.

7. **Integrasi Pipeline CI (`.github/workflows/ci.yml`):**
   * Pipeline GitHub Actions yang menjalankan migrasi bersih, seeding deterministik, eksekusi PHPUnit, dan skrip `test-authz.sh` pada lingkungan container MySQL 8.0.

---

## Daftar Berkas yang Dibuat & Dimodifikasi

| No | Berkas | Status | Deskripsi |
|---|---|---|---|
| 1 | [CoursePolicy.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Policies/CoursePolicy.php) | Baru | Kebijakan otorisasi mata kuliah dan scoping akses |
| 2 | [MaterialPolicy.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Policies/MaterialPolicy.php) | Baru | Kebijakan otorisasi materi perkuliahan |
| 3 | [AssignmentPolicy.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Policies/AssignmentPolicy.php) | Baru | Kebijakan otorisasi tugas perkuliahan |
| 4 | [SubmissionPolicy.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Policies/SubmissionPolicy.php) | Baru | Kebijakan pencegahan IDOR pada pengumpulan tugas |
| 5 | [GradePolicy.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Policies/GradePolicy.php) | Baru | Kebijakan otorisasi penilaian oleh dosen pengampu |
| 6 | [UserPolicy.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Policies/UserPolicy.php) | Baru | Kebijakan otorisasi manajemen pengguna dan pencegahan eskalasi |
| 7 | [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) | Dimodifikasi | Integrasi `authorize()` dan query scoping per peran |
| 8 | [SubmissionController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php) | Dimodifikasi | Integrasi otorisasi SubmissionPolicy dan GradePolicy |
| 9 | [docs/keamanan.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/docs/keamanan.md) | Baru | Dokumen resmi analisis keamanan dan tabel mitigasi IDOR |
| 10 | [scripts/test-authz.sh](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/scripts/test-authz.sh) | Baru | Skrip pengujian otomatis 25 skenario otorisasi multi-peran |
| 11 | [tests/Feature/AuthzTest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/tests/Feature/AuthzTest.php) | Baru | 32 uji otomatis PHPUnit untuk otorisasi dan proteksi IDOR |
| 12 | [.github/workflows/ci.yml](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/.github/workflows/ci.yml) | Baru | Konfigurasi CI/CD GitHub Actions dengan service MySQL 8.0 |
