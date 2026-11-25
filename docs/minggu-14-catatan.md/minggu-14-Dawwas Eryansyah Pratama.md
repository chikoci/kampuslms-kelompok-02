# Catatan Praktikum Minggu 14: Debugging dan Testing

**Nama:** Dawwas Eryansyah Pratama  
**NIM:** 10241019  
**Kelompok:** Kelompok 02  
**Peran Utama Proyek:** Otorisasi Tugas (Assignments), Pengumpulan Tugas (Submissions), Penilaian & Nilai (Grading), Indexing Database, serta UI/UX Portal Mahasiswa.

---

## Bagian READ: Audit Test Suite, Stack Trace, dan Analisis Query

Pada tahap READ ini, saya mengaudit seluruh rangkaian pengujian (*test suite*) KampusLMS Kelompok 02, melatih pembacaan kegagalan PHPUnit dan *stack trace* pada `storage/logs/laravel.log`, serta mengamati query halaman rekap nilai menggunakan `DB::listen()`.

### 1. Inventarisasi Test Suite KampusLMS (`tests/`)
Saat ini repositori KampusLMS Kelompok 02 telah memiliki **8 berkas test** dengan total **83 kasus uji** (jauh melampaui target minimal 20 feature test pada modul):

| Berkas Test | Jenis | Jumlah Test | Fokus Pengujian Utama |
| :--- | :---: | :---: | :--- |
| `tests/Feature/AuthzTest.php` | Feature | 41 | Matriks otorisasi 3 peran (Admin, Dosen, Mahasiswa), proteksi IDOR, dan eskalasi peran |
| `tests/Feature/NotificationQueueTest.php` | Feature | 10 | Event/Listener antrean (`ShouldQueue`), notifikasi tugas/nilai, rekap nilai, paginasi & guard `/my-grades` |
| `tests/Feature/ApiTest.php` | Feature | 9 | Autentikasi token Sanctum, endpoint REST API, dan pembatasan akses API |
| `tests/Feature/FileStorageTest.php` | Feature | 8 | Unggah & unduh berkas privat (`Storage::fake('local')`), validasi MIME/ukuran, dan anti-IDOR unduhan |
| `tests/Feature/RubricTieredGradingTest.php` | Feature | 7 | CRUD rubrik bertingkat, validasi total bobot 100%, dan kalkulasi skor otomatis |
| `tests/Feature/EnrollmentAndGradingTest.php` | Feature | 6 | Enrollment kelas, materi teks, hapus nilai (`destroyGrade`), dan paginasi/agregasi pengumpulan dosen |
| `tests/Feature/ExampleTest.php` | Feature | 1 | Smoke test halaman utama aplikasi |
| `tests/Unit/ExampleTest.php` | Unit | 1 | Unit test dasar PHPUnit |
| **Total** | | **83** | **Terintegrasi otomatis pada `.github/workflows/ci.yml`** |

Pada pipeline CI (`.github/workflows/ci.yml`), GitHub Actions menjalankan instance **MySQL 8.0**, mengeksekusi `php artisan migrate:fresh --seed --force`, lalu menjalankan `php artisan test` dan skrip verifikasi otorisasi `./scripts/test-authz.sh`.

### 2. Membaca Keluaran Test yang Gagal
Saat sebuah test gagal di PHPUnit, aturan utama membaca *output* adalah melihat **pesan assertion** dan **baris pertama yang menunjuk kode milik kita (`app/` atau `tests/`)**, bukan baris di dalam `vendor/`.
* Contoh dari pengujian `NotificationQueueTest` saat `bootstrap/cache/config.php` masih aktif:
  ```text
  Expected response status code [201, 301, 302, 303, 307, 308] but received 419.
  at tests\Feature\NotificationQueueTest.php:237
  ```
