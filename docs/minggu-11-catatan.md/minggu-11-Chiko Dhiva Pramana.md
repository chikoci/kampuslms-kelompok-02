# Catatan Praktikum Minggu 11: Optimisasi Performa

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Pengukuran Mandiri & Temuan N+1

Pada tahap READ ini, saya mengukur performa aplikasi KampusLMS secara langsung melalui **Laravel Debugbar** di browser menggunakan akun Dosen dan Mahasiswa dengan data seeder lengkap (30 mahasiswa, 5 mata kuliah, 15 tugas, dan ratusan pengumpulan tugas) untuk menemukan halaman yang paling lambat dan boros query.

Laporan tabel data lengkap dicatat pada file `docs/performa.md`.

### 1. Ringkasan Pengukuran Asli dari Browser (Debugbar)
Berdasarkan pengujian langsung di browser lokal:
* **Halaman terburuk:** **Detail Mata Kuliah (`/courses/1`)** dengan **18 query**, waktu muat **196.0 ms**, dan memori **4 MB**. Debugbar secara langsung menandai adanya **3 duplicate queries**.
* **Dashboard Mahasiswa (`/dashboard`):** Menjalankan **14 query** dengan total waktu muat **104.0 ms** dan memori 3 MB.
* **Transkrip Nilai Mahasiswa (`/my-grades`):** Menjalankan **12 query** dengan waktu **165.0 ms** dan **2 duplicate queries**.
* **Daftar Tugas (`/courses/1/assignments`):** Menjalankan **11 query** dengan total waktu terlama **224.0 ms**, waktu SQL 33.58 ms, dan **3 duplicate queries** ke tabel `courses`.
* **Rekap Nilai Kelas (`/courses/1/grades`):** Menjalankan **10 query** dengan waktu **183.0 ms** dan memori 4 MB.
* Halaman lainnya (Dashboard Dosen, Daftar Pengumpulan, Daftar Mata Kuliah) berkisar antara 7 hingga 10 query dengan memori 3-4 MB.

### 2. Mengapa Halaman Detail Mata Kuliah Menjadi yang Terburuk?
Setelah memeriksa tab query pada Debugbar, penyebab utamanya adalah **masalah N+1 dan duplikasi query di perulangan tampilan**:
* Di file view `resources/views/courses/show.blade.php`, setiap baris tugas memanggil perhitungan submissions secara terpisah:
  `{{ $assignment->submissions()->count() }}`
* Karena diletakkan di dalam perulangan foreach tugas, database dipaksa menjalankan query `SELECT COUNT(*)` berulang kali sebanyak tugas yang ada.
* Selain itu, terdapat query berulang ke tabel `courses` (`select * from courses where id = '1' limit 1`) yang dipicu saat memeriksa hak aksi materi dan tugas (lazy loading).

### 3. Temuan N+1 pada Halaman Mahasiswa (`/my-grades`)
Pada halaman transkrip nilai mahasiswa, Debugbar mendeteksi 2 query duplikat:
```sql
select * from users where users.id = 2 and users.deleted_at is null limit 1
```
Query ini terpanggil di file view `resources/views/grades/student.blade.php` baris 40 untuk mengambil nama dosen pengampu di setiap kotak mata kuliah, karena controller lupa melakukan eager loading relasi `lecturer`.

---

## Bagian BREAK: Eksperimen Mandiri (7 Percobaan)

Berikut 7 eksperimen BREAK yang dilakukan untuk menganalisis batas performa, mendeteksi kerentanan N+1, dan mengamati dampak kesalahan optimasi di Laravel:

---

### 1. Model::preventLazyLoading (Mendeteksi N+1 Tersembunyi Otomatis)
- **Prediksi:** Laravel akan langsung melempar error `LazyLoadingViolationException` ketika ada relasi model yang diakses di tampilan tanpa dimuat lebih dulu (`eager loading`) di controller.
- **Cara Uji:**
  1. Buka file `app/Providers/AppServiceProvider.php`.
  2. Di dalam method `boot()`, tambahkan baris pencegah lazy loading:
     ```php
     // app/Providers/AppServiceProvider.php

     use Illuminate\Database\Eloquent\Model;

     public function boot(): void
     {
         Model::preventLazyLoading(! app()->isProduction());
     }
     ```
  3. Buka browser dan akses halaman Transkrip Nilai Mahasiswa: `http://127.0.0.1:8000/my-grades` (login sebagai mahasiswa).
