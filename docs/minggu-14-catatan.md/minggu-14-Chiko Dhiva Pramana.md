# Catatan Praktikum Minggu 14: Debugging dan Testing

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Membaca Kegagalan (Pengamatan Langsung di Proyek)

Pada tahap READ ini, saya menjalankan test suite proyek KampusLMS Kelompok 02 secara langsung di lokal, menguji simulasi kegagalan test untuk membaca jejak baris error, menelusuri riwayat exception pada `storage/logs/laravel.log`, dan merekam query SQL halaman rekap nilai menggunakan `DB::listen()`.

### 1. Hasil Eksekusi `php artisan test`
Perintah yang dijalankan di root proyek `kampuslms-kelompok-02`:
```bash
php artisan test
```
* **Jumlah seluruh test:** **80 test** (1 Unit test + 79 Feature test, total **242 assertions**).
* **Status:** **80 passed, 0 failed** (durasi eksekusi `34.61s`).
* **Sebaran berkas test saat ini:**
  - `tests/Unit/ExampleTest.php`: 1 test
  - `tests/Feature/AuthzTest.php`: 41 test
  - `tests/Feature/ApiTest.php`: 9 test
  - `tests/Feature/NotificationQueueTest.php`: 9 test
  - `tests/Feature/FileStorageTest.php`: 8 test
  - `tests/Feature/RubricTieredGradingTest.php`: 7 test
  - `tests/Feature/EnrollmentAndGradingTest.php`: 4 test
  - `tests/Feature/ExampleTest.php`: 1 test

### 2. Mengamati Keluaran Saat Satu Test Sengaja Dibuat Gagal
Saya menjalankan simulasi pengujian gagal yang mengirim `GET /login` namun dipaksa mengharapkan status `403` (`$response->assertStatus(403)`), padahal `/login` mengembalikan `200`.

Keluaran terminal PHPUnit / Laravel Collision:
```text
   FAILED  Tests\Feature\ReadFailDemoTest > intentional failure demo
  Expected response status code [403] but received 200.
Failed asserting that 200 is identical to 403.

  at ...\ReadFailDemoTest.php:12
      8▕ {
      9▕     public function test_intentional_failure_demo(): void
     10▕     {
     11▕         $response = $this->get('/login');
  ➜  12▕         $response->assertStatus(403);
     13▕     }
     14▕ }
```
* **Analisis:** Laravel langsung menampilkan ringkasan selisih nilai (`Expected 403, received 200`) dan menunjuk **baris persis berkas kode kita** dengan tanda panah `➜ 12▕ $response->assertStatus(403);`. Baris pertama yang perlu dibaca adalah file di folder `tests/` atau `app/`, bukan stack trace panjang milik `vendor/`.

### 3. Penelusuran Exception pada `storage/logs/laravel.log`
Dari pemeriksaan berkas log aplikasi, ditemukan beberapa catatan exception lama selama masa pengembangan:

1. **Error saat penyimpanan mata kuliah (2026-09-23):**
   ```text
   #12 vendor/laravel/framework/.../Model.php(2544): Model->forwardCallTo(..., 'create', Array)
   #13 vendor/laravel/framework/.../Model.php(2560): Model->__call('create', Array)
   #14 app/Http/Controllers/CourseController.php(49): Model::__callStatic('create', Array)
   #15 vendor/laravel/framework/.../ControllerDispatcher.php(46): CourseController->store(...)
   ```
   * **Baris pertama yang menunjuk `app/`:** **`app/Http/Controllers/CourseController.php` baris 49** (urutan frame `#14`, setelah 14 frame internal `vendor/`).
2. **Error pelanggaran lazy loading N+1 (2026-11-05 12:00:26):**
   ```text
   local.ERROR: Attempted to lazy load [lecturer] on model [App\Models\Course] but lazy loading is disabled.
   (View: D:\Semester 5\Pemweb\Week1\kampuslms-kelompok-02\resources\views\courses\index.blade.php)
   ```
   * **Pelajaran:** `Model::preventLazyLoading()` langsung mencatat nama relasi yang terlupa (`lecturer`), model asal (`Course`), dan berkas view pemanggilnya (`resources/views/courses/index.blade.php`).

### 4. Log Query Halaman Rekap Nilai via `DB::listen()`
Dengan mengaktifkan `DB::listen()` sementara saat membuka halaman rekap nilai kelas `/courses/1/grades` (`SI201`) sebagai Dosen pengampu (status HTTP 200), tercatat tepat **11 query** berikut:

1. `delete from sessions where id = ?` (0.76 ms)
2. `select * from sessions where id = ? limit 1` (0.58 ms)
3. `select * from courses where id = ? limit 1` (0.39 ms) - Route Model Binding `Course`
4. `select * from assignments where assignments.course_id = ? ... order by due_at asc` (0.60 ms)
5. `select users.*, course_user.* from users inner join course_user on users.id = course_user.user_id where course_user.course_id = ? and users.deleted_at is null order by name asc` (1.65 ms)
6. `select * from submissions where assignment_id in (?, ?, ?, ?, ?, ?)` (0.82 ms)
7. `select * from grades where grades.submission_id in (1, 2, ..., 255)` (0.72 ms) - Eager loading `with('grade')`
8. `select * from users where users.id = ? and users.deleted_at is null limit 1` (0.61 ms)
9. `select * from notifications where notifications.notifiable_type = ? and notifications.notifiable_id = ? ... and read_at is null order by created_at desc` (0.59 ms)
10. `select * from sessions where id = ? limit 1` (0.37 ms)
11. `insert into sessions (...) values (...)` (7.47 ms)

* **Kesimpulan:** Halaman rekap nilai di `GradeController::courseGrades()` bebas dari query N+1 karena daftar `submissions` dan relasi `grade` dimuat serentak menggunakan klausa `WHERE ... IN (...)`.

---

## Bagian BREAK: Tujuh Percobaan Merusak Test (Dijadwalkan Setelah BUILD)

*(Catatan: Sesuai rencana kerja, tahap BREAK akan dieksekusi di branch percobaan terpisah setelah target BUILD Minggu 14 selesai diverifikasi).*

Daftar 7 percobaan yang akan diuji dan dicatat nanti:
1. Test yang hanya `assertStatus(200)` lalu seluruh isi controller dihapus.
2. Menghapus `Gate::authorize()` pada satu controller lalu menjalankan test.
3. Menghapus `RefreshDatabase` dan menjalankan suite test dua kali berurutan.
4. Menulis test yang bergantung pada data seeder.
5. Menghapus `Storage::fake()` saat menjalankan test unggah berkas.
6. Merusak method `down()` pada salah satu berkas migrasi.
7. Meninggalkan `dd()` di dalam controller saat `php artisan test` dijalankan.

---

## Bagian FIX: Repo Cacat `kampuslms-broken` Branch `w14`

* **Status:** Dikerjakan oleh anggota kelompok lain (sudah ditangani pada pembagian tugas tim).

---

## Bagian BUILD (Langkah 4.1 - Disempurnakan): Rancangan Feature Test Berdasarkan Prompt Pack A

Rancangan ini memetakan seluruh syarat **BUILD Poin 1-2 Modul 14**, **Spesifikasi Proyek Bagian 2.1, 2.2, 3, 4, 5, dan 9** (Autentikasi & akses tanpa login, CRUD mata kuliah, Enrollment, Unggah materi, Kumpul tugas, Penilaian & Rubrik, Rekap nilai, Notifikasi asinkron), serta seluruh **15 baris titik rawan pada `docs/keamanan.md`** ke dalam tiga kelompok: **Jalur Sukses**, **Jalur Ditolak (Otorisasi)**, dan **Jalur Validasi Gagal**, beserta **10 Test Prioritas Utama** dan pemetaan celah yang harus dituntaskan pada Langkah 4.2 & 4.3.

### A. Jalur Sukses

1. **`[PRIORITAS #1]` `test_enrolled_student_can_submit_file_assignment`**
   - **Kondisi awal:** Mahasiswa terdaftar (`course_user`) pada kelas aktif dengan tugas `published` dan `due_at` di masa depan; `Storage::fake('local')`.
   - **Aksi:** Mahasiswa mengirim `POST /assignments/{assignment}/submissions` dengan file `.pdf` valid (<= 5 MB).
   - **Hasil diharapkan:** Redirect ke `/assignments/{assignment}`; record `submissions` tersimpan di DB dengan `is_late = false`, `file_path` teracak di disk `local` (bukan `public`), dan file fisik eksis di `Storage::disk('local')`. *(Status: Sudah ada di `FileStorageTest`)*.
2. **`[PRIORITAS #2]` `test_course_lecturer_can_grade_submission_and_trigger_notification`**
   - **Kondisi awal:** Dosen pengampu kelas; mahasiswa telah mengumpulkan submission; `Notification::fake()` / `Event::fake()`.
   - **Aksi:** Dosen mengirim `POST /submissions/{submission}/grades` dengan `score` (0..max_score) dan `feedback` (jalur standar & jalur otomatis rubrik bertingkat).
   - **Hasil diharapkan:** Record `grades` tersimpan di DB, event `SubmissionGraded` terpicu, dan `SubmissionGradedNotification` terkirim ke mahasiswa pemilik (tanpa membocorkan `score`/`feedback` di payload notifikasi). *(Status: Sudah ada di `AuthzTest`, `NotificationQueueTest`, & `RubricTieredGradingTest`)*.
3. **`test_user_can_login_with_valid_credentials_and_logout`**
   - **Kondisi awal:** User aktif (`admin`, `dosen`, atau `mahasiswa`) terdaftar di database.
   - **Aksi:** Kirim `POST /login` dengan kredensial benar, lalu `POST /logout`.
   - **Hasil diharapkan:** Login terautentikasi (`assertAuthenticatedAs`) dan redirect ke `/dashboard`; logout mengembalikan ke `/login` dan status menjadi guest (`assertGuest`). *(Status: Sudah ada di `AuthzTest`)*.
