# Hasil Implementasi Tahap BUILD (Minggu 9)

## Ringkasan Pencapaian BUILD Minggu 9: Alur Berkas KampusLMS
Sesuai modul Minggu 9 (Upload File dan Data Dinamis), seluruh 7 alur manajemen berkas pada KampusLMS (Laravel 12) telah berhasil diimplementasikan, diverifikasi, dan diuji secara komprehensif:

1. **Unggah Materi Perkuliahan oleh Dosen:**
   * Dosen dapat mengunggah berkas materi berformat PDF, PPTX, atau DOCX dengan ukuran maksimal 20 MB (`max:20480`).
   * Berkas disimpan secara aman di disk privat (`storage/app/private/materials/{course_id}/`).
   * Tersedia opsi alternatif berupa tautan eksternal (URL) untuk materi daring.

2. **Unduh Materi Terproteksi Otorisasi Policy:**
   * Mahasiswa hanya dapat mengunduh materi dari mata kuliah yang diikutinya (diverifikasi lewat `MaterialPolicy@download` dan `Gate::authorize()`).
   * Aliran berkas dikirim via `Storage::disk('local')->download()`.

3. **Pengumpulan Tugas oleh Mahasiswa:**
   * Validasi ketat berkas pengumpulan tugas: wajib berformat PDF, DOC, atau DOCX dengan ukuran maksimal 5 MB (`max:5120`).
   * Menerapkan `updateOrCreate` pada composite unique `(assignment_id, user_id)` sehingga pengumpulan ulang secara otomatis menimpa submission lama dan menghapus berkas fisik lama agar tidak meninggalkan berkas sampah.
   * Status keterlambatan `is_late` dihitung di server berdasarkan `due_at`. Pengumpulan ditolak jika `allow_late` bernilai `false` dan batas waktu telah terlewati.

4. **Unduh Submission Aman (Anti-IDOR):**
   * Berkas pengumpulan tugas hanya dapat diunduh oleh mahasiswa pemilik tugas, dosen pengampu mata kuliah terkait, dan administrator sistem.
   * Mahasiswa lain atau dosen dari kelas lain ditolak dengan kode respons HTTP 403 Forbidden.

5. **Daftar Pengumpulan untuk Dosen:**
   * Halaman daftar pengumpulan menyajikan rekap seluruh mahasiswa yang terdaftar di mata kuliah: menampilkan siapa yang sudah mengumpulkan (lengkap dengan tanda terlambat/tepat waktu) dan siapa yang belum mengumpulkan.

6. **Penghapusan Berkas Fisik & Penanganan Kasus Cascade:**
   * Menghapus record `Submission` atau `Material` otomatis menghapus berkas fisik di disk privat via event Eloquent `static::deleting`.
   * Penanganan kasus cascade: saat Tugas (`Assignment`) atau Mata Kuliah (`Course`) dihapus, event berjenjang memicu penghapusan berkas fisik setiap submission dan materi anak sebelum baris database terhapus.

7. **Format Ukuran Berkas Terbaca Manusia:**
   * Tampilan berkas menyajikan `original_name` dan ukuran berkas yang mudah dibaca pengguna (misalnya: `1.2 MB`, `450 KB`) menggunakan helper modern Laravel 12 `\Illuminate\Support\Number::fileSize()`.

---

## Daftar Berkas yang Dibuat & Dimodifikasi

| No | Berkas | Status | Deskripsi |
|---|---|---|---|
| 1 | [SubmissionController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php) | Dimodifikasi | Fitur pengumpulan tugas, updateOrCreate, disk privat, allow_late, dan rekap dosen |
| 2 | [MaterialController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/MaterialController.php) | Dimodifikasi | Upload berkas materi (20MB) / URL eksternal, disk privat, dan unduh aman |
| 3 | [Submission.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/Submission.php) | Dimodifikasi | Event `booted()` deleting untuk hapus berkas fisik dan accessor `formatted_file_size` |
| 4 | [Material.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/Material.php) | Dimodifikasi | Event `booted()` deleting untuk hapus berkas fisik materi dan accessor format ukuran |
| 5 | [Assignment.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/Assignment.php) | Dimodifikasi | Event `booted()` cascade deleting untuk menghapus berkas fisik setiap submission |
| 6 | [Course.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/Course.php) | Dimodifikasi | Event `booted()` cascade deleting untuk menghapus materi dan submission fisik |
| 7 | [routes/web.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/web.php) | Dimodifikasi | Menambahkan rute `submissions.destroy` untuk penghapusan pengumpulan |
| 8 | [assignments/show.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/assignments/show.blade.php) | Dimodifikasi | UI pengumpulan tugas, kumpul ulang (updateOrCreate), dan ukuran berkas manusia |
| 9 | [submissions/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/submissions/index.blade.php) | Dimodifikasi | Tampilan rekap dosen: daftar mahasiswa sudah/belum mengumpulkan, berkas, dan nilai |
| 10 | [submissions/show.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/submissions/show.blade.php) | Dimodifikasi | Detail pengumpulan, nama asli berkas, dan ukuran terbaca manusia |
| 11 | [materials/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/materials/create.blade.php) | Dimodifikasi | Form upload materi dokumen (PDF/PPTX/DOCX 20MB) dan input tautan eksternal |
| 12 | [courses/show.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/show.blade.php) | Dimodifikasi | Tampilan materi (link eksternal vs unduh berkas privat dengan ukuran berkas) |
| 13 | [FileStorageTest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/tests/Feature/FileStorageTest.php) | Baru | 9 uji otomatis PHPUnit untuk validasi berkas, disk privat, cascade, dan otorisasi |
| 14 | [Walkthrough/Materi 9/Prompt Pack A.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Walkthrough/Materi%209/Prompt%20Pack%20A.md) | Baru | Dokumentasi perancangan tabel penyimpanan berkas dan celah keamanan |
| 15 | [Walkthrough/Materi 9/Prompt Pack B.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Walkthrough/Materi%209/Prompt%20Pack%20B.md) | Baru | Dokumentasi implementasi fitur submission dan analisis event model |

---

## Hasil Pengujian Otomatis

Seluruh pengujian unit dan fitur telah dieksekusi menggunakan PHPUnit:
* **Total Uji**: 61 pengujian lolos (100% Passed).
* **Total Asersi**: 160 asersi valid.
* **Feature/FileStorageTest**: 9 pengujian spesifik penyimpanan berkas lolos tanpa kendala.
