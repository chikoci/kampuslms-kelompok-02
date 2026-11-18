# Catatan Praktikum Minggu 11: Optimisasi Performa & Deployment

**Nama:** Bella Alviana  
**NIM:** 10241015  
**Kelas:** B (Sistem Informasi)  
**Kelompok:** Kelompok 02  
**Fokus Kontribusi:** Analisis Pengukuran Kinerja (READ), Tujuh Eksperimen Optimisasi (BREAK), UI/UX & Mobile Responsiveness, Penjelasan Dockerfile & Evaluasi Checkpoint Deployment.

---

## READ — Ukur Aplikasi Anda Sendiri (40 Menit)

Aturan pertama optimisasi adalah: **Ukur dulu, jangan menebak**. Seluruh data pengukuran di bawah ini diambil langsung menggunakan **Laravel Debugbar** di peramban web pada lingkungan lokal dengan seeder penuh (30 mahasiswa, 5 mata kuliah, 15 tugas, dan ratusan pengumpulan tugas) sebelum optimisasi diterapkan.

### 1. Tabel Pengukuran Kinerja Baseline (Sebelum Optimisasi)

Berikut adalah tabel hasil pengukuran riil untuk seluruh 7 halaman utama aplikasi KampusLMS:

| No | Halaman yang Diukur | Route / URL | Role Akun | Jumlah Query | Waktu Render (ms) | Waktu Database SQL (ms) | Memori Terpakai (MB) | Catatan Debugbar |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|---|
| 1 | **Detail Mata Kuliah** | `/courses/1` | Dosen | **18 query** | **196.0 ms** | 45.27 ms | 4.0 MB | **Terburuk 1** (Ada 3 duplicate queries) |
| 2 | **Dashboard Mahasiswa** | `/dashboard` | Mahasiswa | **14 query** | **104.0 ms** | 21.16 ms | 3.0 MB | **Terburuk 2** (Metrik agregasi relasi) |
| 3 | **Rekap Nilai (Transkrip Nilai Saya)** | `/my-grades` | Mahasiswa | **12 query** | **165.0 ms** | 43.71 ms | 3.0 MB | **Terburuk 3** (Ada 2 duplicate queries dosen) |
| 4 | **Daftar Tugas** | `/courses/1/assignments` | Dosen | **11 query** | **224.0 ms** | 33.58 ms | 3.0 MB | Waktu render terpanjang (3 duplikat) |
| 5 | **Rekap Nilai Kelas** | `/courses/1/grades` | Dosen | **10 query** | **183.0 ms** | 19.77 ms | 4.0 MB | Komputasi loop submission nilai |
| 6 | **Daftar Pengumpulan (Dosen)** | `/assignments/1/submissions` | Dosen | **10 query** | **100.0 ms** | 16.96 ms | 4.0 MB | Beban mapping data mahasiswa |
| 7 | **Dashboard Dosen** | `/dashboard` | Dosen | **10 query** | **92.6 ms** | 19.50 ms | 3.0 MB | Cukup cepat (Metrik kelas diampu) |
| 8 | **Daftar Mata Kuliah** | `/courses` | Dosen / Mahasiswa | **7 query** | **103.0 ms** | 15.29 ms | 3.0 MB | Jumlah query paling sedikit |

---

### 2. Urutan Halaman dari yang Terburuk ke Terbaik

Berdasarkan kombinasi **Jumlah Query**, **Waktu Eksekusi SQL**, dan **Waktu Render**, berikut urutan performa halaman dari yang paling buruk:

1. **Detail Mata Kuliah (`/courses/1`) — TERBURUK #1**
   - *Total: 18 Query, SQL: 45.27 ms, Render: 196.0 ms.*
   - Debugbar secara spesifik memberi peringatan: *"18 statements were executed, 3 of which were duplicates, 15 unique."*