4. **`test_admin_and_course_lecturer_can_enroll_and_unenroll_student`**
   - **Kondisi awal:** Admin, Dosen pengampu, Mahasiswa target, dan kelas aktif tersedia.
   - **Aksi:** Admin dan Dosen pengampu mengirim `POST /courses/{course}/enroll` lalu `DELETE /courses/{course}/unenroll/{student}`.
   - **Hasil diharapkan:** Relasi `course_user` bertambah saat enroll (termasuk kolom `enrolled_at`) dan terhapus saat unenroll. *(Status: Sudah ada di `EnrollmentAndGradingTest`)*.
5. **`test_course_lecturer_can_upload_material_and_enrolled_student_can_download`**
   - **Kondisi awal:** Dosen pengampu dan Mahasiswa terdaftar di kelas; `Storage::fake('local')`.
   - **Aksi:** Dosen mengirim `POST /courses/{course}/materials` tipe `file` (`.pdf`/`.pptx`/`.docx` <= 20 MB), lalu Mahasiswa mengakses `GET /materials/{material}/download`.
   - **Hasil diharapkan:** Record `materials` tersimpan, file berada di disk privat `local`, dan unduhan oleh mahasiswa terdaftar mengembalikan HTTP 200 dengan stream file yang benar. *(Status: Sudah ada di `FileStorageTest`)*.
6. **`test_creating_published_assignment_sends_notification_end_to_end_to_enrolled_students`**
   - **Kondisi awal:** `Notification::fake()`; kelas memiliki 2 mahasiswa terdaftar dan 1 mahasiswa tidak terdaftar (`QUEUE_CONNECTION=sync` di `phpunit.xml`).
   - **Aksi:** Dosen pengampu mengirim `POST /courses/{course}/assignments` dengan `status = published`.
   - **Hasil diharapkan:** Tugas tersimpan di DB, `NewAssignmentNotification` terkirim ke 2 mahasiswa terdaftar, dan **tidak terkirim** ke mahasiswa luar kelas. *(Status: Perlu dilengkapi pengujian end-to-end lewat `POST` di Langkah 4.2; saat ini di `NotificationQueueTest` masih menguji `Event::fake()` dan memanggil `$listener->handle()` terpisah)*.
7. **`test_course_index_and_assignment_submissions_index_are_scoped_by_role`**
   - **Kondisi awal:** 2 kelas dengan dosen & mahasiswa berbeda; masing-masing memiliki submission milik beberapa mahasiswa.
   - **Aksi:** Admin, Dosen 1, dan Mahasiswa 1 membuka `GET /courses` (`docs/keamanan.md` No. 1) dan `GET /assignments/{assignment}/submissions` (`docs/keamanan.md` No. 9).
   - **Hasil diharapkan:** Admin melihat semua kelas; Dosen 1 hanya melihat kelasnya; Mahasiswa 1 hanya melihat kelas yang diikuti dan di daftar submission hanya melihat submission miliknya sendiri (bukan milik rekan sekelas). *(Status: `/courses` sudah ada di `AuthzTest`, scoping `/assignments/{id}/submissions` untuk mahasiswa perlu ditambahkan!)*.
8. **`test_course_lecturer_and_student_can_view_appropriate_grade_recap`**
   - **Kondisi awal:** Kelas Dosen A memiliki tugas dan submission yang sudah dinilai untuk Mahasiswa 1.
   - **Aksi:** Dosen A membuka `GET /courses/{course}/grades` dan Mahasiswa 1 membuka `GET /my-grades`.
   - **Hasil diharapkan:** Dosen A melihat matriks nilai mahasiswa kelasnya; Mahasiswa 1 melihat transkrip nilai pribadinya sendiri (nilai & feedback tampil). *(Status: Sudah ada di `NotificationQueueTest`)*.

### B. Jalur Ditolak (Otorisasi / Anti-IDOR 403 / Unauthenticated)

9. **`[PRIORITAS #3]` `test_unauthenticated_guest_cannot_access_protected_routes`**
   - **Kondisi awal:** Pengguna belum login (`guest`), terdapat data kelas, materi, submission, dan notifikasi.
   - **Aksi:** Guest mengakses `GET /dashboard`, `GET /courses`, `GET /users`, `GET /materials/{material}/download`, `GET /submissions/{submission}/download`, `GET /courses/{course}/grades`, dan `GET /api/v1/me`.
   - **Hasil diharapkan:** Seluruh rute web di-redirect ke `/login` (HTTP 302) dan endpoint API mengembalikan **HTTP 401 Unauthorized** (tidak ada data/file yang bocor ke publik). *(Status: Baru `/` dan `/api/v1/me` yang teruji; rute web terproteksi lain perlu dilengkapi sesuai BUILD Poin 1 Modul 14!)*.