- **Sebenarnya:**
  * **Saat `preventLazyLoading` aktif:** Halaman langsung berhenti dan memunculkan layar merah error Laravel: `Attempted to lazy load [lecturer] on model [App\Models\Course] but lazy loading is disabled.` Fitur ini secara otomatis menangkap query relasi dosen yang lupa di-eager load di controller.
  * **Saat dinonaktifkan (default):** Halaman tetap terbuka normal tanpa pesan error, tetapi diam-diam mengeksekusi duplikasi query N+1 ke database yang membebani server.

---

### 2. CourseController.php (Matikan Eager Loading with('lecturer'))
- **Prediksi:** Jika eager loading dimatikan, setiap baris mata kuliah yang ditampilkan di tabel akan mengeksekusi satu query tambahan ke tabel `users` untuk mengambil nama dosen pengampu (masalah N+1).
- **Cara Uji:**
  1. Buka file `app/Http/Controllers/CourseController.php` pada method `index()`.
  2. Komentari baris eager loading `with('lecturer')`:
     ```php
     // SEBELUM:
     $courses = $query
         ->with('lecturer')
         ->when($request->filled('q'), fn ($q) => ...

     // DIUBAH MENJADI:
     $courses = $query
         // ->with('lecturer')
         ->when($request->filled('q'), fn ($q) => ...
     ```
  3. Buka halaman Daftar Mata Kuliah di browser: `http://127.0.0.1:8000/courses`.
  4. Buka tab **Queries** pada panel Laravel Debugbar di bagian bawah layar.
- **Sebenarnya:**
  * **Saat `with('lecturer')` dimatikan (kondisi N+1):** Badge query di Debugbar berubah menjadi **MERAH** dan melonjak dari **7 query menjadi 10 query**. Debugbar menampilkan peringatan *"10 statements were executed, 2 of which were duplicates, 8 unique"*, di mana query `select * from users where users.id = ?` terpanggil berulang kali dari file view `resources/views/courses/index.blade.php` baris 74.
  * **Saat `with('lecturer')` aktif (kondisi normal):** Total query hanya berjumlah **7 query** dengan **0 duplikasi**, karena seluruh data dosen pengampu sudah diambil sekaligus di awal menggunakan query `WHERE id IN (...)`.

---

### 3. DashboardController.php (Ganti withCount Menjadi count($course->students))
- **Prediksi:** `withCount` mengeksekusi agregasi `COUNT(*)` langsung di tingkat database yang hanya mengembalikan sebuah angka integer, sedangkan pemanggilan `count($course->students)` akan memuat seluruh baris objek model mahasiswa ke memori RAM PHP sehingga boros memori.
- **Cara Uji:**
  1. Buka file `app/Http/Controllers/DashboardController.php` pada method `dosenDashboard()`.
  2. Ubah query dan cara perhitungan total mahasiswa:
     ```php
     // SEBELUM:
     $myCourses = $user->taughtCourses()
         ->withCount(['materials', 'assignments', 'students'])
         ->latest()
         ->get();

     $metrics = [
         'total_students' => $myCourses->sum('students_count'),
         ...
     ];

     // DIUBAH MENJADI (Boros Memori):
     $myCourses = $user->taughtCourses()
         ->withCount(['materials', 'assignments'])
         ->with('students')
         ->latest()
         ->get();

     $metrics = [
         'total_students' => $myCourses->sum(fn ($c) => count($c->students)),
         ...
     ];
     ```
  3. Buka Dashboard Dosen di browser: `http://127.0.0.1:8000/dashboard` (login sebagai dosen).
  4. Amati badge **Models** dan **Memory** pada Laravel Debugbar.
- **Sebenarnya:**
  * **Saat memakai `count($course->students)`:** Jumlah model yang dimuat melonjak drastis (dari 21 model menjadi puluhan model User mahasiswa yang diinstansiasi ke memori RAM), dan alokasi memori PHP meningkat karena harus menampung seluruh objek mahasiswa tersebut.
  * **Saat memakai `withCount('students')`:** Hanya 21 model yang dimuat dan pemakaian memori tetap hemat (~3 MB), karena database MySQL hanya mengembalikan angka integer `students_count` tanpa memuat objek mahasiswa ke PHP.

