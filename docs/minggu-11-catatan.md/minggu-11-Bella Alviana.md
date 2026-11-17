# Catatan Praktikum Minggu 11: Optimisasi Performa & Deployment

**Nama:** Bella Alviana  
**NIM:** 10241015  
**Kelas:** B (Sistem Informasi)  
**Kelompok:** Kelompok 02  
**Fokus Kontribusi:** UI/UX & Frontend Optimization, Mobile Responsive Testing, Penjelasan Dockerfile & Evaluasi Checkpoint Deployment.

---

# BAGIAN I: EVALUASI OPTIMISASI PERFORMA (READ & BREAK)

### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?

* **Jawaban:**  
  Berdasarkan tabel pengukuran riil pada file `docs/performa.md`, halaman yang paling lambat dan memiliki jumlah query terbanyak sebelum optimisasi adalah **Halaman Detail Mata Kuliah (`/courses/1`)**.
  * Total Query: **18 query** (Debugbar menandai terdapat **3 duplicate queries**).
  * Waktu Render: **196.0 ms** (dengan waktu eksekusi database/SQL mencapai 45.27 ms).
  * Penggunaan Memori: **4 MB**.

* **Kenapa Halaman Ini Paling Buruk? (Bahasa Sederhana):**
  1. **Ada masalah N+1 saat menghitung jumlah pengumpulan tugas:**  
     Di dalam file tampilan `resources/views/courses/show.blade.php`, setiap baris tugas memanggil query hitung secara mandiri:
     ```blade
     {{ $assignment->submissions()->count() }} Pengumpulan
     ```
     Karena diletakkan di dalam perulangan `@foreach`, database MySQL dipaksa menjalankan query `SELECT COUNT(*)` berulang kali sebanyak tugas yang ada di kelas tersebut.
  2. **Ada query duplikat saat memeriksa izin tombol aksi (Policy):**  
     Ketika sistem memeriksa hak akses apakah tombol edit atau hapus materi dan tugas boleh tampil, Laravel mengambil data mata kuliah induk berulang kali (`select * from courses where id = '1'`) karena relasi induk belum dimuat lebih dulu (*lazy loading*).