10. **`[PRIORITAS #4]` `test_student_cannot_download_or_view_other_student_submission`**
    - **Kondisi awal:** Mahasiswa A memiliki submission dengan file tersimpan di `Storage::fake('local')`; Mahasiswa B berada di kelas yang sama atau kelas lain.
    - **Aksi:** Mahasiswa B mengakses `GET /submissions/{submissionA}` dan `GET /submissions/{submissionA}/download`.
    - **Hasil diharapkan:** Kedua request mengembalikan **HTTP 403 Forbidden** (`docs/keamanan.md` No. 11 & 12). *(Status: Sudah ada di `AuthzTest` & `FileStorageTest`)*.
11. **`[PRIORITAS #5]` `test_unregistered_student_cannot_view_course_download_material_or_submit_assignment`**
    - **Kondisi awal:** Mahasiswa B tidak terdaftar di kelas Dosen A yang memiliki materi file dan tugas `published`; `Storage::fake('local')`.
    - **Aksi:** Mahasiswa B mengakses `GET /courses/{course}`, `GET /materials/{material}/download`, dan mengirim `POST /assignments/{assignment}/submissions`.
    - **Hasil diharapkan:** Ketiganya mengembalikan **HTTP 403 Forbidden** dan tidak ada baris baru di `submissions` (`docs/keamanan.md` No. 2, 7, 10). *(Status: `GET /courses/{course}` sudah ada; unduh materi & submit tugas oleh mahasiswa tidak terdaftar perlu ditambahkan!)*.
12. **`[PRIORITAS #6]` `test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources`**
    - **Kondisi awal:** Dosen A memiliki kelas, materi, tugas, rekap pengumpulan, dan submission mahasiswa; Dosen B bukan pengampu kelas tersebut.
    - **Aksi:** Dosen B mengakses/mengirim `GET /courses/{course}`, `GET /courses/{course}/edit`, `PUT /courses/{course}`, `POST /courses/{course}/materials`, `GET /materials/{material}/download`, `POST /courses/{course}/assignments`, `POST /courses/{course}/enroll`, `GET /courses/{course}/submissions`, `GET /courses/{course}/grades`, dan `POST /submissions/{submission}/grades`.
    - **Hasil diharapkan:** Seluruh request mengembalikan **HTTP 403 Forbidden**; data kelas/materi/tugas/enrollment/nilai di DB tetap utuh (`docs/keamanan.md` No. 2, 3, 4, 6, 7, 8, 13 & Spesifikasi Bagian 3). *(Status: `edit`, `enroll`, `grades` store, `create` GET sudah ada; `PUT /courses`, `POST /materials`, `POST /assignments`, `GET /materials/{id}/download`, `GET /courses/{id}/submissions`, `GET /courses/{id}/grades` oleh `dosen2` perlu ditambahkan!)*.
13. **`[PRIORITAS #7]` `test_non_admin_cannot_create_delete_course_or_escalate_role`**
    - **Kondisi awal:** Akun Dosen dan Mahasiswa biasa; tersedia satu mata kuliah.
    - **Aksi:**
      1. Dosen dan Mahasiswa mengirim `POST /courses` dan `DELETE /courses/{course}`.
      2. Dosen dan Mahasiswa mengakses `GET /users`, serta Mahasiswa mengirim `PUT /users/{mhs}` dengan payload `'role' => 'admin'`.
    - **Hasil diharapkan:** `POST /courses`, `DELETE /courses/{course}`, dan `GET /users` mengembalikan **HTTP 403 Forbidden** (mata kuliah tidak terhapus); pada `PUT /users/{mhs}`, kolom `role` di DB tetap `'mahasiswa'` (`docs/keamanan.md` No. 5, 14, 15). *(Status: `GET /users`, `GET /courses/create`, `PUT /users` sudah ada; `POST /courses` & `DELETE /courses` ditolak 403 perlu dilengkapi!)*.
14. **`[PRIORITAS #8]` `test_admin_and_student_cannot_grade_submission_nor_non_student_submit`**
    - **Kondisi awal:** Tugas `published` dan submission mahasiswa tersedia; tersedia akun Admin, Dosen pengampu, dan Mahasiswa.
    - **Aksi:** Admin dan Mahasiswa masing-masing mengirim `POST /submissions/{submission}/grades` dengan nilai 100; Mahasiswa mengakses `GET /courses/{course}/grades`; serta Admin dan Dosen mencoba mengirim `POST /assignments/{assignment}/submissions`.
    - **Hasil diharapkan:** Seluruhnya mengembalikan **HTTP 403 Forbidden**, tabel `grades` dan `submissions` tidak bertambah (`docs/keamanan.md` No. 10 & 13). *(Status: Admin `grade` & Mahasiswa `courses.grades` sudah ada; Mahasiswa `POST /submissions/{id}/grades` dan Admin/Dosen `POST submissions` 403 perlu ditambahkan!)*.
