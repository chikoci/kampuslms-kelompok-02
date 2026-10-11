# Panduan Code Walkthrough & Jawaban Bank Soal UTS (Minggu 7 - Prompt Pack D)

Dokumen ini disusun khusus sebagai panduan penguasaan individu untuk **Bagian 2: Code Walkthrough (8 menit)** dan **Bagian 3: Uji Keamanan Langsung (5 menit)** pada Ujian Tengah Semester (UTS) Pemrograman Web.

---

## Jawaban Lengkap 12 Bank Soal UTS

### Soal 1: Tunjukkan migrasi yang Anda tulis. Jelaskan setiap constraint di dalamnya.
* **Lokasi Berkas:**
  * `database/migrations/2026_09_14_000001_create_courses_table.php`
  * `database/migrations/2026_09_14_000002_create_course_user_table.php`
  * `database/migrations/2026_09_14_000005_create_submissions_table.php`
* **Penjelasan Constraint:**
  * `constrained()->cascadeOnDelete()`: Menjamin integritas referensial antar tabel relasi; jika parent data dihapus, data child otomatis dibersihkan agar tidak terjadi *orphan records*.
  * `$table->unique(['assignment_id', 'user_id'])`: Memastikan 1 mahasiswa hanya memiliki 1 submission aktif per tugas kuliah.
  * `unsignedBigInteger('file_size')`: Memastikan ukuran berkas selalu bilangan bulat positif.

---

### Soal 2: Mengapa `course_user` memiliki unique composite? Peragakan apa yang terjadi tanpa constraint tersebut.
* **Lokasi Berkas:** `database/migrations/2026_09_14_000002_create_course_user_table.php` baris:
  ```php
  $table->unique(['course_id', 'user_id']);
  ```
* **Alasan:**
  * Mencegah anomali data di mana seorang mahasiswa terdaftar lebih dari satu kali pada mata kuliah yang sama.
* **Peragaan Bila Dihapus:**
  * Mahasiswa akan muncul berulang kali di daftar kelas, penghitungan jumlah peserta (`students_count`) menjadi salah (duplikasi), dan saat sinkronisasi nilai/tugas terjadi tabrakan.

---

### Soal 3: Apa itu mass assignment? Tunjukkan bagian kode Anda yang mencegahnya.
* **Pengertian:**
  * *Mass assignment* adalah kerentanan di mana pengguna menyisipkan field tak terduga ke dalam array request (misal lewat form inspect element atau curl) yang kemudian langsung dimasukkan ke basis data via `Model::create($request->all())`.
* **Bagian Pencegah:**
  * Pada setiap Model (misal `app/Models/User.php` dan `app/Models/Course.php`) didefinisikan properti `$fillable` yang hanya memuat kolom aman.
  * Pada Controller, pemanggilan data selalu menggunakan `$request->validated()` dari Form Request, bukan `$request->all()`.

---

### Soal 4: Mengapa `role` tidak ada di `$fillable`? Di mana nilainya diisi?
* **Lokasi Berkas:** `app/Models/User.php`
* **Alasan:**
  * Jika `role` dimasukkan ke `$fillable`, penyerang bisa mengirimkan payload `role=admin` saat registrasi atau pembaruan profil (serangan *Privilege Escalation*).
* **Di Mana Nilainya Diisi:**
  * Nilai `role` diisi secara eksplisit di controller khusus (misal saat Admin mendaftarkan pengguna di `UserController@store` atau melalui seeder).

---

### Soal 5: Tunjukkan satu Policy yang Anda tulis dan jelaskan setiap barisnya.
* **Lokasi Berkas:** `app/Policies/SubmissionPolicy.php`
* **Kode:**
  ```php
  public function view(User $user, Submission $submission): bool
  {
      if ($user->role === 'admin') return true;
      if ($user->role === 'dosen') {
          return $submission->assignment->course->lecturer_id === $user->id;
      }
      if ($user->role === 'mahasiswa') {
          return $submission->user_id === $user->id;
      }
      return false;
  }
  ```
* **Penjelasan Baris per Baris:**
  * Baris 1: Menerima instance pengguna yang sedang login (`$user`) dan submission yang diakses (`$submission`).
  * Baris 3: Jika pengguna adalah Admin, selalu izinkan untuk keperluan audit.
  * Baris 4-6: Jika Dosen, periksa apakah dosen tersebut adalah pengampu mata kuliah induk dari tugas ini.
  * Baris 7-9: Jika Mahasiswa, pastikan `user_id` pada submission identik dengan ID mahasiswa yang sedang login (pertahanan IDOR).
  * Baris 10: Tolak pengguna lain selain 3 peran sah tersebut.

---

