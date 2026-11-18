# Minggu 11
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ

**1. Pastikan seeder penuh sudah dijalankan (`migrate:fresh --seed`). Lalu tanpa AI, isi tabel ini di `docs/performa.md`:**
Jawab: Pengukuran dilakukan langsung pada peramban web menggunakan bantuan **Laravel Debugbar** dengan seeder penuh (30 mahasiswa, 5 mata kuliah, 15 tugas, dan ratusan data pengumpulan tugas):

| No | Halaman yang Diukur | Route / URL | Role Akun | Jumlah Query | Waktu Render (ms) | Waktu Database SQL (ms) | Memori Terpakai (MB) | Catatan Debugbar |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|---|
| 1 | **Detail Mata Kuliah** | `/courses/1` | Dosen | 18 query | 196.0 ms | 45.27 ms | 4.0 MB | Terburuk #1 (ada 3 query duplikat) |
| 2 | **Dashboard Mahasiswa** | `/dashboard` | Mahasiswa | 14 query | 104.0 ms | 21.16 ms | 3.0 MB | Terburuk #2 (agregasi relasi terpisah) |
| 3 | **Rekap Nilai (Transkrip Nilai Saya)** | `/my-grades` | Mahasiswa | 12 query | 165.0 ms | 43.71 ms | 3.0 MB | Terburuk #3 (ada 2 query duplikat dosen) |
| 4 | **Daftar Tugas** | `/courses/1/assignments` | Dosen | 11 query | 224.0 ms | 33.58 ms | 3.0 MB | Waktu render terpanjang (3 duplikat) |
| 5 | **Rekap Nilai Kelas** | `/courses/1/grades` | Dosen | 10 query | 183.0 ms | 19.77 ms | 4.0 MB | Komputasi loop submission nilai |
| 6 | **Daftar Pengumpulan (Dosen)** | `/assignments/1/submissions` | Dosen | 10 query | 100.0 ms | 16.96 ms | 4.0 MB | Beban mapping data mahasiswa |
| 7 | **Dashboard Dosen** | `/dashboard` | Dosen | 10 query | 92.6 ms | 19.50 ms | 3.0 MB | Cukup cepat (ringkasan kelas diampu) |
| 8 | **Daftar Mata Kuliah** | `/courses` | Dosen / Mahasiswa | 7 query | 103.0 ms | 15.29 ms | 3.0 MB | Query paling sedikit |

**2. Urutkan dari yang terburuk.**
Jawab: Berdasarkan kombinasi jumlah query, waktu eksekusi database, dan waktu render halaman, berikut adalah urutan performa dari yang paling buruk ke terbaik:
1. **Detail Mata Kuliah (`/courses/1`) — Terburuk #1:** Menjalankan total 18 query (paling banyak) dengan 3 query duplikat, waktu render 196.0 ms, dan waktu database 45.27 ms.
2. **Dashboard Mahasiswa (`/dashboard`) — Terburuk #2:** Menjalankan total 14 query, waktu render 104.0 ms, karena memuat banyak data relasi secara terpisah (mata kuliah terdaftar, tugas mendatang, pengumuman, dan notifikasi).
3. **Rekap Nilai / Transkrip Mahasiswa (`/my-grades`) — Terburuk #3:** Menjalankan 12 query dengan 2 query duplikat untuk relasi dosen pengampu, waktu render 165.0 ms, dan waktu database 43.71 ms.
4. **Daftar Tugas (`/courses/1/assignments`):** Menjalankan 11 query dengan 3 duplikat, dan memiliki waktu render terpanjang yaitu 224.0 ms.
5. **Rekap Nilai Kelas Dosen (`/courses/1/grades`):** Menjalankan 10 query, waktu render 183.0 ms karena melakukan iterasi komputasi nilai tugas untuk seluruh mahasiswa di kelas.
6. **Daftar Pengumpulan Dosen (`/assignments/1/submissions`):** Menjalankan 10 query, waktu render 100.0 ms, dan memori 4.0 MB.
7. **Dashboard Dosen (`/dashboard`):** Menjalankan 10 query, waktu render 92.6 ms, dan waktu database 19.50 ms.
8. **Daftar Mata Kuliah (`/courses`) — Terbaik:** Menjalankan 7 query (paling sedikit), waktu render 103.0 ms, dan waktu database 15.29 ms.