---

### 4. Database Indexing (Analisis Query Tanpa Index Menggunakan EXPLAIN)
- **Prediksi:** Kolom yang tidak memiliki index saat digunakan pada klausa `WHERE` atau `ORDER BY` akan memaksa mesin database melakukan *Full Table Scan* (memindai seluruh baris tabel dari awal sampai akhir), ditandai dengan nilai `type: ALL` dan `key: NULL` pada laporan `EXPLAIN`.
- **Cara Uji:**
  1. Buka query console database (via Database Extension / phpMyAdmin / tinker).
  2. Bandingkan dua perintah query berikut:
     ```sql
     -- Query A: Kolom course_id yang memiliki index foreign key
     EXPLAIN SELECT * FROM assignments WHERE course_id = 1;

     -- Query B: Kolom due_at yang belum memiliki index
     EXPLAIN SELECT * FROM assignments WHERE due_at > '2026-11-01';
     ```
- **Sebenarnya:**
  * **Pada Query B (tanpa index `due_at`):** Kolom `type` bernilai **`ALL`** dan kolom `key` bernilai **`NULL`**. Database tidak memiliki indeks penunjuk sehingga terpaksa membaca seluruh baris dari tabel `assignments`.
  * **Pada Query A (dengan index `course_id`):** Kolom `type` bernilai **`ref`** dan kolom `key` berisi `assignments_course_id_foreign`. Database langsung melompat ke baris yang cocok secara instan tanpa memindai seluruh tabel.

---

### 5. Skalabilitas Data Seeder (Dampak Volume Data Terhadap Waktu Eksekusi)
- **Prediksi:** Pada kode yang masih memiliki masalah N+1 atau query tanpa index, peningkatan volume data (misalnya dari 20 baris menjadi ratusan baris) akan menyebabkan penurunan performa secara drastis (waktu respon melonjak dan query bertambah linier O(N)).
- **Cara Uji:**
  1. Periksa kode pembuatan seeder pada file `database/seeders/DatabaseSeeder.php`:
     ```php
     // database/seeders/DatabaseSeeder.php
     $otherStudents = User::factory(29)->student()->create();
     ```
  2. Menganalisis pertumbuhan query pada halaman Detail Mata Kuliah (`/courses/1`) yang memiliki 18 query saat hanya ada 3 tugas dan 23 mahasiswa.
  3. Mengukur proyeksi waktu eksekusi jika seeder dinaikkan menjadi 300 mahasiswa dan 1.000 submission.
- **Sebenarnya:**
  * **Sebelum optimasi:** Setiap penambahan 1 tugas baru di kelas akan menambah 1 query perhitungan submission di perulangan blade (`$assignment->submissions()->count()`). Dengan bertambahnya tugas dan mahasiswa, halaman akan menjalankan puluhan query berulang dan waktu pemuatan (load time) melonjak melewati 1-2 detik.
  * **Setelah optimasi (eager loading & index):** Jumlah query konstan (O(1)) tidak peduli seberapa banyak tugas atau mahasiswa yang terdaftar, sehingga waktu respon tetap stabil di bawah 100 ms.

---

### 6. Optimasi Produksi (Jalankan config:cache dan Amati Perilaku env())
- **Prediksi:** Ketika `config:cache` dijalankan untuk mempercepat booting aplikasi di lingkungan produksi, file `.env` tidak lagi dibaca oleh framework. Akibatnya, pemanggilan fungsi `env()` langsung di luar berkas konfigurasi `config/*.php` akan selalu mengembalikan nilai `null`.
- **Cara Uji:**
  1. Buka file `routes/web.php`, tambahkan route uji coba sementara di baris paling bawah:
     ```php
     // routes/web.php
     Route::get('/test-env', function () {
         return [
             'pakai_env' => env('APP_NAME'),
             'pakai_config' => config('app.name'),
         ];
     });
     ```
  2. Buka `http://127.0.0.1:8000/test-env` di browser sebelum caching: keduanya bernilai `"KampusLMS"`.
  3. Jalankan perintah caching konfigurasi produksi di terminal:
     ```bash
     php artisan config:cache
     ```
  4. Refresh kembali halaman `http://127.0.0.1:8000/test-env` di browser.
