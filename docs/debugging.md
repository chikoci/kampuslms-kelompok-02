# Catatan Debugging & Pencegahan Regresi (KampusLMS - Kelompok 02)

Dokumen ini mencatat tiga bug nyata yang pernah ditemui selama pengembangan KampusLMS sepanjang semester, meliputi gejala yang tampak, proses mempersempit akar penyebab secara sistematis, perbaikan yang diterapkan, serta feature test yang sekarang mencegah bug tersebut kembali.

---

## Bug 1: Kegagalan Senyap Notifikasi Penilaian Tidak Terkirim ke Mahasiswa

* **Konteks Waktu & Referensi:** Milestone M3 & Audit Minggu 14 (Commit `ee734d1` dan perbaikan `SendGradeNotification::failed()`).
* **Gejala:**
  1. Dosen mengisi nilai dan feedback pada submission mahasiswa lalu klik **Simpan Nilai**.
  2. Browser mengembalikan halaman dengan pesan sukses *"Nilai berhasil disimpan."* tanpa error 500.
  3. Saat login sebagai mahasiswa pemilik submission, ikon lonceng notifikasi tetap bernilai `0` dan tidak ada pemberitahuan nilai baru yang masuk.
* **Cara Menemukan Penyebabnya:**
  1. Memeriksa tabel `grades` di database: data nilai ternyata sudah masuk dengan benar, artinya masalah bukan pada proses penyimpanan nilai, melainkan pada alur pemicu event atau antrean notifikasi.
  2. Mengecek konfigurasi `.env` (`QUEUE_CONNECTION=database`), status `php artisan queue:monitor default`, serta isi tabel `jobs` dan `failed_jobs`.
  3. Menelusuri method `SubmissionController::grade()` (`app/Http/Controllers/SubmissionController.php`): pada implementasi awal sebelum commit `ee734d1`, controller lupa memanggil `event(new \App\Events\SubmissionGraded($submission))` setelah menyimpan nilai.
  4. Pada audit Minggu 14 (Prompt Pack D), pemeriksaan lanjutan pada `app/Listeners/SendGradeNotification.php` juga menemukan bahwa method `failed(\Throwable $exception)` kekurangan parameter `SubmissionGraded $event`, sehingga saat job gagal diproses worker, `'submission_id'` di `storage/logs/laravel.log` selalu tercatat `null`.
* **Perbaikan yang Diterapkan:**
  * Memastikan `event(new \App\Events\SubmissionGraded($submission))` dipanggil di kedua jalur penilaian (jalur rubrik otomatis baris 275 dan jalur standar baris 297).
  * Memperbaiki signature method `failed(SubmissionGraded $event, \Throwable $exception): void` pada `app/Listeners/SendGradeNotification.php` agar `submission_id` tercatat akurat di log saat terjadi kegagalan worker.
* **Test Pencegah Regresi:**
  * `NotificationQueueTest::test_grading_submission_dispatches_event_and_notifies_student_safely`
  * `NotificationQueueTest::test_send_grade_notification_failed_handler_logs_submission_id`
  * `PrioritySecurityAndFlowTest::test_course_lecturer_can_grade_submission_and_trigger_notification`

---

## Bug 2: Error Penyimpanan Pengumpulan Tugas (`user_id` & `is_late` Tidak Tersimpan via Mass Assignment)

* **Konteks Waktu & Referensi:** Milestone M2 / M3 (Commit `a1cb4ac`).
* **Gejala:**
  1. Mahasiswa memilih berkas jawaban dan mengirim formulir pengumpulan tugas.
  2. Proses gagal di server dengan error query SQL karena kolom `user_id` tidak memiliki nilai default saat insert, dan status keterlambatan `is_late` tidak terekam saat menggunakan `create()` / `updateOrCreate()`.
  3. Selain itu, jika formulir dikirim tanpa memilih file, error spesifik per field `file` tidak tertangkap secara konsisten.