15. **`test_student_cannot_view_or_submit_draft_assignment_nor_mark_other_notification`**
    - **Kondisi awal:** Tugas berstatus `draft` di kelas yang diikuti Mahasiswa A; serta notifikasi milik Mahasiswa B.
    - **Aksi:** Mahasiswa A membuka `GET /assignments/{draft}`, mengirim `POST /assignments/{draft}/submissions`, dan `POST /notifications/{notifB}/read`.
    - **Hasil diharapkan:** Akses tugas draft ditolak **HTTP 403 Forbidden**, dan mark-as-read notifikasi orang lain ditolak **HTTP 404** (scoped user) dengan `read_at` tetap `null`. *(Status: Sudah ada di `AuthzTest` & `NotificationQueueTest`)*.

### C. Jalur Validasi Gagal (Aturan Bisnis & Input Server-Side)

16. **`[PRIORITAS #9]` `test_submission_rejected_past_deadline_when_allow_late_is_false`**
    - **Kondisi awal:** Mahasiswa terdaftar; tugas `published` dengan `allow_late = false` dan `due_at = now()->subHours(2)`.
    - **Aksi:** Mahasiswa mengirim `POST /assignments/{assignment}/submissions` dengan file PDF valid.
    - **Hasil diharapkan:** Redirect kembali dengan validation error pada key `file`, dan tabel `submissions` tetap kosong (0 record). *(Status: Sudah ada di `FileStorageTest`)*.
17. **`[PRIORITAS #10]` `test_submission_material_and_grading_reject_invalid_inputs`**
    - **Kondisi awal:** `Storage::fake('local')`; mahasiswa terdaftar & dosen pengampu; tugas dengan `max_score = 100`.
    - **Aksi:**
      1. Mahasiswa mengunggah `.php` atau `.pdf` > 5 MB (`5300 KB`) ke submission.
      2. Dosen mengunggah `.php`/`.exe` atau `type=link` tanpa `external_url` valid pada materi.
      3. Dosen mengirim `score = -5` atau `score = 150` pada penilaian standar, atau membuat rubrik dengan total bobot != 100%.
    - **Hasil diharapkan:** Validation error muncul pada field terkait (`file`, `external_url`, `score`, `criteria`), 0 file tersimpan di `Storage::disk('local')`, dan 0 record invalid di DB. *(Status: Validasi submission & bobot rubrik sudah ada; validasi gagal unggah materi & skor `< 0` / `> max_score` perlu ditambahkan!)*.
18. **`test_duplicate_course_enrollment_is_idempotent_and_non_student_rejected`**
    - **Kondisi awal:** Mahasiswa A sudah terdaftar pada kelas Dosen A; tersedia juga akun Dosen B.
    - **Aksi:** Dosen A mengirim `POST /courses/{course}/enroll` kembali dengan `student_id` Mahasiswa A, lalu mencoba `POST` dengan `student_id` milik akun Dosen B.
    - **Hasil diharapkan:** Enroll ulang Mahasiswa A tetap menjaga jumlah baris `course_user` = 1 tanpa error SQL duplicate entry; ID non-mahasiswa ditolak (404 `firstOrFail` / tidak masuk ke `course_user`). *(Status: Belum ada test-nya)*.
19. **`test_login_fails_with_invalid_credentials`**
    - **Kondisi awal:** Akun user valid tersimpan di database.
    - **Aksi:** Mengirim `POST /login` dengan password salah.
    - **Hasil diharapkan:** Session memiliki error pada `email` dan user tetap berstatus guest (`assertGuest`). *(Status: Sudah ada di `AuthzTest`)*.

---

### Pemetaan Lengkap 15 Baris `docs/keamanan.md` (Syarat Wajib BUILD Poin 2)