- **Sebenarnya:**
  * **Setelah `config:cache`:** Nilai `pakai_env` langsung berubah menjadi **`null`**, sedangkan `pakai_config` tetap konsisten berisi **`"KampusLMS"`**.
  * **Pelajaran penting:** Memanggil `env()` di controller atau view adalah sumber bug fatal di server produksi. Seluruh variabel lingkungan wajib dibungkus di dalam file `config/` dan diakses melalui helper `config()`.
  * *(Setelah uji coba selesai, bersihkan kembali dengan `php artisan config:clear` dan hapus rute uji coba dari `routes/web.php`)*.

---

### 7. Caching Data Dinamis (Pasang Cache pada Nilai Mahasiswa dan Amati Stale Data)
- **Prediksi:** Memasang cache pada data transaksional yang sering diperbarui (seperti nilai mahasiswa) tanpa mekanisme invalidasi otomatis (*cache invalidation*) akan menyebabkan pengguna melihat data lama yang sudah basi (*stale data*).
- **Cara Uji:**
  1. Buka file `app/Http/Controllers/GradeController.php` pada method `studentGrades()`.
  2. Bungkus query pengambilan nilai dengan cache selama 10 menit (600 detik):
     ```php
     // SEBELUM:
     $courses = $user->courses()->with([...])->get();

     // DIUBAH MENJADI (Memakai Cache Tanpa Invalidasi):
     $courses = \Illuminate\Support\Facades\Cache::remember('nilai_mhs_' . $user->id, 600, function () use ($user) {
         return $user->courses()->with([
             'assignments' => function ($q) {
                 $q->where('status', '!=', 'draft')->orderBy('due_at');
             },
             'assignments.submissions' => function ($q) use ($user) {
                 $q->where('user_id', $user->id)->with('grade');
             },
         ])->get();
     });
     ```
  3. Akses halaman Transkrip Nilai Mahasiswa di browser: `http://127.0.0.1:8000/my-grades`.
  4. Login sebagai Dosen di tab atau jendela lain, lalu ubah/beri nilai pada salah satu tugas mahasiswa tersebut.
  5. Refresh halaman Transkrip Mahasiswa.
- **Sebenarnya:**
  * **Data Mahasiswa Tidak Berubah (Basi):** Mahasiswa tetap melihat nilai lama sebelum diubah oleh dosen, karena server membaca data dari memori cache selama 10 menit tanpa query ulang ke database.
  * **Pelajaran penting:** Data nilai tidak boleh di-cache secara statis. Jika ingin di-cache, wajib dipasang model observer atau event listener untuk membersihkan cache (`Cache::forget`) setiap kali entitas nilai diperbarui.
  * *(Setelah uji coba selesai, kembalikan controller ke kode asli dan jalankan `php artisan cache:clear`)*.

---

## Bagian CHECKPOINT: Gerbang Interview Materi 11 (11.4)

Berikut panduan jawaban dan langkah peragaan langsung saat interview dosen atau asisten, lengkap dengan baris file dan kodenya:

### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?

- **Halaman Terburuk:** Detail Mata Kuliah (`/courses/1`).
  - Total Query: **18 query**.
  - Total Waktu Render: **196.0 ms**.
  - Waktu Database (SQL): **45.27 ms**.

- **Kenapa Halaman Ini Paling Buruk? (Bahasa Sederhana):**
  1. **Ada N+1 saat menghitung jumlah tugas yang dikumpulkan:**  
     Di file view `resources/views/courses/show.blade.php` pada baris 207, di dalam tabel perulangan tugas ada kode:
     ```blade
     {{ $assignment->submissions()->count() }} Pengumpulan
     ```
     Kode ini memaksa database menjalankan query hitung `SELECT COUNT(*)` berulang kali untuk setiap baris tugas di kelas.
  2. **Ada query berulang saat cek tombol aksi (Policy):**  
     Saat sistem mengecek apakah tombol edit atau hapus materi dan tugas boleh muncul, Laravel mengambil data mata kuliah induk berulang kali (`select * from courses where id = '1'`).