* **Cara Menemukan Penyebabnya:**
  1. Membaca `storage/logs/laravel.log` untuk melihat pesan pengecualian SQL (`SQLSTATE[HY000]: Field 'user_id' doesn't have a default value`).
  2. Membaca stack trace dari atas dan menemukan baris pertama yang menunjuk folder proyek di `app/Http/Controllers/SubmissionController.php`, lalu memeriksa payload yang dikirim ke `Submission::create()` / `updateOrCreate()`.
  3. Membandingkan key array data controller dengan properti `$fillable` pada `app/Models/Submission.php`. Terlihat `'user_id'` dan `'is_late'` belum masuk ke daftar `$fillable`, sehingga Eloquent membuang nilai keduanya secara diam-diam (*mass assignment protection*), dan aturan validasi `file` sebelumnya masih menggunakan `'nullable'`.
* **Perbaikan yang Diterapkan:**
  * Menambahkan `'user_id'` dan `'is_late'` ke dalam `$fillable` pada `app/Models/Submission.php` (tanpa membuka `$guarded = []`).
  * Memperketat validasi unggahan menjadi `['required', 'file', 'mimes:pdf,doc,docx', 'max:5120']` di `SubmissionController::store()` serta menangani status error upload PHP (`UPLOAD_ERR_NO_FILE`, `UPLOAD_ERR_INI_SIZE`).
* **Test Pencegah Regresi:**
  * `AuthzTest::test_student_can_upload_submission_file`
  * `AuthzTest::test_student_cannot_submit_without_file`
  * `FileStorageTest::test_submission_calculates_is_late_server_side`
  * `PrioritySecurityAndFlowTest::test_enrolled_student_can_submit_file_assignment`

---

## Bug 3: `LazyLoadingViolationException` (`Attempted to lazy load [lecturer]`) Setelah Optimisasi Performa

* **Konteks Waktu & Referensi:** Materi 11 - Optimisasi Performa (Tercatat di `storage/logs/laravel.log` tanggal `2026-11-05 12:00:26`, `docs/performa.md`, dan Commit `282c958`).
* **Gejala:**
  1. Sebelumnya halaman Daftar Mata Kuliah (`/courses`) dan Transkrip Nilai Mahasiswa (`/my-grades`) terbuka normal.
  2. Setelah `Model::preventLazyLoading(! app()->isProduction())` diaktifkan di `AppServiceProvider::boot()`, halaman tersebut tiba-tiba memunculkan error 500:
     `Attempted to lazy load [lecturer] on model [App\Models\Course] but lazy loading is disabled.`
* **Cara Menemukan Penyebabnya:**
  1. Menelusuri stack trace pada `storage/logs/laravel.log` dan langsung melihat berkas view yang ditunjuk di pesan error: `resources/views/courses/index.blade.php` dan `resources/views/grades/student.blade.php`.
  2. Memeriksa tab **Queries** pada Laravel Debugbar sebelum `preventLazyLoading` aktif: terlihat query duplikat berulang `select * from users where users.id = ? and users.deleted_at is null limit 1` untuk setiap kartu/baris mata kuliah yang dirender di dalam loop `@foreach`.
  3. Mempersempit pemeriksaan ke `CourseController::index()` dan `GradeController::studentGrades()`, di mana query model `Course` belum menyertakan eager loading relasi `lecturer`.
* **Perbaikan yang Diterapkan:**
  * Menambahkan `->with('lecturer')` pada `CourseController::index()` dan eager loading `'lecturer'` pada `GradeController::studentGrades()`, sekaligus `withCount('submissions')` pada relasi tugas agar tidak memicu lazy loading ataupun N+1 query.
  * Membiarkan `Model::preventLazyLoading(! app()->isProduction())` tetap aktif di `AppServiceProvider::boot()` sehingga error ini otomatis langsung tertangkap di tahap development maupun testing (`APP_ENV=testing`) bila terjadi lagi.
* **Test Pencegah Regresi:**
  * `AuthzTest::test_course_index_is_scoped_by_role` (berjalan dengan `preventLazyLoading` aktif)
  * `NotificationQueueTest::test_student_can_view_own_grades_transcript` (berjalan dengan `preventLazyLoading` aktif)