| No | Endpoint (`docs/keamanan.md`) | Rancangan Skenario Terkait (4.1) | Status di Repo Saat Ini |
|:--:|:---|:---|:---|
| 1 | `GET /courses` | Skenario #7 (Course index scoped per role) | Sudah ada (`AuthzTest`) |
| 2 | `GET /courses/{course}` | Skenario #11 & #12 (Mhs luar & Dosen lain 403) | Mhs luar ada; `dosen2` perlu ditambah |
| 3 | `GET /courses/{course}/edit` | Skenario #12 (Dosen lain 403) | Sudah ada (`AuthzTest`) |
| 4 | `PUT/PATCH /courses/{course}` | Skenario #12 (Dosen lain / Mhs 403) | Baru cek `lecturer_id` tetap; `dosen2` & `mhs` 403 perlu ditambah |
| 5 | `DELETE /courses/{course}` | Skenario #13 (Non-admin 403) | Perlu ditambah |
| 6 | `GET/POST /courses/{course}/materials/create` | Skenario #12 (Dosen lain & Mhs 403) | `GET /create` ada; `POST` 403 perlu ditambah |
| 7 | `GET /materials/{material}/download` | Skenario #11 & #12 (Mhs luar & Dosen lain 403) | Perlu ditambah |
| 8 | `GET/POST /courses/{course}/assignments/create` | Skenario #12 (Dosen lain & Mhs 403) | `GET /create` ada; `POST` 403 perlu ditambah |
| 9 | `GET /assignments/{assignment}/submissions` | Skenario #7 (Mahasiswa hanya lihat miliknya) | Perlu ditambah |
| 10 | `POST /assignments/{assignment}/submissions` | Skenario #11, #14 & #15 (Mhs luar, dosen/admin, draft 403) | Draft ada; Mhs tidak terdaftar & dosen/admin 403 perlu ditambah |
| 11 | `GET /submissions/{submission}` | Skenario #10 (Mhs B & Dosen lain 403) | Sudah ada (`AuthzTest`) |
| 12 | `GET /submissions/{submission}/download` | Skenario #10 (Mhs B & Dosen lain 403) | Sudah ada (`FileStorageTest`) |
| 13 | `POST /submissions/{submission}/grades` | Skenario #12 & #14 (Dosen lain, Admin, Mhs 403) | Dosen lain & Admin ada; Mhs 403 perlu ditambah |
| 14 | `GET /users` | Skenario #13 (Non-admin 403, Admin 200) | Sudah ada (`AuthzTest`, perlu perkuat assertion isi!) |
| 15 | `PUT/PATCH /users/{user}` | Skenario #13 (Cegah `role=admin` escalation) | Sudah ada (`AuthzTest`) |

---

### Ringkasan Celah untuk Dikerjakan di Langkah 4.2 (Prompt Pack B) & 4.3 (Prompt Pack C)
1. **Tambahkan test otorisasi & akses tanpa login yang masih kosong:**
   - Guest akses rute terproteksi (`/dashboard`, `/courses`, `/users`, unduhan, rekap nilai) -> redirect `/login`.
   - `mhs2` (tidak terdaftar) unduh materi & submit tugas -> `403`.
   - `dosen2` (bukan pengampu) `GET /courses/{course}`, `PUT /courses/{course}`, `POST /materials`, `GET /materials/{material}/download`, `POST /assignments`, `GET /courses/{course}/submissions`, `GET /courses/{course}/grades` -> `403`.
   - Non-admin `POST /courses` & `DELETE /courses/{course}` -> `403`, serta Mahasiswa `POST /submissions/{submission}/grades` -> `403`.
   - Scoping `GET /assignments/{assignment}/submissions` agar mahasiswa hanya melihat miliknya sendiri.
2. **Tambahkan test end-to-end notifikasi & validasi gagal yang kurang:**
   - `POST /courses/{course}/assignments` (`status=published`) dengan `Notification::fake()` mengirim notifikasi ke mahasiswa terdaftar saja.
   - Validasi gagal unggah materi (ekstensi terlarang, link tanpa `external_url`), validasi gagal nilai `< 0` / `> max_score`, dan uji enroll ganda idempoten / non-mahasiswa ditolak.
3. **Audit & perkuat test menipu (Langkah 4.3 / Prompt Pack C):**
   - Perkuat test di `AuthzTest` yang baru memeriksa `assertStatus(200)` (seperti `test_admin_can_access_user_index`, `test_guest_can_access_forgot_password_page`, `test_admin_can_access_register_page`) agar memeriksa isi halaman/konten nyata (`assertSee`).
   - Uji coba sementara (lalu kembalikan) dengan mematikan 1 fitur/`Gate::authorize` untuk membuktikan test suite benar-benar berubah menjadi merah.

---

## Bagian BUILD (Langkah 4.2 & 4.3): Implementasi Test & Audit Kualitas (Prompt Pack B & C)

### 1. Implementasi Langkah 4.2 (Prompt Pack B)
Dibuat berkas pengujian baru `tests/Feature/PrioritySecurityAndFlowTest.php` yang berisi **10 test prioritas utama** beserta **3 test pelengkap celah keamanan** (total 13 test baru) dengan standar `RefreshDatabase`, factory mandiri tanpa ketergantungan seeder, `Storage::fake()`, `Notification::fake()`, serta `assertForbidden()` (`403`).

### 2. Hasil Audit Kualitas Test Langkah 4.3 (Prompt Pack C)
Audit dilakukan terhadap seluruh 8 berkas test (`ApiTest`, `AuthzTest`, `EnrollmentAndGradingTest`, `ExampleTest`, `FileStorageTest`, `NotificationQueueTest`, `PrioritySecurityAndFlowTest`, `RubricTieredGradingTest`, dan `Unit\ExampleTest`):