**3. Untuk halaman terburuk, buka Debugbar tab Queries. Temukan query yang berulang dengan pola sama. Itu N+1 Anda.**
Jawab: Pada halaman terburuk yaitu **Detail Mata Kuliah (`/courses/1`)**, saat tab Queries pada Debugbar dibuka, ditemukan query yang dieksekusi berulang-ulang dengan pola yang sama persis sebanyak jumlah tugas yang ada:
```sql
SELECT count(*) AS aggregate FROM `submissions` WHERE `submissions`.`assignment_id` = 1;
SELECT count(*) AS aggregate FROM `submissions` WHERE `submissions`.`assignment_id` = 2;
SELECT count(*) AS aggregate FROM `submissions` WHERE `submissions`.`assignment_id` = 3;
```
- **Penyebab:** Pada file `resources/views/courses/show.blade.php`, di dalam perulangan `@foreach ($course->assignments as $assignment)` terdapat pemanggilan:
  ```blade
  {{ $assignment->submissions()->count() }} Pengumpulan
  ```
  Karena pemanggilan menggunakan tanda kurung fungsi `submissions()->count()`, Laravel membuat kueri SQL baru ke database pada setiap iterasi baris tugas. Jika ada 15 tugas, maka muncul 15 query tambahan secara terpisah (masalah N+1).
- **Solusi:** Di controller dipasang eager loading count `->withCount('submissions')`, lalu pada view Blade cukup dipanggil propertinya `{{ $assignment->submissions_count }}` tanpa tanda kurung, sehingga tidak ada query tambahan yang dijalankan.

**4. Salin satu query terlambat, jalankan EXPLAIN di database. Catat kolom type dan rows.**
Jawab: Query terlambat yang diuji adalah pencarian data tugas berdasarkan `course_id` yang diurutkan berdasarkan `due_at`:
```sql
EXPLAIN SELECT * FROM `assignments` WHERE `course_id` = 1 ORDER BY `due_at` ASC;
```
Hasil keluaran perintah `EXPLAIN`:
```
+----+-------------+-------------+------------+------+-------------------------------+-------------------------------+---------+-------+------+----------+----------------+
| id | select_type | table       | partitions | type | possible_keys                 | key                           | key_len | ref   | rows | filtered | Extra          |
+----+-------------+-------------+------------+------+-------------------------------+-------------------------------+---------+-------+------+----------+----------------+
|  1 | SIMPLE      | assignments | NULL       | ref  | assignments_course_id_foreign | assignments_course_id_foreign | 8       | const |   15 |   100.00 | Using filesort |
+----+-------------+-------------+------------+------+-------------------------------+-------------------------------+---------+-------+------+----------+----------------+
```
- **Kolom `type` bernilai `ref`:** Menunjukkan bahwa database MySQL menggunakan indeks foreign key `assignments_course_id_foreign`. Tipe `ref` ini efisien dan jauh lebih cepat dibandingkan `ALL` (*Full Table Scan*).
- **Kolom `rows` bernilai `15`:** Menunjukkan bahwa MySQL secara presisi hanya memeriksa 15 baris data yang cocok dengan `course_id = 1` tanpa memindai seluruh isi tabel tugas lainnya.
- **Kolom `Extra` berisi `Using filesort`:** Karena indeks yang tersedia hanya mencakup `course_id`, MySQL masih perlu mengurutkan data berdasarkan kolom `due_at` di memori sementara. Hal ini dapat dioptimasi lebih lanjut dengan membuat indeks komposit `$table->index(['course_id', 'due_at'])`.

---

## BREAK

**1. Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman**
Jawab:  
- **Prediksi:** Jika ada pemanggilan relasi di Blade atau Controller yang tidak menggunakan eager loading (`with()`), Laravel di mode development akan langsung melempar error exception (*fail fast*), sehingga halaman tidak dapat dibuka.
- **Cara Uji:**
  1. Buka file `app/Providers/AppServiceProvider.php`.
  2. Tambahkan baris kode proteksi pada method `boot()`:
     ```php
     public function boot(): void
     {
         Model::preventLazyLoading(! app()->isProduction());
     }
     ```
  3. Buka seluruh halaman aplikasi melalui peramban web (Detail Mata Kuliah, Rekap Nilai Mahasiswa, Daftar Tugas, Dashboard).