- **Kode yang Diperbaiki & Barisnya:**
  1. Di file `app/Http/Controllers/CourseController.php` pada method `show()` baris 80-92:
     ```php
     // Baris 80-92: kita eager load relasi induk dan hitung submission langsung di query
     $course->load([
         'lecturer',
         'students' => fn ($q) => $q->orderBy('name'),
         'materials.course', // <-- Mencegah query duplikat policy materi
         'assignments' => function ($query) use ($user) {
             if ($user?->role === 'mahasiswa') {
                 $query->where('status', 'published');
             }
             $query->withCount('submissions')->latest(); // <-- Dihitung sekaligus dalam 1 query
         },
         'assignments.course', // <-- Mencegah query duplikat policy tugas
         'assignments.submissions',
     ]);
     ```
  2. Di file `resources/views/courses/show.blade.php` baris 206-208:
     ```blade
     <!-- SEBELUM: {{ $assignment->submissions()->count() }} Pengumpulan -->
     <!-- SESUDAH (Baris 207): -->
     <a href="{{ route('assignments.submissions.index', $assignment) }}">
         {{ $assignment->submissions_count }} Pengumpulan &rarr;
     </a>
     ```
- **Hasilnya:** Query langsung turun dari 18 menjadi **14 query**, dan waktu database (SQL) turun 45% dari 45.27 ms menjadi **24.89 ms**.

---

### 2. Peragakan N+1 di aplikasi Anda: matikan `with()` pada satu halaman dan tunjukkan lonjakan query di Debugbar.

- **Lokasi File & Baris:**  
  File: `app/Http/Controllers/CourseController.php` pada method `index()` baris 31-42.

- **Langkah Peragaan ke Dosen (Step-by-Step):**
  1. Buka file `app/Http/Controllers/CourseController.php`, cari baris 31-33:
     ```php
     $courses = $query
         ->with('lecturer') // <-- Baris 32
     ```
  2. Beri tanda komentar `//` pada baris 32 untuk mematikan eager loading:
     ```php
     $courses = $query
         // ->with('lecturer')
     ```
  3. Buka browser pada alamat `http://127.0.0.1:8000/courses` (Daftar Mata Kuliah), lalu refresh.
  4. Tunjukkan panel Laravel Debugbar di bagian bawah layar:
     - Badge query berubah warna menjadi **MERAH**.
     - Jumlah query melonjak naik dari **7 query menjadi 10 query**.
     - Debugbar menampilkan pesan *"10 statements were executed, 2 of which were duplicates, 8 unique"*.
     - Buka tab **Queries**, tunjukkan ada query `select * from users where id = ?` yang dipanggil berulang kali untuk tiap kartu mata kuliah.
  5. Hapus kembali tanda `//` di baris 32, simpan file, dan refresh browser.
     - Tunjukkan query kembali normal menjadi **7 query bersih tanpa duplikasi (0 duplicate)**.

---

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?

- **Beda Keduanya (Sangat Sederhana):**
  - `withCount('students')`: Hitungan dilakukan langsung oleh MySQL di database lewat query `SELECT count(*)`. Hasil yang dikirim ke Laravel cuma satu angka bulat (misal: 23). Laravel tidak perlu membuat objek data mahasiswa.
  - `count($course->students)`: Laravel menarik semua baris data mahasiswa dari database, membuat puluhan objek model `User` lengkap ke memori RAM laptop/server, baru kemudian PHP menghitung berapa isi array-nya.

- **Kenapa yang Kedua Boros?**
  - **Boros RAM:** RAM server dipaksa menampung puluhan sampai ratusan objek mahasiswa lengkap beserta data nama, email, nim, dan password hash-nya, padahal kita cuma butuh tahu berapa jumlah orangnya.
  - **Boros CPU:** Server membuang waktu untuk menginstansiasi objek model satu per satu ke PHP.

- **Lokasi Kode di Aplikasi:**  
  Di file `app/Http/Controllers/DashboardController.php` method `dosenDashboard()`:
  - Baris 71-74:
    ```php
    $myCourses = $user->taughtCourses()
        ->withCount(['materials', 'assignments', 'students']) // <-- Baris 72: hitung di database
        ->latest()
        ->get();
    ```
  - Baris 88:
    ```php
    'total_students' => $myCourses->sum('students_count'), // Cuma menjumlahkan angka integernya
    ```

---

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.

- **Lokasi File Migrasi:**  
  File: `database/migrations/2026_11_04_000001_add_performance_composite_indexes.php`.