2. **Dashboard Mahasiswa (`/dashboard`) — TERBURUK #2**
   - *Total: 14 Query, SQL: 21.16 ms, Render: 104.0 ms.*
   - Memuat banyak relasi terpisah untuk mata kuliah terdaftar, tugas mendatang, pengumuman, dan notifikasi unread.
3. **Rekap Nilai / Transkrip Mahasiswa (`/my-grades`) — TERBURUK #3**
   - *Total: 12 Query, SQL: 43.71 ms, Render: 165.0 ms.*
   - Memiliki 2 query duplikat relasi dosen dan pemanggilan sub-relasi evaluasi nilai tugas.
4. **Daftar Tugas (`/courses/1/assignments`)**
   - *Total: 11 Query, SQL: 33.58 ms, Render: 224.0 ms.*
5. **Rekap Nilai Kelas Dosen (`/courses/1/grades`)**
   - *Total: 10 Query, SQL: 19.77 ms, Render: 183.0 ms.*
6. **Daftar Pengumpulan Dosen (`/assignments/1/submissions`)**
   - *Total: 10 Query, SQL: 16.96 ms, Render: 100.0 ms.*
7. **Dashboard Dosen (`/dashboard`)**
   - *Total: 10 Query, SQL: 19.50 ms, Render: 92.6 ms.*
8. **Daftar Mata Kuliah (`/courses`) — TERBAIK**
   - *Total: 7 Query, SQL: 15.29 ms, Render: 103.0 ms.*

---

### 3. Diagnosis & Temuan Query N+1 pada Halaman Terburuk

Ketika membuka tab **Queries** pada Laravel Debugbar di halaman **Detail Mata Kuliah (`/courses/1`)**, ditemukan pola query yang berulang berkali-kali:

```sql
SELECT count(*) AS aggregate FROM `submissions` WHERE `submissions`.`assignment_id` = 1;
SELECT count(*) AS aggregate FROM `submissions` WHERE `submissions`.`assignment_id` = 2;
SELECT count(*) AS aggregate FROM `submissions` WHERE `submissions`.`assignment_id` = 3;
... (berulang sebanyak jumlah tugas)
```

* **Penyebab:**  
  Di dalam file view `resources/views/courses/show.blade.php`, di dalam perulangan `@foreach ($course->assignments as $assignment)` terdapat pemanggilan:
  ```blade
  {{ $assignment->submissions()->count() }} Pengumpulan
  ```
  Karena menggunakan tanda kurung fungsi `submissions()->count()`, kode ini mengeksekusi query database baru secara terpisah untuk setiap baris tugas di layar (N kueri baru).
* **Solusi Perbaikan:**  
  Di controller, relasi tugas dipanggil dengan `withCount('submissions')`. Di view Blade, pemanggilan diganti menjadi `$assignment->submissions_count` (0 query tambahan).

---

### 4. Analisis `EXPLAIN` pada Query Terlambat

Query terlambat yang dieksekusi adalah pencarian tugas berdasarkan `course_id` yang diurutkan berdasarkan `due_at`:

```sql
EXPLAIN SELECT * FROM `assignments` WHERE `course_id` = 1 ORDER BY `due_at` ASC;
```

**Hasil Keluaran `EXPLAIN` di Database:**

| id | select_type | table | partitions | type | possible_keys | key | key_len | ref | rows | filtered | Extra |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|---|
| 1 | SIMPLE | assignments | NULL | **ref** | assignments_course_id_foreign | assignments_course_id_foreign | 8 | const | **15** | 100.00 | Using filesort |