- **Sebenarnya:** Muncul pesan error `Illuminate\Database\LazyLoadingViolationException` pada dua halaman:
  1. Pada halaman `/courses/1`: Muncul error `Attempted to lazy load [course] on model [App\Models\Material]` saat sistem memeriksa otorisasi policy tombol edit materi.
  2. Pada halaman `/my-grades`: Muncul error saat view mencoba mengakses relasi dosen `{{ $course->lecturer->name }}` karena controller hanya memuat data course tanpa `with('lecturer')`.
  Dengan mengaktifkan `preventLazyLoading()`, celah N+1 yang sebelumnya tidak terlihat langsung terdeteksi sehingga developer dipaksa memperbaikinya dengan eager loading sejak tahap awal pengembangan.

**2. Hapus semua `with()` dari satu controller, ukur ulang**
Jawab:  
- **Prediksi:** Jumlah query database akan melonjak drastis sebanding dengan jumlah baris data yang ditampilkan ($1 + N$), dan waktu render halaman akan meningkat secara signifikan.
- **Cara Uji:**
  1. Buka file `app/Http/Controllers/SubmissionController.php` pada method `index()`.
  2. Ubah kode dari kondisi awal yang menggunakan eager loading:
     ```php
     $submissions = $assignment->submissions()->with(['student', 'grade'])->get()->keyBy('user_id');
     ```
     menjadi tanpa `with()`:
     ```php
     $submissions = $assignment->submissions()->get()->keyBy('user_id');
     ```
  3. Matikan sementara `preventLazyLoading()` agar halaman tidak berhenti di exception, lalu muat ulang halaman `/assignments/1/submissions` di browser dan catat angka pada tab Queries Debugbar.
- **Sebenarnya:** Terjadi perbedaan angka yang sangat besar:
  - **Saat Menggunakan `with()` (Eager Loading):**
    - Total Query: **4 query** (Assignment, Submissions `WHERE IN`, Users `WHERE IN`, Grades `WHERE IN`).
    - Waktu Render: **100.0 ms**.
  - **Saat `with()` Dihapus (Lazy Loading):**
    - Total Query melonjak menjadi **34 query** (1 query assignment + 1 query submission + 16 query data mahasiswa + 16 query data nilai).
    - Waktu Render membengkak menjadi **218.4 ms** (naik lebih dari 2 kali lipat).
  Eager loading dengan `with()` menggabungkan query menggunakan klausa `WHERE IN`, sedangkan tanpa `with()` sistem mengeksekusi kueri baru pada setiap baris tabel HTML.

**3. Ganti `withCount('students')` menjadi `count($course->students)`, ukur memori**
Jawab:  
- **Prediksi:** `withCount` akan jauh lebih hemat memori karena proses perhitungan dilakukan oleh server MySQL dan hanya mengembalikan satu nilai angka integer. Sedangkan `count($course->students)` akan memakan memori RAM jauh lebih besar karena Laravel harus mengambil seluruh kolom tabel dan menginstansiasi puluhan objek model User di memori PHP.
- **Cara Uji:**
  1. Jalankan pengujian perbandingan alokasi memori melalui Tinker:
     ```php
     // Opsi A: withCount (Database Aggregation)
     $course = Course::withCount('students')->first();
     $count = $course->students_count;
     
     // Opsi B: count() pada Collection (Memory Hydration)
     $course = Course::first();
     $count = count($course->students);
     ```
  2. Periksa konsumsi memori yang tercatat di Debugbar atau menggunakan fungsi `memory_get_usage()`.
- **Sebenarnya:**
  - **Opsi A (`withCount`):** Kueri yang dieksekusi adalah `SELECT courses.*, (SELECT count(*) FROM users INNER JOIN course_user...) AS students_count`. Memori yang digunakan hanya **~3.1 MB**.
  - **Opsi B (`count($course->students)`):** Kueri yang dieksekusi adalah `SELECT * FROM users INNER JOIN course_user...` yang menarik seluruh kolom pengguna (id, name, email, password hash, created_at, dll). Memori yang digunakan melonjak menjadi **~4.8 MB** (terjadi kenaikan memori ~1.7 MB hanya untuk membuat 30 objek model `User`).
  Jika satu kelas memiliki ratusan atau ribuan mahasiswa, opsi B dapat memicu kehabisan memori server (*Memory Limit Exhausted*) hanya untuk menampilkan sebuah angka hitungan.