* **Langkah Peragaan di Depan Penguji:**
  1. Buka file [**`docs/performa.md`**](file:///d:/kampuslms-kelompok-02/docs/performa.md) di editor VS Code, lalu tunjukkan baris nomor 1 pada tabel baseline: halaman Detail Mata Kuliah mencatat 18 query dengan 3 query duplikat.
  2. Buka browser dan akses halaman Detail Mata Kuliah (`http://127.0.0.1:8000/courses/1`) dengan akun Dosen.
  3. Tunjukkan ke penguji panel Laravel Debugbar di bawah layar: perlihatkan bahwa setelah diperbaiki menggunakan `withCount('submissions')` dan eager loading `materials.course`, jumlah query turun drastis dan bebas dari query duplikat.

---

### 2. Peragakan N+1 di aplikasi Anda: Matikan `with()` pada `SubmissionController` dan tunjukkan lonjakan query di Debugbar.
* **Jawaban:**  
  Masalah N+1 adalah kondisi ketika Laravel menjalankan query database secara berulang untuk mengambil data relasi satu per satu di setiap baris perulangan.
* **Langkah Peragaan pada `SubmissionController.php`:**
  1. Buka file `app/Http/Controllers/SubmissionController.php` pada baris 37 di method `index()`:
     ```php
     // Kondisi Asal (Aman dengan Eager Loading):
     $submissions = $assignment->submissions()->with(['student', 'grade'])->get()->keyBy('user_id');
     ```
  2. Buka halaman daftar pengumpulan tugas di browser sebagai Dosen. Tunjukkan ke penguji panel Debugbar di bawah layar: query hanya berjumlah sedikit (**sekitar 3–4 query**).
  3. Ubah sementara kode di `SubmissionController.php` dengan mematikan `with(['student', 'grade'])`:
     ```php
     // Kondisi N+1 (with dimatikan):
     $submissions = $assignment->submissions()->get()->keyBy('user_id');
     ```
  4. Nonaktifkan sementara proteksi lazy di `app/Providers/AppServiceProvider.php` jika aktif, lalu refresh halaman di browser.
  5. Tunjukkan ke penguji: **Jumlah query di Debugbar langsung melonjak drastis**, dan tab *Queries* dipenuhi query berulang untuk mengambil data siswa dan nilai di setiap barisnya.
  6. Kembalikan kodenya ke kondisi semula.

* **Penjelasan:** `with()` berfungsi untuk mengambil data relasi sekaligus (*eager loading* via `WHERE IN (...)`). Ketika `with()` dimatikan, Laravel mengambil data relasi secara terpisah pada setiap baris perulangan sehingga kueri membengkak.

---

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?

* **Jawaban:**
  Perbedaannya terletak pada **tempat pemrosesan hitungan** dan **beban data yang dikirim**:

  1. **`withCount('students')` (Hemat & Cepat):**
     * Proses menghitung jumlah mahasiswa langsung dilakukan oleh database MySQL menggunakan fungsi agregasi SQL: `SELECT COUNT(*)`.
     * Misalnya dalam satu mata kuliah terdapat 30 mahasiswa, database cukup menghitung dan mengirimkan satu angka integer `30` ke Laravel tanpa perlu mengambil seluruh data mahasiswa.
     * Hasilnya otomatis disimpan ke dalam atribut `$course->students_count` tanpa membebani memori RAM server.

  2. **`count($course->students)` (Boros & Lambat):**
     * Laravel memerintahkan database untuk mengambil **seluruh baris dan seluruh kolom** data mahasiswa terlebih dahulu (`SELECT * FROM users...`), seperti `id`, `name`, `email`, password hash, hingga tanggal dibuat.
     * Seluruh data tersebut dikirim lewat jaringan dan dimuat ke dalam memori RAM PHP untuk diubah menjadi 30 objek model `User` lengkap (*hidrasi model*), baru kemudian dihitung jumlahnya menggunakan fungsi `count()` di PHP.

* **Kenapa yang Kedua (`count`) Sangat Boros?**
  * **Boros Memori (RAM):** Semakin banyak data mahasiswa (misalnya 300 atau 1.000 mahasiswa), semakin besar pula memori RAM server yang tersedot untuk menyimpan ratusan objek model lengkap, padahal yang dibutuhkan di layar hanyalah sebuah angka.
  * **Waktu Proses CPU Lebih Lama:** Mengubah ratusan data mentah dari database menjadi objek model di PHP memakan waktu proses komputasi yang tidak perlu.
  * **Risiko Terjadi Query N+1:** Jika `count($course->students)` dipanggil di dalam tabel perulangan `@foreach`, database akan berulang kali menarik seluruh data mahasiswa untuk setiap mata kuliah yang ditampilkan di halaman.

* **Analogi Sederhana untuk Pemula:**
  * *Bayangkan Anda ingin tahu berapa jumlah buku di dalam sebuah kardus tertutup:*
    * **Cara `withCount`:** Anda cukup bertanya kepada petugas gudang *"Ada berapa buku di dalam kardus itu?"*, petugas langsung menjawab *"Ada 30 buku"*. Sangat cepat dan tidak menghabiskan tempat di meja Anda.
    * **Cara `count(...)`:** Anda membuka kardus tersebut, mengeluarkan seluruh 30 buku ke atas meja satu per satu, menumpuknya hingga meja penuh sesak (boros RAM), baru kemudian menghitungnya dengan jari *"1, 2, 3... 30"*. Meja Anda menjadi penuh dan Anda kelelahan, padahal yang Anda butuhkan hanyalah angkanya saja.

---

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.
* **Jawaban:**  
  Index berfungsi seperti **daftar isi pada buku**. Tanpa index, database terpaksa membaca buku dari halaman 1 sampai halaman terakhir (*Full Table Scan* / tipe `ALL`). Dengan index, database langsung melompat ke baris yang dicari (*tipe `ref`*).
* **Langkah Peragaan:**
  1. Buka terminal dan masuk ke Tinker:
     ```powershell
     php artisan tinker
     ```
  2. Jalankan perintah analisis `EXPLAIN`:
     ```php
     DB::select("EXPLAIN SELECT * FROM assignments WHERE course_id = 1 ORDER BY due_at");
     ```
  3. Tunjukkan hasilnya ke dosen:
     * **Kolom `type` bertuliskan `ref`** (artinya database membaca lewat index, bukan memindai seluruh tabel).
     * **Kolom `key`** memuat nama index: `assignments_course_id_foreign` atau `assignments_course_status_due_idx`.

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
  - Data ini bersifat transaksi langsung (*real-time*).
  - **Sisi Mahasiswa:** Begitu mahasiswa mengunggah file tugas, mereka langsung me-refresh dashboard untuk memastikan statusnya berubah menjadi "Sudah Dikumpulkan". Jika data ini di-cache (misalnya 10 menit), statusnya akan tetap "Belum Mengumpulkan". Mahasiswa akan panik mengira file tugasnya gagal terkirim dan mencoba upload berulang kali.
  - **Sisi Dosen:** Begitu dosen selesai memberi nilai pada satu tugas, tugas itu harus langsung hilang dari antrean penilaian. Jika di-cache, tugas yang sudah dinilai akan tetap muncul di layar dan berisiko dinilai dua kali.

---

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?

- **Apa yang Terjadi?**  
  Ketika perintah `php artisan config:cache` dijalankan di terminal:
  1. Laravel menggabungkan seluruh file di folder `config/` menjadi satu file cepat di `bootstrap/cache/config.php`.
  2. Setelah file cache dibuat, Laravel **sengaja mematikan pembacaan file `.env`**.
  3. Akibatnya, semua pemanggilan fungsi `env('NAMA_KEY')` di luar folder `config/` (seperti di Controller atau View Blade) akan **selalu menghasilkan nilai `null`**.

- **Kenapa Ini Penting Saat Deployment ke Server Produksi?**
  - Di server produksi asli, menjalankan perintah `php artisan config:cache` adalah kewajiban standar agar loading website menjadi sangat cepat.
  - Jika kita masih menulis `env()` di dalam Controller atau View, aplikasi akan langsung rusak dan memunculkan error saat di server produksi karena nilainya berubah menjadi `null` (misalnya link rusak, pengiriman email gagal, atau nama aplikasi kosong).
  - Oleh karena itu, seluruh nilai `.env` wajib dibungkus di dalam folder `config/*.php` (misalnya `config/app.php`), lalu dipanggil di kode menggunakan fungsi `config('app.name')`.

---

# BAGIAN II: PENJELASAN KONFIGURASI DOCKERFILE (LARAVEL PRODUCTION)

## 1. Analogi Sederhana untuk Pemula
Bayangkan **Dockerfile** ini seperti **buku resep otomatis** untuk merakit komputer server baru di cloud (seperti Railway). 

Ketika komputer server itu baru dinyalakan, di dalamnya sama sekali belum ada PHP, belum ada web server Apache, belum ada Composer, dan belum ada kode aplikasi kita. Melalui instruksi di Dockerfile ini, sistem secara otomatis:
1. Mengambil sistem operasi dasar yang sudah berisi PHP 8.4 dan Apache.
2. Memasang semua alat pendukung (ekstensi database, manipulasi gambar, zip, git).
3. Mengatur agar web server Apache mengarahkan pengunjung ke folder yang aman (`/public`).
4. Mengunduh Composer dan memasang dependensi Laravel yang dibutuhkan.
5. Menyalin kode aplikasi KampusLMS ke dalam server.
6. Menjalankan migrasi database serta menyalakan web server agar aplikasi langsung online dan bisa diakses.

---

## 2. Salinan Skrip Dockerfile Lengkap

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

## 3. Bedah Alur dan Penjelasan Per Bagian

### Langkah 0: Pondasi Server (`FROM php:8.4-apache`)
* **Maksud:** Menentukan citra dasar (*base image*).
* **Fungsi:** Mengambil sistem operasi Debian Linux yang sudah memiliki web server **Apache** dan **PHP versi 8.4** siap pakai.

---

### Langkah 1: Pemasangan Library Sistem & Ekstensi PHP
* **Kode:** `RUN apt-get update && apt-get install -y ...`
* **Fungsi:**
  * Memasang paket pendukung Linux seperti `git`, `curl`, `zip`, dan `unzip`.
  * Memasang ekstensi PHP penting untuk Laravel:
    * `pdo_mysql`: Agar Laravel bisa terhubung dan berkomunikasi dengan database MySQL.
    * `mbstring` & `intl`: Untuk pemrosesan string multi-byte, karakter khusus, dan format tanggal internasional.
    * `gd` & `exif`: Untuk memproses file gambar dan foto pengguna.
    * `bcmath` & `pcntl`: Untuk perhitungan matematika presisi dan eksekusi antrean proses.
    * `zip`: Untuk membaca dan mengekstrak berkas `.zip`.
  * `apt-get clean && rm -rf /var/lib/apt/lists/*`: Membersihkan file installer sementara setelah selesai agar ukuran image Docker tetap ringan dan hemat penyimpanan.

---

### Langkah 2: Pengaturan Web Server Apache
* **Kode:** `RUN a2enmod rewrite ...`
* **Fungsi:**
  * `a2enmod rewrite`: Mengaktifkan modul URL Rewrite Apache agar sistem *routing* Laravel (`/login`, `/courses`, `/assignments`) bisa diproses dengan mulus tanpa error 404.
  * `rm -f ... mpm_event.* / mpm_worker.*` & `a2enmod mpm_prefork`: Memastikan modul pemrosesan Apache yang aktif hanyalah `mpm_prefork`. Ini penting untuk mencegah konflik crash server akibat error *"More than one MPM loaded"*.
  * `AllowOverride All`: Mengizinkan file `.htaccess` bawaan Laravel membaca dan mengatur lalu lintas URL di server.

---

### Langkah 3: Mengarahkan Document Root ke Folder `/public`
* **Kode:** `ENV APACHE_DOCUMENT_ROOT /var/www/html/public ...`
* **Fungsi:**
  * Secara default, Apache membuka folder `/var/www/html`. 
  * Pada Laravel, pintu masuk aplikasi harus selalu melalui folder `/public` (tempat file `index.php`).
  * **Aspek Keamanan:** Langkah ini sangat penting agar pengunjung website tidak bisa mengakses file sensitif seperti `.env`, file konfigurasi, maupun kode controller aplikasi secara langsung melalui browser.

---

### Langkah 4: Memasang Composer Resmi
* **Kode:** `COPY --from=composer:latest /usr/bin/composer /usr/bin/composer`
* **Fungsi:** Mengambil binary alat manajemen paket PHP (**Composer**) versi resmi terbaru dan menaruhnya ke dalam container agar perintah `composer install` dapat dijalankan.

---

### Langkah 5: Menyalin Kode Proyek ke Dalam Container
* **Kode:** `WORKDIR /var/www/html` & `COPY . .`
* **Fungsi:**
  * `WORKDIR`: Menentukan bahwa direktori kerja utama di dalam container adalah `/var/www/html`.
  * `COPY . .`: Menyalin seluruh berkas proyek KampusLMS dari repositori git ke dalam container tersebut.

---

### Langkah 6: Memasang Dependensi Composer Mode Produksi
* **Kode:** `RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs`
* **Fungsi:**
  * Mengunduh seluruh vendor library Laravel yang tercatat di `composer.lock`.
  * `--no-dev`: Tidak mengunduh paket khusus testing lokal (seperti Faker atau PHPUnit) sehingga server lebih cepat dan hemat memori.
  * `--optimize-autoloader`: Mengompilasi pemetaan file class PHP menjadi satu berkas agar performa aplikasi semakin kencang saat diakses pengguna.
  * `--ignore-platform-reqs`: Menjaga proses instalasi tetap berhasil meskipun ada sedikit perbedaan versi minor di lingkungan cloud.

---

### Langkah 7: Mengatur Hak Akses Direktori (*File Permissions*)
* **Kode:** `RUN chown -R www-data:www-data ... && chmod -R 775 ...`
* **Fungsi:**
  * Memberikan izin kepemilikan kepada user web server Linux (`www-data`).
  * Laravel memerlukan izin menulis (*write permission*) untuk menyimpan sesi login pengguna (*session*), file cache tampilan Blade, dan berkas tugas yang diunggah mahasiswa ke dalam folder `storage` dan `bootstrap/cache`. Jika langkah ini terlewat, aplikasi akan mengalami error *500 Internal Server Error (Permission Denied)*.

---

### Langkah 8: Port Jaringan & Skrip Startup Kontainer (`CMD`)
* **Kode:** `EXPOSE 80 8080` & `CMD ["sh", "-c", "..."]`
* **Fungsi Rangkaian Startup:**
  1. `EXPOSE 80 8080`: Menginformasikan bahwa container membuka pintu port jaringan 80 dan 8080.
  2. `export PORT="${PORT:-8080}" ...`: Menyesuaikan port Apache dengan port dinamis yang diberikan secara otomatis oleh platform cloud Railway.
  3. `php artisan config:clear && php artisan route:clear && php artisan view:clear`: Membersihkan cache lama agar konfigurasi environment produksi yang baru langsung aktif.
  4. `php artisan migrate --force`: Menjalankan migrasi tabel database secara otomatis saat aplikasi baru pertama kali hidup atau diperbarui.
  5. `php artisan db:seed --force`: Menjalankan seeder untuk mengisi data awal (opsional/aman jika terjadi penyesuaian).
  6. `exec apache2-foreground`: Menjalankan Apache di latar depan (*foreground*). Perintah ini menjaga agar container Docker tetap hidup dan siap melayani permintaan pengguna secara terus-menerus.

---

# BAGIAN III: PANDUAN EVALUASI CHECKPOINT DEPLOYMENT (8 PERTANYAAN PENGUJI)

Berikut adalah panduan lengkap untuk menjawab pertanyaan penguji saat sesi evaluasi lisan dan demonstrasi teknis:

---

### Checkpoint 1: Uji Akses Berkas `.env` via `curl -I`
> **Pertanyaan Penguji:**  
> *"Buka situs Anda, penguji akan menjalankan `curl -I https://domain/.env`. Harus 404 atau 403, tidak boleh 200!"*

* **Jawaban & Penjelasan Pemula:**  
  File `.env` adalah **brankas rahasia utama** aplikasi kita yang menyimpan kunci database (`DB_PASSWORD`), kunci enkripsi Laravel (`APP_KEY`), username, dan token API penting.  
  * Jika perintah `curl -I` mengembalikan status **HTTP 200 (OK)**, artinya siapapun di internet bisa mengunduh file `.env` tersebut dan langsung meretas database kita!
  * Status **HTTP 404 (Not Found)** atau **HTTP 403 (Forbidden)** membuktikan bahwa file `.env` berada di tempat yang aman (di luar Document Root web server) dan tidak bisa diakses dari browser luar.

* **Cara Peragaan di Depan Penguji:**
  1. Buka terminal (PowerShell atau Git Bash).
  2. Ketikkan perintah ini langsung ke domain produksi KampusLMS:
     ```bash
     curl -I https://kampuslms-kelompok-02.up.railway.app/.env
     ```
  3. Tunjukkan baris pertama hasil terminal ke penguji:
     ```http
     HTTP/1.1 404 Not Found
     ```
  4. Jelaskan bahwa konfigurasi Apache kita hanya mengarahkan Document Root ke folder `public/`, sehingga file `.env` yang berada di folder root `/var/www/html/` fisik server terlindungi 100%.

---

### Checkpoint 2: Bahaya dan Risiko `APP_DEBUG=true` di Server Produksi
> **Pertanyaan Penguji:**  
> *"Apa yang terjadi kalau `APP_DEBUG=true` di produksi? Peragakan di lokal, jelaskan risikonya di produksi!"*

* **Jawaban & Penjelasan Pemula:**  
  Fitur `APP_DEBUG` dirancang khusus hanya untuk masa pengembangan (di laptop programmer). Ketika ada kode yang salah atau query database gagal, Laravel menampilkan layar merah interaktif yang sangat detail (Ignition).
  * **Risiko Fatal di Produksi (*Information Disclosure*):** Jika `APP_DEBUG=true` dibiarkan aktif di server online, siapapun pengunjung yang menemukan error (atau sengaja memasukkan input aneh) akan langsung disuguhkan:
    1. Seluruh variabel environment (termasuk password database asli dan secret key).
    2. Struktur kode controller dan alur program.
    3. Struktur folder sistem operasi server.
    Informasi ini memberi modal emas bagi hacker untuk membobol server secara langsung.

* **Langkah Peragaan di Depan Penguji (di Lokal):**
  1. Buka file `.env` di VS Code, ubah `APP_DEBUG=true`.
  2. Buka browser dan buka alamat sengaja salah, misal: `http://127.0.0.1:8000/testing-error-route`.
  3. Tunjukkan ke penguji: Muncul layar merah detail Laravel yang membocorkan path folder `D:\kampuslms-kelompok-02\...` dan variabel sistem.
  4. Ubah kembali di file `.env` menjadi `APP_DEBUG=false`, simpan, lalu refresh browser.
  5. Tunjukkan ke penguji: Sekarang yang tampil hanyalah halaman putih sopan berlogo **`500 | SERVER ERROR`** atau **`404 | NOT FOUND`**, sehingga semua informasi rahasia server tertutup rapat.

---

### Checkpoint 3: Kenapa Document Root Harus Mengarah ke `public/`?
> **Pertanyaan Penguji:**  
> *"Kenapa document root harus mengarah ke `public/`? Apa yang bocor kalau tidak?"*

* **Jawaban & Penjelasan Pemula:**  
  Di dalam arsitektur Laravel, folder `public/` adalah **"ruang tamu" satu-satunya** yang boleh dimasuki oleh orang asing (berisi file pintu masuk `index.php`, css, javascript, dan foto aset).  
  Sementara itu, folder lainnya seperti `app/` (logika kode), `routes/` (rute sistem), `database/` (migrasi tabel), `storage/logs/` (rekaman log error), dan file `.env` (kredensial rahasia) diletakkan di luar folder `public/`.

* **Apa Saja yang Bocor Kalau Tidak Mengarah ke `public/`?**
  Jika Document Root dibiarkan mengarah ke root project `/var/www/html`:
  1. **File `.env` bocor:** Pengunjung tinggal mengetik `https://domain/.env` untuk membaca seluruh password database dan APP_KEY.
  2. **File log error bocor:** Pengunjung bisa mengetik `https://domain/storage/logs/laravel.log` untuk melihat rekaman error internal dan jalur direktori sistem.
  3. **File `composer.json` bocor:** Penyerang bisa melihat daftar paket vendor beserta versinya untuk mencari celah keamanan (*known vulnerabilities*).
  4. **Kode aplikasi bocor:** File kode PHP berisiko bisa diunduh langsung jika modul PHP Apache mengalami gangguan.
  
  Dengan mengarahkan Document Root ke `/var/www/html/public`, web server secara fisik tidak mengizinkan akses ke direktori induk di atasnya.

---

### Checkpoint 4: Pemeriksaan `supervisorctl status` & Risiko Worker Mati
> **Pertanyaan Penguji:**  
> *"Tunjukkan `supervisorctl status`. Apa akibatnya kalau worker mati dan tidak ada yang tahu?"*

* **Jawaban & Penjelasan Pemula:**  
  Di server Linux, pekerjaan yang berat atau butuh waktu lama (seperti mengirim ratusan email, mencetak sertifikat massal, atau memproses laporan) tidak diproses langsung saat pengguna mengklik tombol di web. Pekerjaan tersebut diserahkan ke "petugas di latar belakang" yang disebut **Queue Worker** (`php artisan queue:work`).  
  **Supervisor** adalah "satpam/pengawas" yang bertugas mengawasi worker tersebut agar tetap hidup 24 jam nonstop.

* **Perintah Pemeriksaan:**
  ```bash
  supervisorctl status
  ```
  *Contoh output yang sehat di server:*
  ```text
  kampuslms-worker:kampuslms-worker_00   RUNNING   pid 1420, uptime 3 days, 4:12:08
  ```

* **Apa Akibatnya Kalau Worker Mati dan Tidak Ada yang Tahu?**
  1. **Antrean Pekerjaan Mandek (*Job Stuck*):** Tugas-tugas yang dimasukkan ke antrean database/Redis akan tertahan selamanya dan tidak pernah selesai diproses.
  2. **Pengguna Mengeluh Sistem Rusak:** Mahasiswa tidak menerima email reset password, email pengumuman nilai tidak pernah terkirim, dan proses generate laporan gantung.
  3. **Terjadi *Silent Failure*:** Halaman web terlihat normal dan bisa dibuka, tetapi proses otomatis di balik layar berhenti total tanpa memunculkan error di layar web.  
  Oleh sebab itu, Supervisor wajib digunakan: jika worker mati mendadak karena kehabisan RAM (*memory leak*) atau error fatal, Supervisor akan **langsung menyalakannya kembali dalam hitungan detik (*auto-restart*)**.

---

### Checkpoint 5: Mengapa `queue:restart` Wajib Setelah Setiap Deployment?
> **Pertanyaan Penguji:**  
> *"Kenapa `queue:restart` wajib setelah setiap deployment?"*

* **Jawaban & Penjelasan Pemula:**  
  Proses `php artisan queue:work` adalah proses yang berjalan secara terus-menerus (*long-running process*) di dalam memori RAM server.  
  * **Karakteristik Worker:** Worker membaca file kode PHP aplikasi **hanya satu kali saat pertama kali ia dinyalakan**, kemudian seluruh kode tersebut disimpan di dalam memori RAM.
  * **Masalah Saat Deployment:** Ketika kita mengunggah kode baru (misalnya memperbaiki rumus penilaian atau mengubah format email di file Job), file di server memang sudah diperbarui. **Namun, worker yang sedang aktif di RAM masih tetap menggunakan logika kode versi lama!**
  * **Fungsi `php artisan queue:restart`:**
    * Perintah ini memberi instruksi sopan kepada worker: *"Selesaikan pekerjaan yang sedang kamu pegang saat ini, lalu segera matikan dirimu!"* (*graceful exit*).
    * Begitu worker lama mati, Supervisor akan otomatis menyalakan instance worker baru.
    * Worker baru ini akan membaca file kode PHP versi terbaru yang baru saja kita deploy.

---

### Checkpoint 6: Nasib Pemanggilan `env()` Setelah `config:cache`
> **Pertanyaan Penguji:**  
> *"Setelah `config:cache`, apa yang terjadi pada `env()` di luar `config/`? Sudahkah kode Anda bersih dari itu?"*

* **Jawaban & Penjelasan Pemula:**  
  1. **Apa yang Terjadi?**  
     Ketika perintah `php artisan config:cache` dijalankan, Laravel menyatukan seluruh file di folder `config/*.php` menjadi satu file ringkas di `bootstrap/cache/config.php`. Demi performa kecepatan tinggi, Laravel **sengaja menonaktifkan pembacaan file `.env`**.  
     Akibatnya, setiap pemanggilan fungsi `env('KEY_NAME')` di luar folder `config/` (seperti di Controller, Model, atau View Blade) akan **selalu mengembalikan nilai `null`**.
  2. **Sudahkah Kode KampusLMS Bersih dari Itu?**  
     **SUDAH BERSIH 100%!**  
     Kami telah mengaudit seluruh kode di folder `app/`, `routes/`, dan `resources/views/`. Tidak ada satupun pemanggilan `env()` secara langsung. Seluruh variabel environment diakses melalui konfigurasi resmi Laravel, misalnya menggunakan fungsi bawaan `config('app.name')`, `config('database.default')`, atau `config('mail.from.address')`.  
     *Bukti:* Saat perintah `php artisan config:cache` dieksekusi di server cloud Railway, seluruh fitur KampusLMS tetap berjalan lancar tanpa mengalami error `null`.

---

### Checkpoint 7: Ceritakan Satu Masalah di `docs/deployment.md` & Cara Menemukannya
> **Pertanyaan Penguji:**  
> *"Buka `docs/deployment.md`. Ceritakan satu masalah yang Anda temui dan bagaimana Anda menemukan penyebabnya!"*

* **Jawaban (Fokus UI/UX & Frontend - Bella Alviana):**  
  Masalah nyata yang kami temui saat pertama kali melakukan deployment ke cloud Railway tercatat di **`docs/deployment.md` Bagian 6 (Kasus 1): Masalah *Mixed Content HTTPS***.

* **Cerita Kronologi & Gejala:**  
  Ketika domain produksi [https://kampuslms-kelompok-02.up.railway.app](https://kampuslms-kelompok-02.up.railway.app) pertama kali dibuka di browser, tampilan website hancur berantakan. Tidak ada styling Tailwind CSS, tidak ada font, dan ikon tidak muncul (tampilan kembali seperti halaman teks HTML polos).

* **Bagaimana Menemukan Penyebabnya?**
  1. Saya membuka browser Google Chrome, lalu menekan tombol **F12** untuk membuka **Developer Tools / Inspect Element**.
  2. Pada tab **Console**, terlihat pesan error berwarna merah:
     ```text
     Mixed Content: The page at 'https://kampuslms-kelompok-02.up.railway.app/' was loaded over HTTPS, 
     but requested an insecure stylesheet 'http://kampuslms-kelompok-02.up.railway.app/css/style.css'. 
     This request has been blocked; the content must be served over HTTPS.
     ```
  3. **Akar Masalah:** Platform Railway memasang sertifikat SSL di pintu depan (*Reverse Proxy*), lalu meneruskan trafik ke container Apache di dalam jaringan internal via HTTP biasa. Akibatnya, fungsi `asset()` bawaan Laravel mengira aplikasi berjalan di HTTP, sehingga menghasilkan link aset berawalan `http://` yang langsung diblokir oleh browser modern demi keamanan.

* **Solusi Perbaikan:**
  1. Menambahkan konfigurasi `$middleware->trustProxies(at: '*')` pada file `bootstrap/app.php` agar Laravel mengenali header proxy `X-Forwarded-Proto`.
  2. Menambahkan baris proteksi `URL::forceScheme('https')` pada file `app/Providers/AppServiceProvider.php` khusus di lingkungan produksi, sehingga seluruh link CSS, JS, dan gambar otomatis digenerate menggunakan protokol aman HTTPS.

---

### Checkpoint 8: Bagian yang Ditulis dengan Bantuan AI & Alasan Perubahannya
> **Pertanyaan Penguji:**  
> *"Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?"*

* **Jawaban Jujur, Terbuka, & Bertanggung Jawab:**  
  Saya menggunakan bantuan AI untuk membantu menyusun kerangka awal **skrip startup (`CMD`) pada file `Dockerfile`** serta struktur awal perataan responsif formulir Dashboard Administrator.

* **Apa yang Diubah dan Kenapa?**
  1. **Pada Skrip Startup Dockerfile (`CMD`):**
     * **Rekomendasi Awal AI:** AI awalnya memberikan skrip Apache standar: `CMD ["apache2-foreground"]` dan mengasumsikan port web server selalu berada di port statis `80`.
     * **Yang Saya Ubah:** Di platform cloud PaaS seperti Railway, nomor port server **tidak pernah tetap**, melainkan diatur secara dinamis oleh sistem cloud melalui variabel `$PORT` (misalnya port 8080 atau nomor acak lainnya). Jika menggunakan kode awal AI, container akan langsung mengalami crash *Port Binding Timeout*.
     * **Alasan Perubahan:** Saya mengubah skrip startup tersebut menjadi skrip dinamis yang menggantikan port Apache saat runtime:
       ```bash
       export PORT="${PORT:-8080}" && sed -i "s/Listen .*/Listen $PORT/" /etc/apache2/ports.conf
       ```
       Serta menambahkan eksekusi otomatis `php artisan migrate --force` agar skema database langsung terbarui begitu server aktif.
  2. **Pada Tampilan Dashboard & Form (Tanggung Jawab Bella):**
     * **Rekomendasi Awal AI:** AI menyarankan menempatkan card notifikasi di samping secara terpisah dan tombol navigasi aksi di luar kontainer card form.
     * **Yang Saya Ubah:** Saya mengubah kode CSS pada `public/css/style.css` dan view Blade dengan membatasi lebar kontainer `.admin-hero-card` di 1080px, menyejajarkan 3 card statistik secara proporsional, serta memindahkan tombol "Kembali" dan "Batal" ke dalam card form di bagian kanan bawah.
     * **Alasan Perubahan:** Agar tata letak konsisten dengan seluruh halaman KampusLMS lainnya, ramah diakses lewat layar smartphone tanpa horizontal overflow, serta memenuhi panduan UI/UX praktikum.


---