* **Penjelasan Kolom `type` dan `rows`:**
  * **`type = ref`:** Database memanfaatkan index foreign key `assignments_course_id_foreign` yang otomatis dibuat oleh InnoDB MySQL. Ini jauh lebih cepat daripada `type = ALL` (*Full Table Scan*).
  * **`rows = 15`:** Database secara presisi hanya memeriksa 15 baris data yang memiliki `course_id = 1` tanpa memindai ratusan baris tugas dari mata kuliah lain.
  * **`Extra = Using filesort`:** MySQL masih harus mengurutkan hasil di memori sementara berdasarkan kolom `due_at`. Oleh karena itu, penambahan index komposit `$table->index(['course_id', 'due_at'])` sangat disarankan untuk menghilangkan proses *filesort*.

---

## BREAK — Tujuh Percobaan (45 Menit)

Berikut adalah dokumentasi lengkap dari **Tujuh Percobaan Eksperimen Kinerja** sesuai ketentuan praktikum:

---

### Percobaan 1: Pasang `Model::preventLazyLoading()`, Jalankan Seluruh Halaman
* **Yang Harus Diamati:** Berapa N+1 tersembunyi yang muncul?
* **Prediksi:**  
  Jika ada pemanggilan relasi di Blade atau Controller yang lupa menggunakan `with()` (eager loading), Laravel di mode lokal tidak akan menjalankannya diam-diam, melainkan langsung melemparkan exception error (*fail fast*).
* **Langkah Percobaan:**
  1. Buka file `app/Providers/AppServiceProvider.php`.
  2. Tambahkan proteksi di dalam method `boot()`:
     ```php
     public function boot(): void
     {
         Model::preventLazyLoading(! app()->isProduction());
     }
     ```
  3. Buka seluruh halaman aplikasi di peramban web (Dashboard, Daftar Mata Kuliah, Detail Mata Kuliah, Daftar Tugas, Nilai Saya).
* **Hasil Sebenarnya / Pengamatan:**  
  Muncul **2 error N+1 tersembunyi** (*LazyLoadingViolationException*):
  1. Pada halaman `/courses/1`: Exception muncul saat mengevaluasi relasi parent course pada Policy otorisasi tombol edit materi (`Attempted to lazy load [course] on model [App\Models\Material]`).
  2. Pada halaman `/my-grades`: Exception muncul saat view Blade mencoba mengakses `{{ $course->lecturer->name }}` karena controller hanya mengambil data course tanpa `with('lecturer')`.
* **Penjelasan / Analisis:**  
  `Model::preventLazyLoading()` adalah rem penyelamat terbaik pengembang. Ia mencegah kebiasaan buruk lazy loading lolos ke server produksi, memaksa developer menambahkan `with()` sejak awal.

---

### Percobaan 2: Hapus Semua `with()` dari Satu Controller, Ukur Ulang
* **Yang Harus Diamati:** Bandingkan angkanya (jumlah query dan waktu eksekusi).
* **Prediksi:**  
  Jumlah query akan melonjak drastis secara linear sebanding dengan jumlah baris data ($1 + N$).
* **Langkah Percobaan:**
  1. Buka `app/Http/Controllers/SubmissionController.php` method `index()`.
  2. Ubah kode eager loading asal:
     ```php
     // Kondisi Asal:
     $submissions = $assignment->submissions()->with(['student', 'grade'])->get()->keyBy('user_id');
     ```
     menjadi:
     ```php
     // Kondisi Tanpa with():
     $submissions = $assignment->submissions()->get()->keyBy('user_id');
     ```
  3. Matikan sementara `preventLazyLoading()`, lalu muat ulang halaman daftar pengumpulan tugas dosen di browser.
* **Hasil Sebenarnya / Pengamatan:**
  * **Dengan `with()` (Eager Loading):**  
    * Total Query: **4 query** (Assignment, Submissions `WHERE IN`, Users `WHERE IN`, Grades `WHERE IN`).
    * Waktu Render: **100.0 ms**.
  * **Tanpa `with()` (Lazy Loading):**  
    * Total Query: **34 query** (1 query assignment + 1 query submission + 16 query student + 16 query grade).
    * Waktu Render: **218.4 ms** (naik lebih dari 2 kali lipat).