- **Isi Kode Index yang Ditambahkan:**
  - Baris 23-25 (Tabel `assignments`):
    ```php
    Schema::table('assignments', function (Blueprint $table) {
        $table->index(['course_id', 'status', 'due_at'], 'assignments_course_status_due_idx');
    });
    ```
  - Baris 31-33 (Tabel `courses`):
    ```php
    Schema::table('courses', function (Blueprint $table) {
        $table->index(['lecturer_id', 'status'], 'courses_lecturer_status_idx');
    });
    ```

- **Langkah Peragaan & Perintah EXPLAIN:**
  1. Buka database console (ekstensi Database Client di VS Code atau phpMyAdmin).
  2. Jalankan perintah query ini:
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE course_id = 1 AND status = 'published' ORDER BY due_at ASC;
     ```
  3. Tunjukkan tabel hasil EXPLAIN ke dosen:
     - Kolom **`key`**: tertulis `assignments_course_status_due_idx` (artinya index buatan kita benar-benar dipakai oleh MySQL, bukan `NULL`).
     - Kolom **`type`**: tertulis `ref` (artinya MySQL langsung lompat ke catatan index, tidak memindai seluruh isi tabel dari awal sampai akhir atau `ALL`).
     - Kolom **`Extra`**: bersih dari `Using filesort` karena urutan kolom index sudah sama persis dengan urutan klausa `ORDER BY due_at`.

---

### 5. Sebutkan satu data di aplikasi Anda yang tidak boleh di-cache. Kenapa?

- **Data yang Tidak Boleh Di-Cache:**  
  Daftar tugas yang baru dikumpulkan mahasiswa (`myRecentSubmissions`) dan antrean tugas yang belum dinilai dosen (`pendingSubmissions`) di halaman Dashboard.

- **Lokasi Kode di Aplikasi:**  
  Di file `app/Http/Controllers/DashboardController.php` baris 78-83:
  ```php
  // Baris 78-83: Data ini SENGAJA dibiarkan query biasa dan tidak dibungkus Cache::remember
  $pendingSubmissions = Submission::whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
      ->doesntHave('grade')
      ->with(['assignment.course', 'student'])
      ->latest()
      ->take(8)
      ->get();
  ```

- **Kenapa Tidak Boleh Di-Cache? (Contoh Kasus Riil):**
  - Data ini bersifat transaksi langsung (real-time).
  - Mahasiswa: Begitu mahasiswa mengunggah file tugas, mereka langsung me-refresh dashboard untuk memastikan statusnya berubah menjadi "Sudah Dikumpulkan". Jika data ini di-cache (misalnya 10 menit), statusnya akan tetap "Belum Mengumpulkan". Mahasiswa akan panik mengira file tugasnya gagal terkirim dan mencoba upload berulang kali.
  - Dosen: Begitu dosen selesai memberi nilai pada satu tugas, tugas itu harus langsung hilang dari antrean penilaian. Jika di-cache, tugas yang sudah dinilai akan tetap muncul di layar dan berisiko dinilai dua kali.

---

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?

- **Apa yang Terjadi?**  
  Ketika perintah `php artisan config:cache` dijalankan di terminal:
  1. Laravel menggabungkan seluruh file di folder `config/` menjadi satu file cepat di `bootstrap/cache/config.php`.
  2. Setelah file cache dibuat, Laravel **sengaja mematikan pembacaan file `.env`**.
  3. Akibatnya, semua pemanggilan fungsi `env('NAMA_KEY')` di luar folder `config/` (seperti di Controller atau View Blade) akan **selalu menghasilkan nilai `null`**.

- **Kenapa Ini Penting Minggu Depan (Materi 12 - Deployment / Server Produksi)?**
  - Di server produksi asli, menjalankan perintah `php artisan config:cache` adalah kewajiban standar agar loading website menjadi cepat.
  - Jika kita masih menulis `env()` di dalam Controller atau View, aplikasi akan langsung rusak dan memunculkan error saat di server produksi karena nilainya berubah menjadi `null` (misalnya link error, email gagal, nama aplikasi kosong).
  - Oleh karena itu, seluruh nilai `.env` wajib dibungkus di dalam folder `config/*.php` (misalnya `config/app.php`), lalu dipanggil di kode menggunakan fungsi `config('app.name')`.