1. **Test yang tidak akan merah / hanya memeriksa `assertStatus(200)` tanpa isi/efek DB:**
   - `AuthzTest::test_admin_can_create_course` sebelumnya hanya mengecek `GET /courses/create -> 200` tanpa menguji `POST`, `PUT`, `DELETE` ke DB -> **Diperbaiki:** kini mengecek teks `'Tambah Mata Kuliah Baru'` dan menguji alur penuh Create -> Update -> Delete hingga verifikasi `assertDatabaseHas` & `assertDatabaseMissing`.
   - `AuthzTest::test_dosen_can_edit_own_course`, `test_dosen_can_create_material_in_own_course`, `test_dosen_can_create_assignment_in_own_course`, `test_dosen_and_admin_can_view_draft_assignment`, `test_student_can_view_own_submission`, `test_course_lecturer_can_view_submission`, `test_admin_can_access_user_index`, `test_guest_can_access_forgot_password_page`, dan `test_admin_can_access_register_page` sebelumnya hanya `assertStatus(200)` -> **Diperbaiki:** seluruhnya ditambahkan assertion konten spesifik (`assertSee`).
   - `ApiTest::test_grading_upsert_returns_201_then_200` sebelumnya membungkus assertion di dalam `if ($submission)` (berisiko pass 0 assertion jika `$submission` null) dan tidak mengecek DB -> **Diperbaiki:** diganti `$this->assertNotNull($submission)` dan ditambahkan `assertDatabaseHas('grades', ...)`.
2. **Ketergantungan urutan eksekusi & data:**
   - Seluruh berkas feature test fitur web menggunakan `RefreshDatabase` + factory mandiri sehingga bebas dari ketergantungan urutan eksekusi. `ApiTest` menggunakan `RefreshDatabase` + `$seed = true` untuk skenario integrasi seeder kontrak API.
   - `tests/Unit/ExampleTest.php` adalah boilerplate bawaan Laravel (`assertTrue(true)`), tidak dipakai sebagai bukti fungsionalitas aplikasi.
3. **Jalur gagal penting yang ditambahkan:**
   - Menambahkan `AuthzTest::test_student_cannot_view_edit_or_delete_other_user` untuk memastikan mahasiswa tidak dapat melihat, mengubah, atau menghapus akun mahasiswa lain (`403 Forbidden`).

### 3. Hasil Eksekusi Akhir Setelah Audit
```text
Tests:    94 passed (370 assertions)
Duration: 13.12s
```

*(Pembaruan setelah Langkah 4.4-4.6: Bug signature `SendGradeNotification::failed()` sudah diperbaiki, kolom `Test` pada seluruh 15 baris `docs/keamanan.md` sudah dilengkapi, dan `docs/debugging.md` berisi 3 bug nyata sudah dibuat. Total test suite akhir: **95 passed, 371 assertions**).*

---

## Bagian CHECKPOINT: Gerbang Wawancara Materi 14 (14.4)

Panduan jawaban singkat, mudah diingat, dan langkah peragaan langsung untuk wawancara Checkpoint 14 bersama dosen:

---

### 1. Peragakan: hapus satu fitur, tunjukkan test yang menjadi merah karenanya.
* **Fitur yang Dimatikan Sementara:** Pengecekan otorisasi unduhan berkas tugas di `app/Http/Controllers/SubmissionController.php` baris `179` (`Gate::authorize('download', $submission);`).
* **Test Penjaganya:** `test_student_cannot_download_or_view_other_student_submission` di `tests/Feature/PrioritySecurityAndFlowTest.php`.
* **Langkah Peragaan (Step-by-Step):**
  1. **Jalankan dulu dalam kondisi normal:** Di terminal proyek, ketik:
     ```bash
     php artisan test --filter=test_student_cannot_download_or_view_other_student_submission
     ```
     *(Tunjukkan hasilnya **PASS hijau** karena mahasiswa lain ditolak `403`).*
  2. **Matikan penjaga otorisasi sementara:** Buka `app/Http/Controllers/SubmissionController.php` di VS Code, cari method `download()` baris `179`, lalu beri komentar `//`:
     ```php
     // Gate::authorize('download', $submission);
     ```
     Tekan `Ctrl + S` untuk menyimpan.
  3. **Jalankan test yang sama lagi di terminal:**
     ```bash
     php artisan test --filter=test_student_cannot_download_or_view_other_student_submission
     ```
     *(Tunjukkan hasilnya langsung berubah jadi **FAILED merah** dengan pesan: `Expected response status code [403] but received 200.`, bukti mahasiswa lain berhasil menembus dan mengunduh berkas orang lain).*
  4. **Kembalikan kode semula:** Hapus tanda `//` di baris `179`, simpan, lalu jalankan perintah test tadi sekali lagi agar kembali **PASS hijau**.
* **Kalimat ke Dosen:**  
  *"Ini Pak/Bu, awalnya test unduhan submission hijau. Begitu saya matikan `Gate::authorize` di `SubmissionController` baris 179, test-nya langsung merah karena server malah ngasih status 200 ke mahasiswa lain. Pas saya nyalakan lagi, test-nya langsung hijau. Artinya test ini beneran ngejaga fiturnya, bukan cuma formalitas."*