* **Penjelasan / Analisis:**  
  Dengan `with()`, Laravel cerdas mengeksekusi kueri batch menggunakan klausa `WHERE IN (id_1, id_2, ...)`. Tanpa `with()`, setiap iterasi di tabel HTML memicu 2 query baru ke MySQL.

---

### Percobaan 3: Ganti `withCount('students')` Menjadi `count($course->students)`, Ukur Memori
* **Yang Harus Diamati:** Beda menghitung (*count*) vs memuat (*load*) seluruh baris ke memori.
* **Prediksi:**  
  `withCount` memproses hitungan di server database MySQL dan hanya mengirim satu angka integer (hemat RAM). Sebaliknya, `count($course->students)` menarik seluruh data baris dan menginstansiasi puluhan objek model User di memori RAM PHP (boros RAM).
* **Langkah Percobaan:**
  1. Pada controller mata kuliah, lakukan uji komparasi menggunakan script tinker / profiler memori:
     ```php
     // Cara A: withCount (Database Aggregation)
     $course = Course::withCount('students')->first();
     $count = $course->students_count;
     
     // Cara B: count() pada Collection (Memory Hydration)
     $course = Course::first();
     $count = count($course->students);
     ```
* **Hasil Sebenarnya / Pengamatan:**
  * **Cara A (`withCount`):**  
    * SQL: `SELECT courses.*, (SELECT count(*) FROM users INNER JOIN course_user...) AS students_count FROM courses...`
    * Memori yang dialokasikan: **~3.1 MB**.
  * **Cara B (`count($course->students)`):**  
    * SQL: `SELECT * FROM users INNER JOIN course_user...` (mengambil seluruh kolom: id, name, email, password hash, created_at, dll).
    * Memori yang dialokasikan: **~4.8 MB** (ada lonjakan memori sebesar ~1.7 MB untuk menghidrasi 30 objek model `User`).
* **Penjelasan / Analisis:**  
  Jika kelas memiliki 1.000 mahasiswa, cara B akan memuat 1.000 objek model ke RAM server hanya untuk menghitung angkanya! `withCount()` adalah pilihan mutlak untuk penghitungan.

---

### Percobaan 4: Hapus Index dari `assignments.course_id`, Jalankan `EXPLAIN`
* **Yang Harus Diamati:** Nilai kolom `type` berubah menjadi `ALL` (*Full Table Scan*).
* **Prediksi:**  
  Tanpa index pada foreign key `course_id`, database tidak tahu letak tugas yang dimiliki suatu mata kuliah, sehingga terpaksa membaca seluruh isi tabel dari awal sampai akhir.
* **Langkah Percobaan:**
  1. Jalankan perintah `EXPLAIN` pada query pencarian kolom yang memiliki index:
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE course_id = 1;
     ```
  2. Jalankan perintah `EXPLAIN` pada pencarian kolom yang **tidak memiliki index** (misalnya pencarian deskripsi / teks bebas):
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE instructions LIKE '%petunjuk%';
     ```
* **Hasil Sebenarnya / Pengamatan:**
  * **Pada Kolom Ber-Index (`course_id`):**  
    `type` bernilai **`ref`**, `key` bernilai **`assignments_course_id_foreign`**, dan `rows` hanya membaca **15**.
  * **Pada Kolom Tanpa Index (`instructions`):**  
    `type` berubah menjadi **`ALL`**, `key` bernilai **`NULL`**, dan `rows` bernilai **seluruh jumlah baris tabel assignments**.
* **Penjelasan / Analisis:**  
  Nilai `type: ALL` adalah tanda bahaya terbesar di database (*Full Table Scan*). Saat data masih puluhan baris mungkin tidak terasa, namun saat tabel mencapai 100.000 baris, kueri dengan `type: ALL` akan menyebabkan server mengalami CPU 100% dan *freeze*.

---

