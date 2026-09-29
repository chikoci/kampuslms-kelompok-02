# Hasil Implementasi Tahap BUILD (Minggu 5)

## Ringkasan Pencapaian BUILD Minggu 5
Sesuai modul Minggu 5 sub-bab 5.3 (Tahap BUILD) dan spesifikasi proyek KampusLMS, seluruh 6 butir persyaratan arsitektur routing dan middleware telah selesai diimplementasikan:

1. **Restrukturisasi `routes/web.php` dengan Route Group per Peran:**
   * Seluruh rute dikelompokkan berdasarkan peran pengguna:
     * **Admin (`admin.` / `/admin`):** Mengelola seluruh data pengguna dan master mata kuliah.
     * **Dosen (`dosen.` / `/dosen`):** Mengelola pembelajaran, materi, tugas, dan penilaian.
     * **Mahasiswa (`mahasiswa.` / `/mahasiswa`):** Mengakses mata kuliah terdaftar dan mengumpulkan tugas.
   * Dilengkapi rute publik (`/`, `/tentang`) yang dapat diakses tanpa login.

2. **Middleware `EnsureUserHasRole` & Pendaftaran di `bootstrap/app.php`:**
   * Dibuat kelas middleware `app/Http/Middleware/EnsureUserHasRole.php` yang mendukung verifikasi multi-peran (`role:admin,dosen`).
   * Membedakan respons **401 Unauthorized** jika belum login, dan **403 Forbidden** jika peran tidak sesuai.
   * Didaftarkan secara resmi pada file `bootstrap/app.php` menggunakan method `->withMiddleware(...)` sebagai alias `'role'` sesuai standar Laravel 12.

3. **Penerapan Penuh Route Model Binding:**
   * Seluruh controller (`CourseController`, `UserController`, `AssignmentController`, `MaterialController`, `SubmissionController`) telah menggunakan Route Model Binding (implicit binding).
   * Menghilangkan seluruh pemanggilan manual `findOrFail($id)` sehingga otomatis mengembalikan **HTTP 404** jika ID data tidak ditemukan.

4. **Rute Bersarang (Nested Routes) dengan `shallow()` dan `scopeBindings()`:**
   * Rute materi (`courses.materials`) dan tugas (`courses.assignments`) dikelompokkan di dalam `Route::scopeBindings()`.
   * Memakai `->shallow()` agar URL detail tugas/materi tetap ringkas (`/assignments/{assignment}`) namun tetap terjamin integritas relasi anak-ke-induknya.

5. **Proteksi IDOR Sementara (`abort_unless`):**
   * Di `CourseController`: Memblokir dosen mengedit/memperbarui mata kuliah milik dosen lain (`$course->lecturer_id === auth()->id()`).
   * Di `UserController`: Memblokir pengguna mengedit profil pengguna lain (`$user->id === auth()->id()`).
   * Di `SubmissionController`: Memblokir mahasiswa melihat atau mengunduh submission milik mahasiswa lain.

6. **Halaman Error 403 Kustom:**
   * Dibuat berkas view `resources/views/errors/403.blade.php` yang modern dan informatif tanpa membocorkan identitas data rahasia pemilik.

---

## Daftar Berkas yang Dibuat & Dimodifikasi

| No | Berkas | Status | Deskripsi |
|---|---|---|---|
| 1 | [EnsureUserHasRole.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Middleware/EnsureUserHasRole.php) | Baru | Middleware validasi peran pengguna dengan pemisahan 401 dan 403 |
| 2 | [bootstrap/app.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/bootstrap/app.php) | Dimodifikasi | Mendaftarkan alias middleware `'role'` pada konfigurasi Laravel 12 |
| 3 | [routes/web.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/web.php) | Dimodifikasi | Menyusun route group peran, shallow nested routes, dan scopeBindings |
| 4 | [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) | Dimodifikasi | Menambahkan proteksi IDOR pada edit, update, dan destroy |
| 5 | [UserController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php) | Dimodifikasi | Menambahkan proteksi IDOR pada edit, update, dan destroy |
| 6 | [AssignmentController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/AssignmentController.php) | Baru | Controller tugas perkuliahan dengan route model binding & proteksi IDOR |
| 7 | [MaterialController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/MaterialController.php) | Baru | Controller materi perkuliahan dengan shallow binding & download aman |
| 8 | [SubmissionController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php) | Baru | Controller pengumpulan tugas dengan proteksi IDOR ketat |
| 9 | [403.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/errors/403.blade.php) | Baru | Halaman error kustom 403 Forbidden |