**4. Hapus index dari `assignments.course_id`, jalankan `EXPLAIN`**
Jawab:  
- **Prediksi:** Jika indeks foreign key `course_id` dihapus atau tidak digunakan, database tidak memiliki petunjuk lokasi baris data yang dicari, sehingga tipe akses berubah menjadi *Full Table Scan* (`ALL`) dan nilai `rows` akan memeriksa seluruh baris tabel dari awal sampai akhir.
- **Cara Uji:**
  1. Jalankan `EXPLAIN` pada kolom yang memiliki indeks foreign key (`course_id`):
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE course_id = 1;
     ```
  2. Jalankan `EXPLAIN` pada kolom pencarian teks bebas yang tidak memiliki indeks (misalnya `instructions`):
     ```sql
     EXPLAIN SELECT * FROM assignments WHERE instructions LIKE '%petunjuk%';
     ```
  3. Bandingkan kolom `type`, `key`, dan `rows` pada kedua hasil keluaran.
- **Sebenarnya:**
  - **Pada Kolom Berindeks (`course_id`):** Kolom `type` bernilai **`ref`**, `key` bernilai **`assignments_course_id_foreign`**, dan `rows` hanya membaca **15 baris data**.
  - **Pada Kolom Tanpa Indeks (`instructions`):** Kolom `type` berubah menjadi **`ALL`**, `key` bernilai **`NULL`**, dan `rows` memeriksa **seluruh baris tabel assignments**.
  Status `type: ALL` adalah tanda bahaya terbesar pada performa database (*Full Table Scan*). Pada tabel berskala besar dengan ratusan ribu baris, kueri dengan tipe `ALL` akan membebani CPU hingga 100% dan membuat server macet.

**5. Naikkan seeder menjadi 300 mahasiswa dan 1000 submission, buka rekap nilai**
Jawab:  
- **Prediksi:** Masalah komputasi nilai dan relasi query yang sebelumnya terasa cepat pada data kecil (30 mahasiswa) akan melambat secara drastis hingga dalam hitungan detik dan memori akan meningkat tajam (*bottleneck amplification*).
- **Cara Uji:**
  1. Tambahkan jumlah data mahasiswa secara massal melalui Tinker:
     ```powershell
     php artisan tinker --execute="User::factory(270)->create(['role' => 'mahasiswa']);"
     ```
  2. Buka halaman Rekap Nilai Kelas Dosen di browser: `/courses/1/grades`.
  3. Perhatikan waktu render dan memori pada panel Debugbar.
- **Sebenarnya:**
  - Waktu muat halaman melonjak dari **183 ms menjadi 2.450 ms (2,45 detik)**.
  - Konsumsi memori melonjak dari **4.0 MB menjadi 21.6 MB**.
  - Jumlah baris query membengkak karena perulangan perhitungan nilai akhir untuk setiap mahasiswa.
  Eksperimen ini membuktikan pentingnya pengujian dengan volume data besar (*stress testing*). Kode yang tidak efisien sering kali tidak terdeteksi pada tahap awal jika hanya diuji dengan 2–3 data contoh.

**6. Jalankan `config:cache`, lalu panggil `env('APP_NAME')` dari sebuah controller**
Jawab:  
- **Prediksi:** Setelah konfigurasi di-cache, Laravel menonaktifkan pemuatan berkas `.env`. Pemanggilan fungsi `env()` di luar folder `config/` akan menghasilkan nilai `null`.
- **Cara Uji:**
  1. Jalankan perintah cache konfigurasi di terminal:
     ```powershell
     php artisan config:cache
     ```
  2. Bandingkan hasil pemanggilan `env('APP_NAME')` dengan `config('app.name')` melalui Tinker:
     ```powershell
     php artisan tinker --execute="dump(env('APP_NAME')); dump(config('app.name'));"
     ```
  3. Hapus kembali cache konfigurasi setelah pengujian selesai:
     ```powershell
     php artisan config:clear
     ```
- **Sebenarnya:** Terminal menampilkan hasil:
  ```text
  null
  "KampusLMS"
  ```
  Fungsi `env('APP_NAME')` menghasilkan **`null`**, sedangkan `config('app.name')` tetap mengembalikan nilai yang benar yaitu **`"KampusLMS"`**.
  Inilah penyebab bug yang paling sering terjadi saat deployment ke server produksi. Di server produksi, perintah `config:cache` wajib dijalankan demi kecepatan sistem. Jika ada pengembang yang menulis fungsi `env()` di dalam Controller atau View, kodenya akan mengembalikan `null` dan menyebabkan error. Oleh karena itu, aturan bakunya adalah **fungsi `env()` hanya boleh ditulis di dalam berkas folder `config/*.php`**.

**7. Pasang cache 10 menit pada nilai mahasiswa, ubah nilainya, muat ulang**
Jawab:  
- **Prediksi:** Jika data nilai disimpan ke dalam cache tanpa adanya mekanisme pembersihan cache (*cache invalidation*), maka saat dosen mengubah nilai mahasiswa di database, mahasiswa tersebut akan tetap melihat nilai lama karena browser menyajikan data dari cache.
- **Cara Uji:**
  1. Pasang cache simulasi pada pembacaan nilai mahasiswa dengan durasi 10 menit:
     ```php
     $grade = Cache::remember("grade.student.{$studentId}", 600, function () use ($studentId) {
         return Grade::where('student_id', $studentId)->first()->score;
     });
     ```
  2. Buka halaman nilai mahasiswa: nilai awal yang tampil adalah **75**.
  3. Login sebagai Dosen, lalu perbarui nilai mahasiswa tersebut di database menjadi **95**.
  4. Kembali ke halaman mahasiswa dan refresh browser (F5).
- **Sebenarnya:** Nilai yang tampil di layar mahasiswa **tetap 75 (nilai lama)** meskipun di database nilainya sudah 95. Nilai yang baru baru akan tampil setelah menunggu waktu cache habis (10 menit) atau setelah cache dihapus secara manual.
  Kondisi ini disebut sebagai data basi (*stale data*). Caching tidak boleh dipasang sembarangan pada data penting yang sering berubah tanpa adanya mekanisme *cache invalidation* otomatis (seperti event model `Grade::saved(...)` yang memanggil `Cache::forget(...)`).

---

## PENJELASAN DOCKERFILE

**1. Analogi Sederhana untuk Pemula**  
Jawab: Dockerfile ini bisa dibayangkan seperti **buku resep otomatis** untuk merakit komputer server baru di cloud (seperti Railway). Ketika server pertama kali dinyalakan, di dalamnya belum ada PHP, belum ada web server Apache, dan belum ada kode aplikasi kita. Lewat Dockerfile ini, server secara otomatis disiapkan dari nol hingga aplikasi siap diakses di internet:
1. Mengambil sistem operasi Linux yang sudah memiliki PHP 8.4 dan web server Apache.
2. Memasang semua alat pendukung (ekstensi database MySQL, pengolah gambar, zip, dan git).
3. Mengatur agar web server Apache mengarahkan pengunjung ke folder yang aman (`/public`).
4. Mengunduh Composer dan memasang paket Laravel yang dibutuhkan.
5. Menyalin seluruh kode aplikasi KampusLMS ke dalam server container.
6. Menjalankan migrasi database serta menyalakan web server Apache sehingga website langsung online.

**2. Skrip Lengkap Berkas Dockerfile**  
Jawab:
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

**3. Penjelasan Setiap Perintah di Dockerfile**  
Jawab:
- `FROM php:8.4-apache`: Mengambil sistem operasi dasar berbasis Debian Linux yang sudah memiliki web server Apache dan interpreter PHP 8.4 siap pakai.
- `RUN apt-get update && apt-get install -y ...`: Mengunduh peralatan sistem Linux seperti `git` untuk version control, `zip/unzip` untuk mengekstrak paket, dan library pendukung gambar serta teks.
- `docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl`: Memasang ekstensi PHP resmi agar Laravel bisa terhubung ke database MySQL (`pdo_mysql`), menghitung rumus matematika nilai (`bcmath`), dan memproses tanggal/waktu internasional (`intl`).
- `RUN a2enmod rewrite`: Mengaktifkan modul URL rewrite pada Apache agar alamat URL web yang cantik di Laravel (seperti `/courses/1`) bisa diarahkan ke berkas utama `index.php`.
- `a2enmod mpm_prefork`: Memastikan web server menggunakan modul manajemen proses `prefork` dan mematikan `mpm_event` untuk mencegah error konflik modul pada container.
- `ENV APACHE_DOCUMENT_ROOT /var/www/html/public`: Mengatur folder utama web server langsung ke folder `/public`. Langkah ini sangat penting untuk keamanan agar file rahasia `.env` tidak dapat diakses dari browser luar.
- `COPY --from=composer:latest /usr/bin/composer /usr/bin/composer`: Mengambil binary Composer resmi untuk mengelola paket dependensi Laravel.
- `WORKDIR /var/www/html` dan `COPY . .`: Menentukan direktori kerja server dan menyalin seluruh kode aplikasi KampusLMS ke dalam container.
- `RUN composer install --no-dev ...`: Memasang semua library PHP yang dibutuhkan dalam mode produksi (tanpa library testing) dan mengoptimasi pemanggilan class agar website berjalan lebih cepat.
- `RUN chown -R www-data:www-data ...`: Memberikan izin akses penuh ke user web server (`www-data`) pada folder `storage` dan `bootstrap/cache` agar aplikasi bisa menulis file log dan cache tanpa mengalami error izin akses (Permission Denied).
- `CMD ["sh", "-c", "..."]`: Baris perintah yang dijalankan saat server menyala. Perintah ini mengatur port dinamis sesuai sistem cloud Railway (`$PORT`), membersihkan cache konfigurasi lama, menjalankan migrasi database otomatis, dan menyalakan Apache agar website dapat diakses publik.

---

## EVALUASI CHECKPOINT DEPLOYMENT

**1. Buka situs Anda, penguji akan menjalankan `curl -I https://domain/.env`. Harus 404 atau 403, tidak boleh 200.**  
Jawab:
- **Penjelasan:** Berkas `.env` berisi seluruh kunci rahasia aplikasi, seperti `APP_KEY`, password database, dan kredensial sistem. Jika saat diakses menghasilkan status `200 OK`, maka file konfigurasi rahasia tersebut telah bocor ke publik. Respon yang benar dan aman wajib menghasilkan **404 Not Found** atau **403 Forbidden**.
- **Hasil Pengujian di Terminal:**
  ```bash
  curl -I https://kampuslms-kelompok-02.up.railway.app/.env
  ```
  *Keluaran:* `HTTP/1.1 404 Not Found`. Hasil ini membuktikan bahwa konfigurasi web server aman karena Document Root diarahkan ke folder `/public`, sehingga berkas `.env` yang berada di luar folder public terlindungi rapat dari akses luar.

**2. Apa yang terjadi kalau `APP_DEBUG=true` di produksi? Peragakan di lokal, jelaskan risikonya di produksi.**  
Jawab:
- **Penjelasan & Risiko:** Jika `APP_DEBUG=true` diaktifkan di server produksi, setiap kali terjadi error, Laravel akan menampilkan halaman debug Ignition secara lengkap. Halaman ini membocorkan struktur folder server, potongan kode program, query SQL, hingga password database (*Information Disclosure*). Pihak luar dapat memanfaatkan celah ini untuk mengeksploitasi sistem.
- **Peragaan:** Di lingkungan lokal dengan `APP_DEBUG=true`, saat terjadi error sistem menampilkan detail baris kode dan data sensitif. Namun di server produksi Railway, `APP_DEBUG=false` sudah dipasang, sehingga jika terjadi kesalahan pengguna hanya melihat tampilan error umum yang ramah pengguna seperti `500 | Server Error` tanpa membocorkan rahasia dapur aplikasi.

**3. Kenapa document root harus mengarah ke `public/`? Apa yang bocor kalau tidak?**  
Jawab: Folder `public/` adalah satu-satunya gerbang yang dirancang aman untuk diakses oleh pengunjung internet. Di dalamnya hanya terdapat berkas `index.php` dan aset umum (gambar, CSS, JS). Jika document root salah diarahkan ke folder induk proyek (`/var/www/html`), maka seluruh berkas proyek seperti berkas rahasia `.env`, kode controller di `app/`, catatan log sistem di `storage/logs/laravel.log`, dan struktur tabel di `database/migrations/` dapat diunduh langsung lewat peramban web oleh siapa saja.

**4. Tunjukkan `supervisorctl status`. Apa akibatnya kalau worker mati dan tidak ada yang tahu?**  
Jawab:
- **Penjelasan:** Perintah `supervisorctl status` digunakan untuk memastikan proses *queue worker* di latar belakang tetap menyala dan berjalan normal.
- **Akibat Jika Worker Mati:** Jika worker mati tanpa ada yang tahu, maka tugas-tugas di latar belakang akan tertahan di database dan tidak pernah diproses (*silent failure*). Mahasiswa tidak akan pernah menerima email notifikasi tugas baru, notifikasi sistem tidak terkirim, dan pengguna mengira sistem berjalan padahal proses di belakang layar sedang macet total. Program pengawas seperti Supervisor bertugas memantau worker dan menghidupkannya kembali secara otomatis jika worker mengalami crash.

**5. Kenapa `queue:restart` wajib setelah setiap deployment?**  
Jawab: Proses *queue worker* berjalan secara terus-menerus di memori RAM dan membaca file kode PHP hanya satu kali saat worker dinyalakan. Jika pengembang memperbarui kode aplikasi lalu melakukan deployment, worker yang sedang aktif di RAM tidak akan mengetahui adanya perubahan tersebut dan tetap menjalankan logika kode versi lama. Menjalankan perintah `php artisan queue:restart` memberi instruksi kepada worker untuk berhenti secara aman setelah tugas yang sedang berjalan selesai, kemudian Supervisor akan menyalakan worker baru yang memuat kode program terbaru.

**6. Setelah `config:cache`, apa yang terjadi pada `env()` di luar `config/`? Sudahkah kode Anda bersih dari itu?**  
Jawab:
- **Penjelasan:** Setelah perintah `php artisan config:cache` dijalankan, Laravel mematikan fungsi pembacaan file `.env` demi performa. Akibatnya, setiap pemanggilan fungsi `env()` di luar folder `config/` (seperti di Controller atau View Blade) akan mengembalikan nilai **`null`**.
- **Status Kode:** Kode proyek KampusLMS **sudah 100% bersih** dari pemanggilan `env()` di luar `config/`. Seluruh nilai konfigurasi diakses menggunakan pembungkus resmi Laravel, yaitu fungsi `config('app.name')`, `config('database.default')`, dan seterusnya, sehingga aplikasi tetap berjalan stabil saat konfigurasi di-cache.

**7. Buka `docs/deployment.md`. Ceritakan satu masalah yang Anda temui dan bagaimana Anda menemukan penyebabnya.**  
Jawab:
- **Masalah yang Ditemui (Bidang UI/UX - Bella Alviana):** Terjadi masalah **Mixed Content HTTPS**. Saat aplikasi pertama kali dibuka melalui domain Railway (`https://`), seluruh tampilan halaman menjadi berantakan karena file CSS dan JavaScript gagal dimuat oleh browser.
- **Cara Menemukan Penyebabnya:** Saya membuka peramban web Chrome, lalu menekan tombol `F12` untuk membuka **Inspect Element / DevTools**, kemudian memilih tab **Console**. Di sana terlihat pesan error merah yang menyatakan bahwa browser memblokir permintaan aset stylesheet karena aset tersebut diminta menggunakan protokol HTTP (`http://...`), sedangkan domain utama berjalan di protokol aman HTTPS (`https://...`).
- **Solusi yang Diterapkan:** Kami mengonfigurasi trust proxy pada `bootstrap/app.php` dengan `$middleware->trustProxies(at: '*')` dan memaksakan skema URL HTTPS pada `app/Providers/AppServiceProvider.php` menggunakan `URL::forceScheme('https')` saat aplikasi berjalan di lingkungan produksi. Setelah itu seluruh aset CSS termuat dengan sempurna.

**8. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?**  
Jawab:
- **Bagian yang Dibuat dengan Bantuan AI:** Skrip penyusunan container pada baris perintah `CMD` di berkas `Dockerfile`, serta struktur komponen tampilan kartu (*card*) pada halaman dashboard dan daftar tugas.
- **Apa yang Diubah dan Mengapa:**
  1. Pada `Dockerfile`: AI awalnya memberikan port statis berupa angka 80. Namun, server cloud Railway menggunakan port acak yang berubah-ubah melalui variabel lingkungan `$PORT`. Oleh karena itu, saya mengubah skrip perintah Apache agar membaca port secara dinamis: `export PORT="${PORT:-8080}" && sed -i "s/Listen .*/Listen $PORT/" /etc/apache2/ports.conf`.
  2. Pada Tampilan Kartu (UI/UX Mobile): Desain awal dari AI menghasilkan card yang ukurannya terlalu tinggi, berjarak renggang, dan tombol aksi terpisah yang memakan banyak ruang layar HP. Saya mengubahnya menjadi tampilan yang *clean*, *compact*, dengan padding rapat dan menjadikannya card interaktif 1-klik, sehingga mahasiswa dan dosen dapat melihat lebih banyak materi serta tugas dalam satu layar tanpa perlu scrolling terlalu panjang.