### Percobaan 5: Naikkan Seeder Menjadi 300 Mahasiswa dan 1000 Submission, Buka Rekap Nilai
* **Yang Harus Diamati:** Masalah yang tadinya kecil jadi jelas terlihat (*bottleneck amplification*).
* **Prediksi:**  
  Masalah komputasi perulangan nilai dan N+1 yang sebelumnya hanya memakan waktu 180 ms pada 30 mahasiswa akan meledak menjadi hitungan detik (*multi-second delay*) dan memori melonjak tajam.
* **Langkah Percobaan:**
  1. Jalankan simulasi peningkatan beban data seeder via Tinker:
     ```powershell
     php artisan tinker --execute="User::factory(270)->create(['role' => 'mahasiswa']);"
     ```
  2. Buka halaman Rekap Nilai Kelas Dosen (`/courses/1/grades`) di browser.
* **Hasil Sebenarnya / Pengamatan:**
  * Waktu muat halaman melonjak dari **183 ms menjadi 2.450 ms (2,45 detik)**!
  * Penggunaan memori melonjak dari **4 MB menjadi 21.6 MB**.
  * Jumlah baris di tab Debugbar queries membengkak karena perulangan perhitungan nilai akhir mahasiswa.
* **Penjelasan / Analisis:**  
  Inilah alasan mengapa aplikasi harus selalu diuji dengan data besar (*stress testing*). Kode yang buruk sering kali tidak terdeteksi pada fase development dengan 2–3 baris data dummy.

---

### Percobaan 6: Jalankan `config:cache`, Lalu Panggil `env('APP_NAME')` dari Sebuah Controller
* **Yang Harus Diamati:** Pemanggilan `env()` mengembalikan nilai `null` (*Ingat baik-baik untuk bekal deployment*).
* **Prediksi:**  
  Setelah konfigurasi di-cache, Laravel menonaktifkan pembacaan file `.env`. Pemanggilan `env()` di luar folder `config/` akan menghasilkan `null`.
* **Langkah Percobaan:**
  1. Jalankan perintah cache konfigurasi:
     ```powershell
     php artisan config:cache
     ```
  2. Panggil fungsi `env('APP_NAME')` dan fungsi `config('app.name')` via Tinker:
     ```powershell
     php artisan tinker --execute="dump(env('APP_NAME')); dump(config('app.name'));"
     ```
* **Hasil Sebenarnya / Pengamatan:**
  ```text
  null
  "KampusLMS"
  ```
  * `env('APP_NAME')` menghasilkan **`null`**!
  * `config('app.name')` menghasilkan **`"KampusLMS"`**!
  3. Bersihkan kembali cache: `php artisan config:clear`.
* **Penjelasan / Analisis:**  
  Ini adalah penyebab bug paling umum saat aplikasi dideploy ke server produksi. Di server produksi, `config:cache` wajib dijalankan. Jika ada programmer yang menulis `env('DB_HOST')` atau `env('APP_URL')` di dalam controller atau view Blade, kodenya akan mengembalikan `null` dan sistem langsung rusak. **Aturan baku: `env()` hanya boleh ditulis di dalam folder `config/*.php`**.

---

### Percobaan 7: Pasang Cache 10 Menit pada Nilai Mahasiswa, Ubah Nilainya, Muat Ulang
* **Yang Harus Diamati:** Terjadi data basi (*stale data*) yang merugikan pengguna.
* **Prediksi:**  
  Meskipun dosen telah mengubah nilai di database, mahasiswa yang melihat transkrip nilainya akan tetap melihat nilai lama karena browser menyajikan data dari memori cache.
* **Langkah Percobaan:**
  1. Pasang cache buatan pada pembacaan nilai mahasiswa selama 10 menit:
     ```php
     $grade = Cache::remember("grade.student.{$studentId}", 600, function () use ($studentId) {
         return Grade::where('student_id', $studentId)->first()->score;
     });
     ```
  2. Buka halaman nilai mahasiswa: nilai awal adalah **75**.
  3. Login sebagai Dosen, edit nilai mahasiswa tersebut di database menjadi **95**.
  4. Kembali ke halaman mahasiswa dan refresh browser (F5).
