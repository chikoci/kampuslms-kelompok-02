# Catatan Praktikum Minggu 14: Debugging dan Testing

**Nama:** Dawwas Eryansyah Pratama  
**NIM:** 10241019  
**Kelompok:** Kelompok 02  
**Peran Utama Proyek:** Otorisasi Tugas (Assignments), Pengumpulan Tugas (Submissions), Penilaian & Nilai (Grading), Indexing Database, serta UI/UX Portal Mahasiswa.

---

## Bagian READ: Membaca Kegagalan (Pengamatan Langsung di Proyek)

Pada tahap READ ini, saya mengaudit test suite proyek KampusLMS Kelompok 02 setelah sinkronisasi branch `main`, melatih pembacaan keluaran kegagalan PHPUnit, menelusuri riwayat exception pada `storage/logs/laravel.log`, dan mengamati query SQL halaman rekap nilai serta daftar pengumpulan menggunakan `DB::listen()`.

### 1. Hasil Audit & Eksekusi `php artisan test`
Perintah pengujian di root proyek `kampuslms-kelompok-02`:
```bash
php artisan test
```
* **Jumlah seluruh test (kondisi terbaru setelah merge `main` + revisi PR #49 & #55):** **101 test** (**1 Unit test + 100 Feature test**) tersebar pada **9 berkas test** (jauh melampaui target minimal 20 feature test pada modul).
* **Sebaran berkas test saat ini:**
  - `tests/Feature/AuthzTest.php`: 42 test *(matriks otorisasi 3 peran, proteksi IDOR, & anti eskalasi peran)*
  - `tests/Feature/PrioritySecurityAndFlowTest.php`: 13 test *(10 test prioritas utama + 3 pelengkap celah `docs/keamanan.md`)*
  - `tests/Feature/NotificationQueueTest.php`: 11 test *(event/listener antrean, guard `/my-grades`, paginasi rekap nilai & skor `0.0`)*
  - `tests/Feature/RubricTieredGradingTest.php`: 10 test *(CRUD rubrik, proteksi hapus rubrik dinilai, & formula skor)*
  - `tests/Feature/ApiTest.php`: 9 test *(autentikasi token Sanctum & endpoint REST API)*
  - `tests/Feature/FileStorageTest.php`: 8 test *(unggah/unduh berkas privat via `Storage::fake('local')`, validasi MIME/ukuran, & anti-IDOR)*
  - `tests/Feature/EnrollmentAndGradingTest.php`: 6 test *(enrollment, hapus nilai `destroyGrade` 403 saat `grade === null`, & paginasi/agregasi pengumpulan dosen)*
  - `tests/Feature/ExampleTest.php`: 1 test
  - `tests/Unit/ExampleTest.php`: 1 test
* **Integrasi CI (`.github/workflows/ci.yml`):** Pipeline GitHub Actions menjalankan service **MySQL 8.0**, mengeksekusi `php artisan migrate:fresh --seed --force`, lalu menjalankan `php artisan test` dan `./scripts/test-authz.sh`.

### 2. Mengamati Keluaran Saat Test Gagal
Saat menjalankan `php artisan test` pada `NotificationQueueTest` dan `EnrollmentAndGradingTest`, saya mengamati langsung cara membaca keluaran kegagalan Laravel Collision / PHPUnit:

```text
   FAILED  Tests\Feature\NotificationQueueTest > student can view and mark own notification as read
  Expected response status code [201, 301, 302, 303, 307, 308] but received 419.
Failed asserting that false is true.

  at tests\Feature\NotificationQueueTest.php:237
    235▕         // Tandai sudah dibaca
    236▕         $postResponse = $this->actingAs($this->student1)->post(route('notifications.read', $notification->id));
  ➜ 237▕         $postResponse->assertRedirect();
```
* **Analisis:** Laravel langsung menampilkan selisih status (`Expected redirect, received 419`) dan menunjuk baris pasti di berkas kita dengan tanda panah `➜ 237`. Kode `419` (CSRF Token Mismatch) mengarahkan diagnosis pada `bootstrap/cache/config.php` lokal yang masih aktif menimpa `APP_ENV=testing`. Setelah menjalankan `php artisan config:clear`, seluruh **10/10** test di `NotificationQueueTest` langsung berubah menjadi **PASS hijau**.

### 3. Penelusuran Exception pada `storage/logs/laravel.log`
Dari pemeriksaan error nyata dan catatan `storage/logs/laravel.log` selama pengembangan:

1. **Error tabel rubrik belum termigrasi (`SQLSTATE[42S02]`):**
   ```text
   Illuminate\Database\QueryException
   SQLSTATE[42S02]: Base table or view not found: 1146 Table 'kampuslms-kelompok-02.rubrics' doesn't exist
   ...
   #14 app/Http/Controllers/AssignmentController.php(77): Illuminate\Database\Eloquent\Model->load(Array)
   ```
   * **Baris pertama yang menunjuk `app/`:** **`app/Http/Controllers/AssignmentController.php` baris 77** (setelah 14 frame `vendor/`). Pengecekan `php artisan migrate:status` menunjukkan migrasi `2026_11_18_000001_create_rubrics_and_criteria_tables` berstatus `Pending`. Setelah `php artisan migrate`, halaman `/assignments/1` langsung normal.
2. **Error pelanggaran lazy loading N+1 (`2026-11-05 12:00:26`):**
   ```text
   local.ERROR: Attempted to lazy load [lecturer] on model [App\Models\Course] but lazy loading is disabled.
   (View: ...\resources\views\courses\index.blade.php / grades\student.blade.php)
   ```
   * **Pelajaran:** `Model::preventLazyLoading(! app()->isProduction())` di `AppServiceProvider` langsung menangkap relasi yang terlupa (`lecturer`) beserta nama berkas view pemanggilnya.

### 4. Pengamatan Query Halaman Rekap Nilai & Pengumpulan via `DB::listen()`
Pengamatan query pada `/courses/{course}/grades` dan `/assignments/{assignment}/submissions` sebagai Dosen pengampu:
* **Sebelum revisi PR #55 & #49:** Query mahasiswa menarik seluruh peserta kelas dengan `->get()` tanpa batas, dan query `submissions` memuat seluruh pengumpulan mata kuliah sekaligus ke RAM.
* **Sesudah revisi:**
  1. Daftar mahasiswa memakai `paginate($perPage)->withQueryString()` (`LIMIT 15 OFFSET ...`, dengan opsi adaptif `15 / 25 / 50` per halaman).
  2. Query `submissions` dibatasi dengan `where user_id in (...)` **hanya** untuk ID mahasiswa yang tampil di halaman aktif, disertai eager loading `with('grade')` dan `$course->loadMissing('lecturer')`.
  3. Pada `SubmissionController::index()`, pengurutan agar mahasiswa yang sudah mengumpulkan tampil paling atas dipindah ke level SQL (`orderByDesc` subquery) sehingga konsisten lintas halaman tanpa memuat semua baris ke memori PHP.
* **Kesimpulan:** Halaman rekap nilai dan pengumpulan tugas kini bebas N+1, hemat memori RAM, dan tetap cepat meskipun jumlah mahasiswa dalam kelas bertambah besar.

---

## Bagian BREAK: Tujuh Percobaan Merusak Test

Pengamatan dari 7 percobaan eksperimen untuk membuktikan perbedaan test yang bermakna versus test yang menipu:

| # | Yang Dicoba | Hasil yang Diamati | Pelajaran Penting |
| :-: | :--- | :--- | :--- |
| 1 | Tulis test yang hanya `assertStatus(200)`, lalu hapus seluruh isi controller-nya | **Test tetap hijau (*false positive*)** meski konten & logika hilang. | Status `200` saja tidak cukup; wajib disertai `assertSee`, `assertViewHas`, atau `assertDatabaseHas`. |
| 2 | Hapus `Gate::authorize()` dari satu controller (`SubmissionController::destroyGrade` / `download`), lalu jalankan test | Test otorisasi langsung **merah** (`Expected 403, received 302/200`). | Membuktikan test benar-benar menjaga keamanan; jika tetap hijau, berarti celah belum tertutup test. |
| 3 | Hapus `RefreshDatabase`, jalankan seluruh test dua kali berurutan | Run kedua gagal akibat data sisa bentrok *unique email* / *unique composite* `(assignment_id, user_id)`. | Setiap test wajib terisolasi pada database bersih agar hasil deterministik. |
| 4 | Tulis test yang bergantung pada data seeder (`User::find(1)` / status acak) | Rentan gagal di CI maupun laptop berbeda karena `DatabaseSeeder` mengacak 60% data nilai. | Selalu buat data uji mandiri memakai **Model Factory**. |
| 5 | Hapus `Storage::fake('local')`, jalankan test unggah berkas | Berkas uji sungguhan menumpuk di `storage/app/private/submissions/`. | Gunakan `Storage::fake()`, `Notification::fake()`, dan `Event::fake()` agar bebas efek samping. |
| 6 | Rusak method `down()` pada satu berkas migrasi, lalu jalankan test | Proses reset `RefreshDatabase` / rollback migrasi gagal. | Migrasi wajib *reversible* (`up()` dan `down()` seimbang). |
| 7 | Tinggalkan `dd()` di dalam controller, lalu jalankan `php artisan test` | Eksekusi PHPUnit langsung berhenti mendadak dan seluruh test setelahnya gagal berjalan. | `dd()` / `dump()` dilarang keras masuk PR; gunakan `Log::info()` untuk pencatatan diagnostik. |

---

## Bagian FIX: Repo Cacat `kampuslms-broken` Branch `w14`

* **Status & Prinsip Perbaikan:** Dikoordinasikan pada pembagian tugas kelompok, dengan menerapkan 5 prinsip anti-test menipu ke seluruh test suite proyek KampusLMS:
  1. Melengkapi test `assertStatus(200)` dengan assertion isi (`assertSee`) dan efek database (`assertDatabaseHas` / `assertDatabaseMissing`).
  2. Menghapus ketergantungan pada data seeder atau urutan test sebelumnya (`RefreshDatabase` + Factory).
  3. Memeriksa status otorisasi secara eksplisit dengan **`assertStatus(403)` / `assertForbidden()`** (bukan sekadar "tidak 200").
  4. Membuktikan kualitas test dengan *mutation check* (merusak fitur/otorisasi sementara dan memastikan test berubah menjadi merah).

---

## Bagian BUILD: Tindak Lanjut Peer Review (PR #55 & PR #49), Feature Test, & Debugging

Seluruh syarat BUILD Minggu 14 pada proyek KampusLMS Kelompok 02 kini telah terpenuhi: **101 test** (jauh di atas minimal 20 feature test), seluruh **15 baris `docs/keamanan.md`** sudah dilengkapi kolom **`Test`**, **`docs/debugging.md`** sudah terdokumentasi di repositori, dan pipeline CI menjalankan `php artisan test`.

### A. Tindak Lanjut 6 Temuan Peer Review (PR #55 & PR #49)

#### 1. Perbaikan 3 Temuan PR #55 (Modul Rekap Nilai — `GradeController`)
| Kategori | Lokasi & Masalah Awal | Perbaikan yang Dikerjakan | Verifikasi |
| :--- | :--- | :--- | :--- |
| **Keamanan** | `routes/web.php` & `GradeController::studentGrades()` belum menolak Dosen/Admin pada `/my-grades`. | Memasang middleware `role:mahasiswa` (`EnsureUserHasRole`) di `routes/web.php` **dan** guard `abort_unless($user && $user->role === 'mahasiswa', 403)` di baris awal controller, sambil mempertahankan eager-load `'lecturer'` dan filter `where('status', '!=', 'draft')`. | Dosen & Admin ditolak **`403 Forbidden`**; Mahasiswa tetap `200 OK` (`NotificationQueueTest`). |
| **Performa** | `GradeController::courseGrades()` menarik seluruh mahasiswa & submission sekaligus (`->get()`). | Mengubah ke paginasi adaptif `paginate($perPage)->withQueryString()` (default 15, opsi *allowlist* `[15, 25, 50]`), membatasi submission dengan `whereIn('user_id', $students->pluck('id'))`, menambah `$course->loadMissing('lecturer')`, serta memperbarui `$students->total()`, `$students->firstItem() + $index`, dan `{{ $students->links() }}` di `grades/course.blade.php`. | Total mahasiswa akurat, halaman 2 lanjut nomor urut, dan query submission terbatas pada halaman aktif. |
| **Struktur MVC** | `resources/views/grades/course.blade.php` memuat 3 blok `@php` untuk lookup submission, penjumlahan skor, warna badge, dan rata-rata. | Memindahkan seluruh perhitungan ke `GradeController::courseGrades()` dalam variabel `$matrix` (pengecekan nilai via `$grade !== null` agar nilai `0.0` tetap tampil akurat, tidak berubah jadi `-`). Tabel Blade kini **0 blok `@php`**. | Nilai `0.0`, badge warna, tautan detail submission, dan rata-rata teruji akurat. |

#### 2. Perbaikan 3 Temuan PR #49 (Modul Pengumpulan & Penilaian — `SubmissionController`)
| Kategori | Lokasi & Masalah Awal | Perbaikan yang Dikerjakan | Verifikasi |
| :--- | :--- | :--- | :--- |
| **Keamanan** | `SubmissionController::destroyGrade()` menaruh `Gate::authorize()` di dalam `if ($submission->grade)`, sehingga terlewati saat nilai masih `null`. | Menambahkan `$submission->loadMissing(['assignment.course', 'grade'])` dan `Gate::authorize('update', $submission->assignment->course)` di baris paling awal method (sebelum `if ($submission->grade)`), lalu tetap menjaga `Gate::authorize('delete', $submission->grade)` saat nilai ada. | Mahasiswa & Dosen MK lain langsung ditolak **`403 Forbidden`** meski `grade === null` (dibuktikan via PHPUnit & DevTools Network `redirect: 'manual'`). |
| **Performa** | `SubmissionController::index()` blok Dosen/Admin memakai `->get()` tanpa paginasi dan `sortByDesc()` di PHP memory. | Mengubah ke paginasi adaptif `paginate($perPage)->withQueryString()` (default 15, opsi `[15, 25, 50]`), memindahkan urutan *"sudah kumpul tampil paling atas, lalu urut nama"* ke level SQL (`orderByDesc` subquery), membatasi `$submissions` dengan `whereIn('user_id', $enrolledStudents->pluck('id'))`, dan menambah `{{ $enrolledStudents->links() }}` di `submissions/index.blade.php`. | Tampilan desktop table & mobile cards tetap rapi dan urutan lintas halaman konsisten. |
| **Struktur MVC** | `resources/views/submissions/index.blade.php` menghitung `$totalEnrolled`, `$submittedCount`, dan `$pendingGrading` lewat `@php`. | Memindahkan ketiga agregasi ke `SubmissionController::index()` dan menghapus blok `@php` agregasi di header Blade. | Angka ringkasan kelas di header tetap akurat untuk seluruh mahasiswa meski sedang di halaman 2. |

### B. Penambahan Feature Test yang Dikerjakan
* **`NotificationQueueTest::test_student_can_view_own_grades_transcript`** *(diperkuat)*: memverifikasi Mahasiswa melihat nilai & feedback (`200 OK`), sementara Dosen dan Admin ditolak **`403 Forbidden`** di `/my-grades`.
* **`NotificationQueueTest::test_course_grades_supports_pagination_and_zero_score`** *(baru)*: memverifikasi paginasi adaptif (`per_page=25`), variabel `$matrix` dari Controller, serta tampilan skor **`0.0`**.
* **`EnrollmentAndGradingTest::test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null`** *(baru)*: memverifikasi Mahasiswa dan Dosen mata kuliah lain ditolak **`403 Forbidden`** saat memanggil `DELETE /submissions/{id}/grades` pada submission yang belum dinilai (`grade === null`).
* **`EnrollmentAndGradingTest::test_lecturer_submission_index_uses_pagination_and_controller_aggregates`** *(baru)*: memverifikasi paginasi daftar pengumpulan dosen dan variabel agregasi (`$totalEnrolled`, `$submittedCount`, `$pendingGrading`, `$perPage`) dari Controller.

### C. Ringkasan Hasil Pengujian & Sinkronisasi Berkas
* **Berkas PR #55 (`e7c2551`):** `routes/web.php`, `app/Http/Controllers/GradeController.php`, `resources/views/grades/course.blade.php`, `tests/Feature/NotificationQueueTest.php` (**10/10 passed**, 46 assertions).
* **Berkas PR #49 (`44ac7b9`):** `app/Http/Controllers/SubmissionController.php`, `resources/views/submissions/index.blade.php`, `tests/Feature/EnrollmentAndGradingTest.php` (**5/5** uji enrollment, grading, `destroyGrade`, & paginasi lulus di in-memory SQLite).
* **Sinkronisasi `main` (`899ecae`):** Tergabung bersih tanpa konflik dengan `docs/debugging.md`, pembaruan kolom `Test` di `docs/keamanan.md`, `PrioritySecurityAndFlowTest.php` (Chiko), serta optimasi batch upsert rubrik (Arman).

---

## Bagian CHECKPOINT: Gerbang Wawancara Materi 14 (14.4)

Panduan jawaban ringkas dan langkah peragaan langsung untuk wawancara Checkpoint 14 bersama dosen:

---

### 1. Peragakan: hapus satu fitur, tunjukkan test yang menjadi merah karenanya.
* **Fitur yang Dimatikan Sementara:** Pengecekan otorisasi awal pada penghapusan nilai di `app/Http/Controllers/SubmissionController.php` baris `349` (`Gate::authorize('update', $submission->assignment->course);`).
* **Test Penjaganya:** `test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null` di `tests/Feature/EnrollmentAndGradingTest.php`.
* **Langkah Peragaan (Step-by-Step):**
  1. **Jalankan dulu dalam kondisi normal:**
     ```bash
     php artisan test --filter=test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null
     ```
     *(Hasil **PASS hijau** karena Mahasiswa & Dosen lain ditolak `403` meski nilai masih `null`).*
  2. **Matikan otorisasi awal sementara:** Buka `app/Http/Controllers/SubmissionController.php` baris `349`, beri komentar `//`:
     ```php
     // Gate::authorize('update', $submission->assignment->course);
     ```
     Simpan (`Ctrl + S`).
  3. **Jalankan test yang sama lagi:**
     ```bash
     php artisan test --filter=test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null
     ```
     *(Hasil langsung **FAILED merah**: `Expected response status code [403] but received 302.`, membuktikan request hapus nilai lolos tanpa diblokir saat `grade === null`).*
  4. **Kembalikan kode semula:** Hapus `//` di baris `349`, simpan, lalu jalankan test sekali lagi agar kembali **PASS hijau**.
* **Kalimat ke Dosen:**  
  *"Ini Pak/Bu, awalnya test proteksi hapus nilai ini hijau. Begitu saya matikan `Gate::authorize` di awal `SubmissionController::destroyGrade` baris 349, test-nya langsung merah karena mahasiswa yang menembak endpoint hapus nilai saat grade masih null malah dapat 302 redirect, bukan 403. Saat saya aktifkan lagi, test kembali hijau."*

---

### 2. Kenapa test yang hanya `assertStatus(200)` sering tidak bermakna?
* **Jawabannya:**  
  Status `200 OK` hanya membuktikan server tidak *crash* (`500`), **tidak membuktikan isi halaman maupun efek ke database benar**. Jika isi controller dikosongkan, atau rata-rata nilai salah hitung (misal skor `0.0` berubah jadi `-`), atau tugas draft dosen bocor ke mahasiswa, test yang hanya mengecek `assertStatus(200)` tetap hijau (*false positive*). Karena itu test harus memeriksa konten (`assertSee`/`assertDontSee`), variabel view (`assertViewHas`), atau perubahan database (`assertDatabaseHas`).

---

### 3. Kenapa test jalur ditolak lebih penting daripada test jalur sukses di aplikasi ini?
* **Jawabannya:**  
  Kalau jalur sukses yang rusak (misal tombol simpan nilai error), pengguna langsung sadar dan melapor karena fiturnya tidak bisa dipakai. Sebaliknya, jika **jalur ditolak (keamanan) yang jebol** — misalnya Dosen bisa masuk `/my-grades` atau Mahasiswa menembak `DELETE /submissions/{id}/grades` — aplikasi tetap tampak berjalan normal tanpa error, sehingga celah kebocoran data bisa lolos diam-diam ke produksi. Dengan feature test `403 Forbidden`, seluruh kombinasi peran × endpoint terjaga otomatis setiap kali ada perubahan kode.

---

### 4. Apa fungsi `RefreshDatabase`? Kenapa migrasi harus reversible agar ia bekerja?
* **Jawabannya:**  
  - **Fungsi `RefreshDatabase`:** Menjamin setiap test dimulai dari kondisi database yang bersih dan terisolasi, sehingga data dari satu test tidak mengotori atau memengaruhi test berikutnya.
  - **Kenapa migrasi harus reversible (`down()`):** Karena untuk mereset skema database secara bersih pada saat testing maupun rollback, Laravel bergantung pada method `down()` untuk membalikkan perubahan `up()`. Jika ada satu migrasi yang `down()`-nya rusak atau terhalang *foreign key*, proses reset akan gagal dan seluruh test macet.
* **Tempat Tunjuk:** Baris `use RefreshDatabase;` pada `tests/Feature/EnrollmentAndGradingTest.php` baris `16` (dan `tests/Feature/NotificationQueueTest.php`).

---

### 5. Buka `docs/debugging.md`. Ceritakan satu bug: bagaimana Anda mempersempit penyebabnya?
* **Tempat Peragaan:** Buka `docs/debugging.md` di VS Code, arahkan ke **Bug 3: `LazyLoadingViolationException` (`Attempted to lazy load [lecturer]`)** — yang langsung berkaitan dengan modul `GradeController::studentGrades()` dan `CourseController::index()`.
* **Cara Menjelaskan ke Dosen (Singkat & Sistematis):**  
  *"Gejalanya setelah `Model::preventLazyLoading()` kami aktifkan di `AppServiceProvider`, halaman Daftar Mata Kuliah dan Transkrip Nilai (`/my-grades`) tiba-tiba error 500.*
  *Cara mempersempit penyebabnya:*
  1. *Pertama baca `storage/logs/laravel.log`: pesan error langsung menyebutkan relasi yang terlupa (`lecturer`) pada model `Course` dan file view pemanggilnya (`courses/index.blade.php` & `grades/student.blade.php`).*
  2. *Kedua cek tab **Queries** di Laravel Debugbar: terlihat query berulang `select * from users where id = ?` setiap baris mata kuliah di dalam loop `@foreach`.*
  3. *Ketiga periksa `CourseController::index()` dan `GradeController::studentGrades()`: memang relasi `lecturer` belum dimasukkan ke `with()`.*
  *Setelah ditambahkan eager loading `'lecturer'`, error hilang, query N+1 tuntas, dan sekarang dicegah regresi oleh `AuthzTest::test_course_index_is_scoped_by_role` serta `NotificationQueueTest::test_student_can_view_own_grades_transcript`."*
* *(Tambahan bug nyata dari revisi minggu ini bila dosen menanyakan contoh lain: bug bypass otorisasi `destroyGrade()` saat `grade === null`, yang awalnya tersamarkan jadi `403` karena `fetch()` otomatis mengikuti `302 Redirect`, lalu berhasil dibuktikan lewat `redirect: 'manual'` di tab Network dan ditutup dengan `EnrollmentAndGradingTest::test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null`.)*

---

### 6. Kenapa `dd()` tidak boleh masuk PR, sementara `Log::info()` boleh?
* **Jawabannya:**  
  - **`dd()` (*Dump and Die*)** langsung menghentikan eksekusi PHP dan mencetak struktur variabel internal mentah ke layar pengguna — jika lolos ke PR/produksi, halaman langsung rusak, data rahasia bisa bocor, dan eksekusi `php artisan test` di CI ikut berhenti mendadak.
  - **`Log::info()`** hanya menulis catatan diagnostik secara diam-diam ke berkas privat server (`storage/logs/laravel.log` / Railway Logs) tanpa menghentikan aplikasi ataupun mengganggu tampilan pengguna.