* Dari kode status `419` (CSRF Token Mismatch), langsung diketahui bahwa `APP_ENV=testing` tidak aktif saat test berjalan karena tertutup oleh cache konfigurasi lokal (`bootstrap/cache/config.php`). Setelah menjalankan `php artisan config:clear`, seluruh 10/10 test di `NotificationQueueTest` langsung lulus hijau.

### 3. Menelusuri Stack Trace di `storage/logs/laravel.log`
Contoh exception nyata yang ditemui dan ditelusuri melalui halaman error & log Laravel:
* **Pesan Error:**
  ```text
  Illuminate\Database\QueryException
  SQLSTATE[42S02]: Base table or view not found: 1146 Table 'kampuslms-kelompok-02.rubrics' doesn't exist
  ```
* **Frame teratas (framework):** `vendor/laravel/framework/src/Illuminate/Database/Connection.php:838` → *diabaikan karena hanya titik lempar exception PDO*.
* **Baris pertama di `app/`:** `app/Http/Controllers/AssignmentController.php:77` (`$assignment->load(['course', 'rubric.criteria.levels', ...])`).
* **Akar Masalah:** Setelah diperiksa dengan `php artisan migrate:status`, migrasi `2026_11_18_000001_create_rubrics_and_criteria_tables` berstatus `Pending` (belum dijalankan di MySQL lokal). Menjalankan `php artisan migrate` langsung menyelesaikan masalah secara tuntas.

### 4. Pengamatan Query Rekap Nilai (`DB::listen()`)
Saat memantau query pada `/courses/{course}/grades` dan `/assignments/{assignment}/submissions`:
* **Sebelum optimasi:** Query menarik seluruh mahasiswa dengan `->get()` dan seluruh baris `submissions` untuk mata kuliah/tugas tersebut tanpa batas, sehingga konsumsi memori meningkat linear terhadap total mahasiswa × tugas.
* **Sesudah optimasi:** Query mahasiswa menggunakan `LIMIT 15 OFFSET ...` (`paginate($perPage)`), dan query `submissions` dibatasi dengan `where user_id in (...)` khusus mahasiswa di halaman aktif. Tidak ada query N+1 karena relasi `grade`, `student`, dan `lecturer` sudah dimuat dengan *eager loading* (`with()` dan `loadMissing()`), ditambah proteksi `Model::preventLazyLoading(! app()->isProduction())` di `AppServiceProvider`.

---

## Bagian BREAK: 7 Percobaan Eksperimen Mandiri



## Bagian FIX: Pola Perbaikan Test yang Menipu



## Bagian BUILD: Pengembangan Test Suite & Dokumentasi 3 Bug Nyata


### C. Catatan Pengerjaan Perbaikan Temuan Peer Review (PR #55 & PR #49)

Selain menulis pengujian, saya juga memverifikasi dan mengeksekusi langsung seluruh **6 temuan** dari kelompok reviewer pada **PR #55** (Rekap Nilai & Notifikasi) dan **PR #49** (Pengumpulan Tugas & Penilaian), dengan memastikan seluruh perbaikan tetap selaras dengan ketentuan modul dan tidak merusak fitur existing:

#### 1. Tindak Lanjut 3 Temuan PR #55 (Modul Rekap Nilai)
| Kategori | Lokasi & Masalah Awal | Perbaikan yang Dikerjakan | Verifikasi |
| :--- | :--- | :--- | :--- |
| **Keamanan** | `routes/web.php` & `GradeController::studentGrades()` belum menolak Dosen/Admin pada `/my-grades`. | Memasang middleware `role:mahasiswa` (`EnsureUserHasRole`) pada rute `/my-grades` **dan** guard `abort_unless($user && $user->role === 'mahasiswa', 403)` di baris awal controller, sambil mempertahankan eager-load `lecturer` dan filter `status != 'draft'`. | Dosen & Admin ditolak `403 Forbidden`; Mahasiswa tetap `200 OK` (`NotificationQueueTest`). |
| **Performa** | `GradeController::courseGrades()` menarik seluruh mahasiswa & submission sekaligus (`->get()`). | Mengubah ke paginasi adaptif `paginate($perPage)->withQueryString()` (default 15, opsi 15/25/50 per halaman), membatasi submission dengan `whereIn('user_id', $students->pluck('id'))`, menambah `$course->loadMissing('lecturer')`, serta memperbaiki `$students->total()`, `$students->firstItem() + $index`, dan `{{ $students->links() }}` di `grades/course.blade.php`. | Halaman 2 melanjutkan nomor urut, total mahasiswa tetap akurat, dan tombol paginasi muncul otomatis. |
| **Struktur MVC** | `resources/views/grades/course.blade.php` memuat 3 blok `@php` untuk lookup submission, penjumlahan skor, warna badge, dan rata-rata. | Memindahkan seluruh perhitungan ke `GradeController::courseGrades()` menjadi variabel `$matrix` (pengecekan nilai via `$grade !== null` sehingga nilai `0.0` tidak berubah jadi `-`). Tabel di Blade kini **0 blok `@php`**. | Nilai `0.0`, badge warna, link detail submission, dan rata-rata teruji akurat. |

#### 2. Tindak Lanjut 3 Temuan PR #49 (Modul Pengumpulan & Penilaian)
| Kategori | Lokasi & Masalah Awal | Perbaikan yang Dikerjakan | Verifikasi |
| :--- | :--- | :--- | :--- |
| **Keamanan** | `SubmissionController::destroyGrade()` menaruh `Gate::authorize()` di dalam `if ($submission->grade)`, sehingga terlewati saat nilai masih `null`. | Menambahkan `$submission->loadMissing(['assignment.course', 'grade'])` dan `Gate::authorize('update', $submission->assignment->course)` di baris paling awal method sebelum `if ($submission->grade)`. | Teruji `403 Forbidden` saat Mahasiswa/Dosen lain menembak `DELETE /submissions/{id}/grades` meski nilai `null` (dibuktikan via `EnrollmentAndGradingTest` & DevTools Network `redirect: 'manual'`). |
| **Performa** | `SubmissionController::index()` blok Dosen/Admin memakai `->get()` tanpa paginasi dan `sortByDesc()` di PHP memory. | Mengubah ke paginasi adaptif `paginate($perPage)->withQueryString()` (default 15, opsi 15/25/50), memindahkan urutan *"sudah mengumpulkan di atas, lalu urut nama"* ke level SQL (`orderByDesc` subquery), membatasi submission dengan `whereIn('user_id', $enrolledStudents->pluck('id'))`, dan memasang `{{ $enrolledStudents->links() }}` di `submissions/index.blade.php`. | Daftar dosen rapi baik di desktop maupun mobile cards, urutan lintas halaman tetap konsisten. |
| **Struktur MVC** | `resources/views/submissions/index.blade.php` menghitung `$totalEnrolled`, `$submittedCount`, dan `$pendingGrading` lewat `@php`. | Memindahkan ketiga agregasi ke `SubmissionController::index()` dan menghapus blok `@php` di header Blade. | Angka ringkasan kelas di header tetap akurat untuk seluruh mahasiswa meski sedang berada di halaman 2. |

#### 3. Ringkasan Berkas yang Dimodifikasi & Hasil Uji
* **Berkas PR #55:** `routes/web.php`, `app/Http/Controllers/GradeController.php`, `resources/views/grades/course.blade.php`, `tests/Feature/NotificationQueueTest.php` (**10/10 passed**, 46 assertions).
* **Berkas PR #49:** `app/Http/Controllers/SubmissionController.php`, `resources/views/submissions/index.blade.php`, `tests/Feature/EnrollmentAndGradingTest.php` (**5/5** uji enrollment, grading, `destroyGrade`, & paginasi lulus di in-memory SQLite).
* **Balasan Peer Review:** Seluruh 6 temuan pada PR #55 dan PR #49 sudah ditanggapi secara rinci pada komentar GitHub beserta penjelasan teknis perbaikannya.

---