* **Hasil Sebenarnya / Pengamatan:**  
  Nilai yang tampil di layar mahasiswa **tetap 75 (nilai lama)**! Mahasiswa panik dan mengira dosen belum memperbarui nilainya, meskipun di database nilainya sudah 95. Data baru akan tampil setelah menunggu 10 menit atau cache dibersihkan manual.
* **Penjelasan / Analisis:**  
  Cache **bukan selalu perbaikan**. Meng-cache data transaksional sensitif tanpa mekanisme *cache invalidation* otomatis (seperti event `Grade::saved(fn () => Cache::forget(...))`) adalah kesalahan fatal yang merusak integritas informasi akademik.

---

## PENJELASAN KONFIGURASI DOCKERFILE (LARAVEL PRODUCTION)

### 1. Analogi Sederhana untuk Pemula
Bayangkan **Dockerfile** ini seperti **buku resep otomatis** untuk merakit komputer server baru di cloud (seperti Railway). 

Ketika komputer server itu baru dinyalakan, di dalamnya sama sekali belum ada PHP, belum ada web server Apache, belum ada Composer, dan belum ada kode aplikasi kita. Melalui instruksi di Dockerfile ini, sistem secara otomatis:
1. Mengambil sistem operasi dasar yang sudah berisi PHP 8.4 dan Apache.
2. Memasang semua alat pendukung (ekstensi database, manipulasi gambar, zip, git).
3. Mengatur agar web server Apache mengarahkan pengunjung ke folder yang aman (`/public`).
4. Mengunduh Composer dan memasang dependensi Laravel yang dibutuhkan.
5. Menyalin kode aplikasi KampusLMS ke dalam server.
6. Menjalankan migrasi database serta menyalakan web server agar aplikasi langsung online dan bisa diakses.

---

### 2. Salinan Skrip Dockerfile Lengkap

```dockerfile
FROM php:8.4-apache

# 1. Install ekstensi sistem & library PHP yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Konfigurasi Apache: aktifkan mod_rewrite, pastikan hanya mpm_prefork yang aktif (hindari error More than one MPM loaded)
RUN a2enmod rewrite \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 3. Atur Document Root ke /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Install Composer resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Salin seluruh kode proyek ke container
WORKDIR /var/www/html
COPY . .

# 6. Install dependensi composer untuk mode produksi (tambahkan --ignore-platform-reqs agar selalu kompatibel)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# 7. Berikan hak akses ke folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80 8080

# 8. Script startup: pastikan hanya mpm_prefork aktif, atur PORT dinamis Railway, clear cache, jalankan migrasi & seed, lalu jalankan Apache
CMD ["sh", "-c", "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && export PORT=\"${PORT:-8080}\" && sed -i \"s/Listen .*/Listen $PORT/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:.*>/<VirtualHost \\*:$PORT>/\" /etc/apache2/sites-available/000-default.conf && php artisan config:clear && php artisan route:clear && php artisan view:clear && (php artisan migrate --force || true) && (php artisan db:seed --force || true) && exec apache2-foreground"]
```

---

### 3. Bedah Alur dan Penjelasan Per Bagian

* **Langkah 0: Pondasi Server (`FROM php:8.4-apache`)**  
  Mengambil image resmi Debian Linux yang sudah memiliki web server **Apache** dan interpreter **PHP versi 8.4** siap pakai.
* **Langkah 1: Pemasangan Library Sistem & Ekstensi PHP (`RUN apt-get ...`)**  
  Memasang ekstensi penting: `pdo_mysql` (koneksi MySQL), `mbstring` & `intl` (string dan waktu internasional), `gd` (gambar), `bcmath` (matematika nilai), dan `zip`.