### Soal 6: Mengapa `@can` di Blade tidak cukup untuk otorisasi? Peragakan.
* **Alasan:**
  * `@can` hanya mengontrol *tampilan visual (UI rendering)* di sisi browser. Penyerang tetap bisa mengirim HTTP request (misalnya `PUT /courses/1` atau `GET /submissions/2`) langsung melalui cURL, Postman, atau inspect element.
* **Peragaan:**
  * Sembunyikan tombol "Hapus" dengan `@can`. Lalu buka terminal dan kirimkan `curl -X DELETE http://127.0.0.1:8000/courses/1`. Tanpa Policy di controller, data tetap terhapus! Oleh karena itu otorisasi server-side via `Gate::authorize()` wajib ada.

---

### Soal 7: Apa perbedaan HTTP 401 dan 403? Tunjukkan masing-masing satu contoh pada API Anda.
* **Perbedaan:**
  * **401 Unauthorized:** Pengguna belum terautentikasi (belum login / tidak membawa Bearer Token yang valid).
  * **403 Forbidden:** Pengguna sudah login, server mengenali identitasnya, tetapi peran/hak aksesnya tidak diizinkan untuk sumber daya tersebut.
* **Contoh pada API KampusLMS:**
  * **401:** Mengakses `GET /api/v1/me` tanpa header `Authorization: Bearer <token>` menghasilkan 401.
  * **403:** Mahasiswa login dengan token valid lalu memanggil `POST /api/v1/assignments` untuk membuat tugas kuliah menghasilkan 403.

---

### Soal 8: Mengapa `$request->validated()` lebih aman daripada `$request->all()`?
* **Alasan:**
  * `$request->all()` mengambil seluruh data mentah yang dikirim oleh klien, termasuk field jahat yang tidak didefinisikan.
  * `$request->validated()` hanya mengambil data yang telah didefinisikan di aturan validasi (rules) Form Request dan telah lolos verifikasi, membuang payload yang tidak diizinkan.

---

### Soal 9: Mengapa filter pencarian disimpan di query string, bukan di session?
* **Alasan:**
  * URL menjadi *bookmarkable* dan *shareable* (dapat dibagikan ke dosen/teman lain dengan hasil filter yang sama).
  * Menjaga aplikasi tetap *stateless* dan tidak membingungkan pengguna saat membuka beberapa tab browser sekaligus dengan filter yang berbeda.

---

### Soal 10: Mengapa `session()->regenerate()` dipanggil setelah login?
* **Lokasi Berkas:** `app/Http/Controllers/AuthController.php`
* **Alasan:**
  * Mencegah serangan **Session Fixation**. Dengan membuat ID session baru saat pengguna berganti status dari tamu menjadi pengguna terautentikasi, penyerang yang memegang ID session lama tidak bisa membajak akun korban.

---

### Soal 11: Di mana middleware didaftarkan pada Laravel 12? Mengapa berbeda dari tutorial versi lama?
* **Lokasi Berkas:** `bootstrap/app.php`
* **Alasan Perbedaan:**
  * Pada Laravel 12, arsitektur framework disederhanakan (*lean skeleton*). Berkas `app/Http/Kernel.php` telah ditiadakan. Seluruh konfigurasi middleware sekarang dipusatkan di `bootstrap/app.php` menggunakan method `->withMiddleware()`.

---

### Soal 12: Tunjukkan bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan mengapa?
* **Rekomendasi Jawaban:**
  * "Kami menggunakan bantuan AI untuk merancang struktur dasar Policy dan pengujian IDOR. Namun kami mengubah implementasi `CoursePolicy` dan `DatabaseSeeder` agar sepenuhnya mematuhi rubrik dosen: kami menyesuaikan aturan Course 5 agar sengaja tidak meng-enroll mahasiswa demo demi membuktikan respons 403, serta memastikan submission pertama pada seeder terikat secara deterministik ke akun demo agar seluruh pengujian otomatis di CI/CD lulus 100% tanpa flakiness."

---

## Bagian 3: Simulasi Uji Keamanan Langsung (5 Menit)

Saat dosen penguji meminta uji tembus IDOR:
1. Penguji membuka `docs/keamanan.md`.
2. Penguji memilih skenario IDOR Submission: Mahasiswa A (ID 3) mencoba membuka submission milik Mahasiswa B (ID 2):
   ```bash
   curl -b cookies_mhs.txt http://127.0.0.1:8000/submissions/2
   ```
3. Server merespons: **HTTP 403 Forbidden** (Lolos Uji Pertahanan).
4. Anggota menjelaskan mekanisme: `SubmissionPolicy@view` memeriksa kepemilikan submission terhadap `auth()->id()`. Karena ID tidak cocok dan peran adalah mahasiswa, server menolak akses.