## Bagian CHECKPOINT Minggu 14 (Refleksi & Evaluasi)

### 1. Peragaan: Hapus satu fitur, tunjukkan test yang menjadi merah karenanya
Jika baris `Gate::authorize('update', $submission->assignment->course);` dihapus dari `SubmissionController::destroyGrade()`, lalu menjalankan `EnrollmentAndGradingTest`, maka test `test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null` langsung **merah** (`Expected response status code [403] but received 302`). Ini membuktikan test benar-benar menjaga perilaku keamanan yang dimaksud.

### 2. Kenapa test yang hanya `assertStatus(200)` sering tidak bermakna?
Karena HTTP `200 OK` hanya menandakan server berhasil mengembalikan respons tanpa *crash* (`500`). Halaman bisa saja kosong, menampilkan data milik pengguna lain, gagal menyimpan ke database, atau salah menghitung rata-rata nilai, namun `assertStatus(200)` tetap meloloskannya sebagai hijau (*false positive*). Test yang bermakna harus memeriksa isi (`assertSee`), state database (`assertDatabaseHas`), atau efek samping (`Notification::assertSentTo`, `Storage::assertExists`).

### 3. Kenapa test jalur ditolak lebih penting daripada test jalur sukses di aplikasi ini?
Jalur sukses biasanya langsung ketahuan rusak saat kita memakai aplikasi secara manual (misal tombol simpan tidak jalan). Sebaliknya, **celah keamanan (jalur ditolak)** tidak pernah terlihat dalam pemakaian normal — pengguna biasa tidak menembak URL `/submissions/5/grades` atau `/my-grades` memakai peran berbeda. Tanpa test jalur ditolak otomatis yang memeriksa `403 Forbidden`, celah IDOR dan kebocoran data bisa lolos diam-diam ke produksi setiap kali ada perubahan kode.

### 4. Apa fungsi `RefreshDatabase`? Kenapa migrasi harus reversible agar ia bekerja?
`RefreshDatabase` menjamin setiap kasus uji dimulai dengan kondisi skema dan data yang benar-benar bersih serta terisolasi (dibungkus transaksi / reset migrasi), sehingga test A tidak pernah mengotori hasil test B. Migrasi harus *reversible* (memiliki method `down()` yang benar-benar membalikkan `up()`) agar proses rollback dan pembangunan ulang tabel saat reset database di lingkungan testing maupun CI tidak gagal oleh error *table already exists* atau *foreign key constraint*.

### 5. Ceritakan satu bug nyata: bagaimana cara mempersempit penyebabnya?
Pada bug `destroyGrade()` saat nilai `null`, gejala awalnya tersamarkan karena `fetch()` default mengembalikan `403`. Saya mempersempitnya dengan memisahkan respons request `DELETE` dari request *redirect* `GET` menggunakan opsi `redirect: 'manual'` dan memantau tab **Network** di DevTools. Dari situ terlihat jelas bahwa `DELETE /submissions/{id}/grades` sebenarnya lolos dengan `302 Found`, lalu pemeriksaan kode di `SubmissionController::destroyGrade()` langsung mengonfirmasi bahwa `Gate::authorize()` terbungkus di dalam `if ($submission->grade)`.

### 6. Kenapa `dd()` tidak boleh masuk PR, sementara `Log::info()` boleh?
* **`dd()` (*dump and die*)** langsung menghentikan eksekusi PHP dan mencetak isi variabel/struktur internal mentah ke layar browser pengguna. Jika lolos ke PR atau produksi, fitur langsung lumpuh, CI/PHPUnit berhenti mendadak di tengah jalan, dan data sensitif berisiko terekspos ke pengguna.
* **`Log::info()`** tidak menghentikan alur aplikasi dan tidak menampilkan apa pun ke layar pengguna; ia hanya mencatat jejak diagnostik secara aman ke berkas log server (`storage/logs/laravel.log` atau Railway Logs) yang hanya bisa diakses oleh pengembang/administrator.