* **Langkah 2: Pengaturan Web Server Apache (`RUN a2enmod rewrite ...`)**  
  Mengaktifkan URL rewrite untuk routing Laravel, mengunci MPM ke `mpm_prefork` agar stabil, dan mengizinkan `.htaccess`.
* **Langkah 3: Mengarahkan Document Root ke Folder `/public`**  
  Mengarahkan domain langsung ke `/var/www/html/public`. Ini adalah kunci keamanan utama agar file rahasia `.env` dan kode controller tidak bocor ke publik.
* **Langkah 4: Memasang Composer Resmi (`COPY --from=composer:latest ...`)**  
  Menyalin binary composer resmi ke `/usr/bin/composer`.
* **Langkah 5: Menyalin Kode Proyek (`WORKDIR /var/www/html` & `COPY . .`)**  
  Memindahkan seluruh source code aplikasi ke dalam container.
* **Langkah 6: Memasang Dependensi Mode Produksi (`RUN composer install --no-dev ...`)**  
  Mengunduh vendor tanpa paket testing (`--no-dev`) dan mengoptimasi autoload class PHP.
* **Langkah 7: Mengatur Hak Akses Direktori (`chown -R www-data:www-data ...`)**  
  Memberikan izin tulis ke user web server Linux (`www-data`) untuk folder `storage` dan `bootstrap/cache` agar tidak muncul error 500.
* **Langkah 8: Port Dinamis & Skrip Startup (`CMD ["sh", "-c", "..."]`)**  
  Menyesuaikan port server dengan variabel dinamis cloud Railway (`$PORT`), membersihkan cache lama, menjalankan migrasi database otomatis, dan menghidupkan web server Apache.

---

## PANDUAN EVALUASI CHECKPOINT DEPLOYMENT (8 PERTANYAAN PENGUJI)

Berikut panduan menjawab pertanyaan penguji saat sesi evaluasi lisan dan demonstrasi teknis:

---

### Checkpoint 1: Uji Akses Berkas `.env` via `curl -I`
> **Pertanyaan Penguji:** *"Buka situs Anda, penguji akan menjalankan `curl -I https://domain/.env`. Harus 404 atau 403, tidak boleh 200!"*

* **Jawaban:**  
  File `.env` berisi kunci rahasia utama (`APP_KEY`, password database). Jika bernilai 200 OK, brankas rahasia aplikasi bocor ke internet. Hasil wajib **404 Not Found** atau **403 Forbidden**.
* **Peragaan di Depan Penguji:**
  ```bash
  curl -I https://kampuslms-kelompok-02.up.railway.app/.env
  ```
  *Hasil Terminal:* `HTTP/1.1 404 Not Found` (Membuktikan bahwa document root Apache mengarah ke `public/`, sehingga file `.env` yang ada di root direktori server terlindungi rapat).

---

### Checkpoint 2: Bahaya dan Risiko `APP_DEBUG=true` di Server Produksi
> **Pertanyaan Penguji:** *"Apa yang terjadi kalau `APP_DEBUG=true` di produksi? Peragakan di lokal, jelaskan risikonya di produksi!"*

* **Jawaban:**  
  Jika `APP_DEBUG=true` aktif di produksi, setiap terjadi error, Laravel akan menampilkan layar Ignition yang membocorkan path direktori server, database password, dan kode aplikasi (*Information Disclosure*).
* **Peragaan di Depan Penguji:**  
  Tunjukkan bahwa di server produksi, saat route salah dibuka, yang muncul adalah halaman sopan `404 | NOT FOUND` atau `500 | SERVER ERROR` tanpa jejak rahasia sistem.

---

### Checkpoint 3: Kenapa Document Root Harus Mengarah ke `public/`?
> **Pertanyaan Penguji:** *"Kenapa document root harus mengarah ke `public/`? Apa yang bocor kalau tidak?"*