---

### 2. Kenapa test yang hanya `assertStatus(200)` sering tidak bermakna?
* **Jawabannya:**  
  Status `200` cuma membuktikan halaman bisa dibuka tanpa error 500, **tidak mengecek apakah isi halaman dan efek ke databasenya benar**.
  Contohnya, seandainya isi controller dihapus semua lalu diganti tampilan kosong, atau halaman daftar tugas malah membocorkan tugas draf milik dosen ke mahasiswa, test yang cuma `assertStatus(200)` tetap bakal hijau. Karena itu test harus ikut mengecek isi halaman (`assertSee` / `assertDontSee`) atau perubahan database (`assertDatabaseHas`).

---

### 3. Kenapa test jalur ditolak lebih penting daripada test jalur sukses di aplikasi ini?
* **Jawabannya:**  
  Kalau jalur sukses yang rusak (misal tombol simpan error), pengguna pasti langsung tahu dan melapor karena tidak bisa bekerja.
  Sebaliknya, kalau **jalur ditolak (keamanan) yang bolong** - misalnya mahasiswa bisa melihat atau mengunduh berkas teman lewat URL - aplikasi kelihatan jalan normal tanpa error apa pun, jadi tidak ada yang lapor sementara data bocor diam-diam. Selain itu, kita tidak mungkin mencoba manual seluruh kombinasi 3 peran ke semua URL setiap kali mengubah kode, jadi feature test `403` yang otomatis mengawalnya.

---

### 4. Apa fungsi `RefreshDatabase`? Kenapa migrasi harus reversible agar ia bekerja?
* **Jawabannya:**  
  - **Fungsi `RefreshDatabase`:** Mengosongkan dan membangun ulang kondisi database bersih sebelum test dijalankan, supaya data sisa dari satu test tidak mengotori atau memengaruhi hasil test lainnya.
  - **Kenapa migrasi harus reversible (`down()`):** Karena untuk mereset database ke kondisi awal dengan bersih, proses test ikut bergantung pada rollback migrasi (menjalankan method `down()` lalu `up()`). Kalau ada satu saja migrasi yang `down()`-nya kosong atau gagal (misal terhalang foreign key), proses reset akan gagal dan seluruh test ikut macet.
* **Tempat Tunjuk:** Baris `use RefreshDatabase;` di bagian atas berkas test (misalnya `tests/Feature/PrioritySecurityAndFlowTest.php` baris 26).

---

### 5. Buka `docs/debugging.md`. Ceritakan satu bug: bagaimana Anda mempersempit penyebabnya?
* **Tempat Peragaan:** Buka file `docs/debugging.md` di VS Code, arahkan ke **Bug 1: Kegagalan Senyap Notifikasi Penilaian Tidak Terkirim ke Mahasiswa**.
* **Cara Menjelaskan ke Dosen (Tanpa Membaca Panjang):**  
  *"Gejalanya waktu dosen simpan nilai, muncul pesan sukses 'Nilai berhasil disimpan', tapi lonceng di akun mahasiswa tetap kosong dan tidak ada pesan error.*
  *Cara kami mempersempit penyebabnya:*
  1. *Pertama cek tabel `grades` di database: nilainya ternyata sudah masuk. Berarti proses simpan aman, masalahnya ada di jalur event atau antrean notifikasi.*
  2. *Kedua cek `.env` (`QUEUE_CONNECTION=database`) dan tabel `jobs` untuk memastikan apakah job nyangkut karena worker mati atau memang belum pernah masuk antrean.*
  3. *Ketiga cek `SubmissionController::grade()`: ketemu waktu awal dulu lupa memanggil `event(new SubmissionGraded($submission))` setelah simpan nilai (lalu di Minggu 14 kami cek lagi ke listener `SendGradeNotification::failed()`, ketemu juga parameter `$event` lupa dipasang sehingga `submission_id` di log selalu `null`).*
  *Setelah diperbaiki, sekarang dicegah regresi oleh test `test_grading_submission_dispatches_event_and_notifies_student_safely` dan `test_send_grade_notification_failed_handler_logs_submission_id`."*

---

### 6. Kenapa `dd()` tidak boleh masuk PR, sementara `Log::info()` boleh?
* **Jawabannya:**  
  - **`dd()` (Dump and Die) menghentikan eksekusi program dan mencetak isi variabel langsung ke layar browser pengguna.** Jika lupa dihapus dan terbawa ke PR atau server produksi, web langsung mati/rusak di depan pengguna dan membocorkan data internal sistem. Saat menjalankan `php artisan test`, `dd()` juga merusak jalannya test.
  - **`Log::info()` hanya menulis catatan secara diam-diam ke berkas privat server (`storage/logs/laravel.log`)**, tidak mengganggu tampilan pengguna maupun menghentikan program, sehingga aman dipakai di server produksi.