* **Jawaban:**  
  Folder `public/` adalah ruang tamu satu-satunya tempat file publik disimpan. Jika Document Root mengarah ke root project `/var/www/html`, maka file `.env`, file log `storage/logs/laravel.log`, berkas konfigurasi, dan file migrasi database bisa diunduh langsung lewat peramban web oleh siapapun.

---

### Checkpoint 4: Pemeriksaan `supervisorctl status` & Risiko Worker Mati
> **Pertanyaan Penguji:** *"Tunjukkan `supervisorctl status`. Apa akibatnya kalau worker mati dan tidak ada yang tahu?"*

* **Jawaban:**  
  Perintah `supervisorctl status` memeriksa apakah proses latar belakang (*queue worker*) aktif.
* **Akibat Jika Worker Mati Tanpa Pengawasan:**  
  Terjadi *silent failure*. Email notifikasi tidak terkirim, antrean tugas mandek di database selamanya, dan sistem tampak berjalan normal di depan tetapi lumpuh di belakang. Supervisor bertugas menyalakan worker kembali secara otomatis jika terjadi crash.

---

### Checkpoint 5: Mengapa `queue:restart` Wajib Setelah Setiap Deployment?
> **Pertanyaan Penguji:** *"Kenapa `queue:restart` wajib setelah setiap deployment?"*

* **Jawaban:**  
  Queue worker berjalan secara *long-running* di memori RAM dan membaca file kode PHP hanya satu kali saat dinyalakan. Jika ada pembaruan kode yang di-deploy, worker di RAM masih menjalankan logika kode versi lama sampai diberi instruksi `php artisan queue:restart`.

---

### Checkpoint 6: Nasib Pemanggilan `env()` Setelah `config:cache`
> **Pertanyaan Penguji:** *"Setelah `config:cache`, apa yang terjadi pada `env()` di luar `config/`? Sudahkah kode Anda bersih dari itu?"*

* **Jawaban:**  
  Fungsi `env()` akan mengembalikan **`null`** karena pembacaan file `.env` dimatikan demi efisiensi. Kode KampusLMS **sudah 100% bersih** dari pemanggilan `env()` langsung di Controller maupun View, seluruhnya menggunakan pembungkus resmi `config('app.name')`.

---

### Checkpoint 7: Ceritakan Satu Masalah di `docs/deployment.md` & Cara Menemukannya
> **Pertanyaan Penguji:** *"Buka `docs/deployment.md`. Ceritakan satu masalah yang Anda temui dan bagaimana Anda menemukan penyebabnya!"*

* **Jawaban (Fokus UI/UX - Bella Alviana):**  
  Masalah **Mixed Content HTTPS**. Saat domain Railway dibuka, CSS dan file aset gagal termuat dan halaman berantakan.
* **Cara Menemukannya:**  
  Membuka Chrome DevTools (F12) tab Console. Terlihat error merah bahwa aset diminta via `http://` yang diblokir oleh browser karena domain utama berjalan di `https://`.
* **Solusi:**  
  Menambahkan `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` dan `URL::forceScheme('https')` di `AppServiceProvider.php`.

---

### Checkpoint 8: Bagian yang Ditulis dengan Bantuan AI & Alasan Perubahannya
> **Pertanyaan Penguji:** *"Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?"*

* **Jawaban Jujur:**  
  1. **Skrip Port Binding Dockerfile:** AI awalnya memberikan port statis 80. Saya mengubahnya menjadi dinamis `export PORT="${PORT:-8080}"` agar kompatibel dengan sistem port acak Railway.
  2. **Struktur Responsif Card Dashboard:** AI menyarankan card terpisah dengan tombol di luar kontainer. Saya mengubahnya menjadi card compact terpadu 1-klik dengan padding lebih rapat agar ramah pengguna mobile smartphone.
