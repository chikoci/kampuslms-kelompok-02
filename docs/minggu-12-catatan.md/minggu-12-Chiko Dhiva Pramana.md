# Catatan Praktikum Minggu 12: Deployment & Server Produksi (Milestone M4)

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Investigasi Server Produksi & Analisis Arsitektur

Pada tahap READ ini, saya melakukan investigasi langsung terhadap server produksi aplikasi KampusLMS yang sudah berhasil live di platform cloud PaaS (Railway) pada alamat [https://kampuslms-kelompok-02.up.railway.app](https://kampuslms-kelompok-02.up.railway.app) untuk memverifikasi isolasi direktori, konfigurasi keamanan, dan lingkungan eksekusi produksi.

### 1. Konfigurasi Web Server & DocumentRoot
* Pada server produksi berbasis Docker Apache (`php:8.4-apache`), DocumentRoot dikonfigurasi pada file `/etc/apache2/sites-available/000-default.conf`:
  ```apache
  DocumentRoot /var/www/html/public
  ```
* **Hasil Analisis Keamanan:** Seluruh berkas di luar folder `public/` (seperti `.env`, direktori `app/`, `config/`, dan `storage/logs/`) secara fisik berada satu tingkat di atas DocumentRoot, sehingga tidak dapat diakses atau diunduh oleh peramban luar.

### 2. Perbedaan Status Aplikasi (`php artisan about`) Lokal vs Produksi

| Parameter | Lingkungan Lokal (*Development*) | Server Produksi (*Railway Cloud*) |
|---|---|---|
| **Environment (`APP_ENV`)** | `local` | `production` |
| **Debug Mode (`APP_DEBUG`)** | `true` (aktif) | `false` (wajib nonaktif) |
| **URL Aplikasi (`APP_URL`)** | `http://127.0.0.1:8000` | `https://kampuslms-kelompok-02.up.railway.app` |
| **Protokol Web** | HTTP biasa | HTTPS penuh (SSL/TLS terenkripsi) |
| **Configuration Cache** | `NOT CACHED` | `CACHED` (`bootstrap/cache/config.php`) |
| **Route Cache** | `NOT CACHED` | `CACHED` (`bootstrap/cache/routes-v7.php`) |
| **View Cache** | `NOT CACHED` | `CACHED` (Blade terkompilasi) |

### 3. Status dan Penanganan Queue Worker
* Antrean tugas dan notifikasi diatur menggunakan driver database (`QUEUE_CONNECTION=database`).
* Pada lingkungan kontainer Railway, worker berjalan sebagai proses latar belakang yang diawasi oleh *Container Process Orchestrator*. Jika kontainer mengalami restart, seluruh job notifikasi yang belum sempat dikirim tetap tersimpan aman di tabel `jobs` tanpa hilang.

### 4. Pemeriksaan Berkas Log Server (`storage/logs/laravel.log`)
* Seluruh log aplikasi tersimpan secara privat di `storage/logs/laravel.log`.
* Karena `APP_DEBUG=false`, jika terjadi error 500, detail *stack trace* dan query SQL yang gagal hanya dicatat ke berkas ini, sedangkan pengunjung luar hanya disajikan layar kesalahan generik yang bersih tanpa kebocoran informasi teknis.

### 5. Hasil Uji Keamanan Tiga Perintah `curl -I` (Security Checklist)
Pemeriksaan dilakukan dari terminal Git Bash ke domain live produksi:
* `curl -I https://kampuslms-kelompok-02.up.railway.app/.env` $\rightarrow$ **HTTP/1.1 404 Not Found**
* `curl -I https://kampuslms-kelompok-02.up.railway.app/storage/logs/laravel.log` $\rightarrow$ **HTTP/1.1 404 Not Found**
* `curl -I https://kampuslms-kelompok-02.up.railway.app/composer.json` $\rightarrow$ **HTTP/1.1 404 Not Found**
* **Hasil:** Ketiga berkas rahasia terbukti 100% aman dan tidak bocor ke publik.

### 6. Pengujian Tampilan Smartphone Fisik (Mobile Responsiveness)
* Pengujian antarmuka dilakukan langsung menggunakan browser pada smartphone fisik anggota tim:
  - Poco F4 (Android Chrome): Dashboard Mahasiswa & detail mata kuliah.
  - Samsung A54 (Android Chrome): Dashboard Administrator & manajemen civitas.
  - Infinix Note 30 (Android Chrome): Halaman Login & notifikasi sukses logout.
  - iPhone 13 (iOS Safari): Dashboard Dosen & daftar mata kuliah yang diampu.
* **Hasil:** Seluruh layout responsif proporsional tanpa cacat overflow horizontal (*no horizontal scrolling*).

---

## Bagian BREAK: Eksperimen Mandiri (8 Percobaan)

Seluruh eksperimen BREAK dijalankan di komputer lokal (*localhost*) untuk menganalisis risiko kegagalan server produksi secara aman:

---

### 1. APP_DEBUG=true di Lingkungan Produksi
- **Prediksi:** Jika `APP_DEBUG=true` saat aplikasi berada di produksi, exception error akan menampilkan layar debug interaktif (Whoops) yang membocorkan seluruh isi kredensial environment server ke browser.
- **Cara Uji:**
  1. Buka `.env` di lokal, ubah sementara menjadi `APP_ENV=production` dan `APP_DEBUG=true`.
  2. Buka `app/Http/Controllers/DashboardController.php`, tambahkan pemicu exception di baris pertama method:
     ```php
     throw new \Exception("Uji Kebocoran Debug");
     ```
  3. Buka browser dan refresh dashboard.
- **Sebenarnya:**
  * Layar browser menampilkan antarmuka debugger Whoops secara lengkap: membeberkan variabel environment, password database MySQL, nilai `APP_KEY`, dan jejak folder server (*stack trace*).
  * **Pelajaran penting:** `APP_DEBUG` wajib bernilai `false` di server produksi tanpa pengecualian untuk mencegah kebocoran kredensial ke bot pemindai internet.

---

### 2. DocumentRoot Diarahkan ke Root Proyek
- **Prediksi:** Jika web server diarahkan ke root repositori alih-alih folder `/public`, berkas `.env` dan kode aplikasi dapat diunduh langsung lewat browser.
- **Cara Uji:**
  1. Jalankan PHP built-in server langsung dari root proyek:
     ```bash
     php -S 127.0.0.1:8001
     ```
  2. Buka URL: `http://127.0.0.1:8001/.env` pada browser.
- **Sebenarnya:**
  * Browser langsung mengunduh atau menampilkan seluruh teks berkas `.env` tanpa halangan autentikasi apa pun.
  * **Pelajaran penting:** DocumentRoot Apache/Nginx wajib mengarah ketat ke `/public` agar berkas sensitif di luar folder tersebut terisolasi dari jangkauan URL publik.

---

### 3. config:cache dengan Pemanggilan env() di Luar Folder config/
- **Prediksi:** Setelah perintah `config:cache` dieksekusi, pemanggilan `env()` di Controller atau View akan mengembalikan nilai `null`.
- **Cara Uji:**
  1. Buka `app/Http/Controllers/DashboardController.php`, tambahkan uji coba di dalam method:
     ```php
     \Log::info('Uji env langsung: ' . env('APP_NAME'));
     \Log::info('Uji via config helper: ' . config('app.name'));
     ```
  2. Jalankan perintah cache konfigurasi:
     ```bash
     php artisan config:cache
     ```
  3. Buka halaman dashboard di browser, lalu periksa `storage/logs/laravel.log`.
- **Sebenarnya:**
  * Di dalam file log: `Uji env langsung:` bernilai kosong (`null`), sedangkan `Uji via config helper:` sukses mencetak `KampusLMS`.
  * **Pelajaran penting:** Setelah caching konfigurasi, Laravel mematikan parsing berkas `.env`. Seluruh kode di luar folder `config/` wajib memakai helper `config('key')`.

---

### 4. Menghentikan Queue Worker Saat Pengiriman Notifikasi
- **Prediksi:** Jika antrean diatur ke database (`QUEUE_CONNECTION=database`) tetapi worker mati, notifikasi tidak akan terkirim ke mahasiswa tanpa memunculkan pesan error di layar dosen (*silent failure*).
- **Cara Uji:**
  1. Pastikan tidak ada worker yang berjalan di terminal (`php artisan queue:work` dimatikan).
  2. Login sebagai Dosen, lalu terbitkan tugas baru pada mata kuliah Pemrograman Web.
  3. Periksa tabel `jobs` di database dan buka lonceng notifikasi mahasiswa di browser.
- **Sebenarnya:**
  * Dosen melihat notifikasi *"Tugas berhasil diterbitkan"* di web. Namun di database, job antrean menumpuk di tabel `jobs`, dan ikon lonceng notifikasi pada akun mahasiswa tetap kosong.
  * Begitu perintah `php artisan queue:work` dijalankan di terminal, job langsung diproses dan lonceng notifikasi mahasiswa baru bertambah.
  * **Pelajaran penting:** Di server produksi, proses worker wajib diawasi oleh process manager seperti Supervisor agar selalu menyala 24/7.

---

### 5. Deploy Kode Baru Tanpa Menjalankan queue:restart
- **Prediksi:** Karena worker berjalan terus menerus di memori RAM, pembaruan kode baru tidak akan dieksekusi oleh worker sebelum worker tersebut di-restart.
- **Cara Uji:**
  1. Jalankan queue worker di satu terminal: `php artisan queue:work`.
  2. Buka class listener pengiriman notifikasi, ubah format pesan yang dicatat pada log.
  3. Picu pengiriman tugas baru tanpa mematikan terminal worker.
- **Sebenarnya:**
  * Worker yang sedang aktif tetap mencetak pesan log format lama yang tersimpan di memori RAM saat worker pertama kali dinyalakan.
  * Begitu perintah `php artisan queue:restart` dijalankan, worker lama berhenti dengan aman (*gracefully*), dan proses baru memuat kode versi terkini.
  * **Pelajaran penting:** Perintah `php artisan queue:restart` adalah kewajiban mutlak pada setiap skrip deployment (`deploy.sh`).

---

### 6. Izin Folder storage Diubah Menjadi Read-Only (555)
- **Prediksi:** Laravel membutuhkan hak tulis pada folder `storage/` untuk session, cache, dan file log. Jika dijadikan read-only, aplikasi akan langsung crash.
- **Cara Uji:**
  1. Di terminal Linux/Git Bash, ubah permission storage:
     ```bash
     chmod -R 555 storage
     ```
  2. Buka browser dan refresh halaman aplikasi.
- **Sebenarnya:**
  * Aplikasi langsung menampilkan HTTP 500 fatal: `The stream or file ".../storage/logs/laravel.log" could not be opened in append mode: Failed to open stream: Permission denied`.
  * Aplikasi bahkan tidak mampu mencatat pesan error ke file log karena tidak memiliki izin tulis.
  * **Pelajaran penting:** Web server user (`www-data`) wajib memiliki hak akses baca-tulis penuh (`chmod -R 775 storage bootstrap/cache`) di server produksi.

---

### 7. upload_max_filesize Diatur 1M, Mengunggah Berkas 3 MB
- **Prediksi:** Jika ukuran berkas melebihi batasan konfigurasi PHP runtime, unggahan akan ditolak langsung di level web server sebelum mencapai controller Laravel.
- **Cara Uji:**
  1. Ubah konfigurasi `upload_max_filesize = 1M` dan `post_max_size = 2M` pada file `php.ini`.
  2. Restart server PHP, lalu coba mengunggah materi perkuliahan berukuran 3 MB melalui form dosen.
- **Sebenarnya:**
  * Unggahan langsung gagal. Di controller, `$request->file('file')` bernilai `null` tanpa memicu pesan validasi Laravel yang semestinya.
  * **Pelajaran penting:** Nilai `upload_max_filesize` dan `post_max_size` pada PHP server produksi harus disesuaikan minimal setara atau lebih besar dari aturan validasi FormRequest (misal 10 MB atau 20 MB).

---

### 8. Akses via HTTP Biasa Saat SESSION_SECURE_COOKIE=true
- **Prediksi:** Browser akan menolak mengirim cookie sesi jika koneksi tidak menggunakan HTTPS, menyebabkan pengguna gagal login terus menerus (*session loop*).
- **Cara Uji:**
  1. Atur `SESSION_SECURE_COOKIE=true` pada `.env`.
  2. Akses aplikasi melalui protokol biasa `http://localhost:8000/login`.
  3. Masukkan email dan password yang benar, lalu klik Masuk.
- **Sebenarnya:**
  * Halaman langsung dialihkan kembali ke form login tanpa masuk ke dashboard. Browser mengabaikan cookie `Set-Cookie` karena tidak ada enkripsi TLS.
  * **Pelajaran penting:** `SESSION_SECURE_COOKIE=true` hanya boleh diaktifkan jika aplikasi sudah terpasang sertifikat SSL/HTTPS yang valid.

---

## Bagian FIX: Studi Kasus Troubleshooting Riil (Post-Mortem)

Dalam proses membawa aplikasi KampusLMS ke server produksi Railway, kami mendiagnosis dan memecahkan 3 masalah riil tanpa menyalakan debug mode:

### 1. Masalah Mixed Content HTTPS pada Reverse Proxy Railway
* **Gejala:** Tampilan website di Railway sempat tampil polos tanpa styling CSS (font Times New Roman) karena browser memblokir aset yang dipanggil via `http://` di halaman `https://`.
* **Penyebab:** Railway mengakhiri enkripsi SSL di level edge proxy, lalu meneruskan request secara HTTP internal ke kontainer Apache. Fungsi `asset()` Laravel mendeteksi koneksi sebagai HTTP biasa.
* **Langkah Perbaikan:**
  1. Menambahkan konfigurasi `$middleware->trustProxies(at: '*')` di berkas `bootstrap/app.php` baris 15-18 agar Laravel membaca header `X-Forwarded-Proto`.
  2. Menambahkan `URL::forceScheme('https')` pada method `boot()` di berkas `app/Providers/AppServiceProvider.php` untuk memaksakan skema URL aman di lingkungan produksi.

### 2. Gagal Eksekusi Seeder Akibat Dependensi Faker di Build Kontainer
* **Gejala:** Perintah migrasi otomatis kontainer `php artisan db:seed --force` berhenti dengan error fatal `Class "Faker\Factory" not found`.
* **Penyebab:** Paket `fakerphp/faker` berada di grup `require-dev` pada `composer.json`, sedangkan perintah build Docker menjalankan `composer install --no-dev`.
* **Langkah Perbaikan:**
  1. Memindahkan paket `fakerphp/faker` dari grup `require-dev` ke grup `require` utama pada `composer.json`.
  2. Memperbarui berkas `composer.lock` menggunakan perintah `composer update --lock` agar hash integritas lockfile selaras di pipeline CI dan build kontainer.

### 3. Error HTTP 500 Ketiadaan Ekstensi PHP intl pada Format Ukuran Berkas
* **Gejala:** Halaman detail mata kuliah (`/courses/1`) dan pengumpulan tugas (`/submissions/1`) menghasilkan error HTTP 500 saat dibuka.
* **Penyebab:** Berkas log mencatat exception `The "intl" PHP extension is required to use the [format] method` yang dipicu oleh helper bawaan Laravel `Number::fileSize()` di model `Material.php` dan `Submission.php`.
* **Langkah Perbaikan:**
  1. Menambahkan fungsi fallback matematika byte PHP murni (`formatBytesManual`) pada model `Material.php` dan `Submission.php` sehingga sistem tidak akan pernah crash meskipun ekstensi `intl` tidak tersedia.
  2. Memasang pustaka sistem `libicu-dev` dan menambahkan perintah `docker-php-ext-install intl` pada berkas `Dockerfile` agar kontainer memiliki dukungan ekstensi internasionalisasi penuh.

---

## Bagian BUILD: Laporan Implementasi Deployment & Security Checklist

Seluruh bukti implementasi deployment produksi dicatat pada repositori:
* **Tautan Produksi:** [https://kampuslms-kelompok-02.up.railway.app](https://kampuslms-kelompok-02.up.railway.app)
* **Dokumentasi Lengkap:** [docs/deployment.md](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/docs/deployment.md)
* **Skrip Otomatisasi Deploy:** [deploy.sh](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/deploy.sh)
* **Konfigurasi Kontainer:** [Dockerfile](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/Dockerfile) dan `.dockerignore`

### Rangkuman Hasil Pengujian Otorisasi Otomatis (`scripts/test-authz.sh`)
Skrip pengujian otorisasi dijalankan langsung menembak server produksi:
```bash
./scripts/test-authz.sh https://kampuslms-kelompok-02.up.railway.app
```
* **Hasil:** **Total: 28 uji skenario, Lolos: 28, Gagal: 0 (100% SUKSES)**.
* Membuktikan bahwa seluruh hak akses multi-peran (Admin, Dosen, Mahasiswa) dan proteksi IDOR bekerja identik dengan pengujian di lingkungan lokal.

---

## Bagian CHECKPOINT: Gerbang Interview Materi 12 (12.4)

Panduan jawaban dan langkah peragaan saat sesi evaluasi wawancara bersama dosen penguji:

---

### 1. Buka situs Anda, penguji akan menjalankan `curl -I https://domain/.env`. Apa hasilnya?
* **Jawabannya:**  
  Hasilnya adalah **HTTP/1.1 404 Not Found**. Berkas konfigurasi `.env` terbukti aman dan tidak dapat diunduh dari publik.
* **Langkah Peragaan ke Dosen:**  
  Buka terminal Git Bash, ketik perintah:
  ```bash
  curl -I https://kampuslms-kelompok-02.up.railway.app/.env
  ```
  Tunjukkan baris status header pertama yang mengembalikan `HTTP/1.1 404 Not Found`.
* **File & Baris Kode Terkait:**  
  Berkas `Dockerfile` baris 26 mengonfigurasi DocumentRoot Apache mengarah ke `/var/www/html/public`. Karena berkas `.env` berada di root proyek di luar direktori `public/`, web server Apache menolak memetakan URL tersebut dan mengembalikan status 404.

---

### 2. Apa yang terjadi kalau `APP_DEBUG=true` di server produksi? Apa risikonya?
* **Jawabannya:**  
  Jika terjadi exception error, layar interaktif debugger Whoops/Ignition akan membeberkan baris kode program, struktur tabel database, query SQL, variabel environment, nilai `APP_KEY`, dan password database ke publik. Peretas menggunakan informasi bocor ini untuk membobol sistem. Di produksi, nilainya wajib `false` agar pengguna umum hanya melihat tampilan error 500 generik.
* **Langkah Peragaan ke Dosen:**  
  Tunjukkan dashboard variabel Railway pada service `kampuslms-kelompok-02`, tunjukkan variabel `APP_DEBUG` bernilai `false`.

---

### 3. Kenapa DocumentRoot harus mengarah ke `public/`? Apa yang bocor kalau tidak?
* **Jawabannya:**  
  Folder `public/` adalah satu-satunya pintu gerbang publik yang hanya berisi `index.php` dan aset statis terkompilasi (CSS/JS). Jika DocumentRoot diarahkan ke root proyek, siapa pun dapat mengunduh berkas rahasia `.env`, berkas dependensi `composer.json`, dan file log server di `storage/logs/laravel.log` langsung lewat URL browser.
* **File & Baris Kode Terkait:**  
  Berkas `Dockerfile` dan konfigurasi Apache `000-default.conf` menetapkan: `DocumentRoot /var/www/html/public`.

---

### 4. Tunjukkan `supervisorctl status`. Apa akibatnya kalau worker mati dan tidak ada yang tahu?
* **Jawabannya:**  
  Jika antrean worker mati, tugas latar belakang seperti pengiriman notifikasi pengumuman tugas dan nilai ujian akan menumpuk di database pada tabel `jobs`. Pengguna tampak melihat operasi berhasil di browser, tetapi notifikasi tidak pernah sampai ke mahasiswa (*silent failure*). Supervisor bertugas memantau worker secara terus menerus dan me-restart worker secara otomatis jika terjadi crash.
* **Langkah Peragaan ke Dosen:**  
  - Pada VPS Ubuntu: peragakan via perintah `sudo supervisorctl status`.
  - Pada PaaS Railway: tunjukkan status kontainer **Active** dan panel **Deploy Logs** yang menunjukkan proses kontainer dipantau secara otomatis oleh container orchestrator Railway. Tunjukkan baris `php artisan queue:restart` pada berkas `deploy.sh`.

---

### 5. Kenapa `queue:restart` wajib dijalankan setelah setiap deployment?
* **Jawabannya:**  
  Karena worker antrean adalah proses jangka panjang (*long-running process*) yang berjalan di memori RAM. Saat pertama kali dihidupkan, worker memuat seluruh kelas kode PHP ke RAM. Jika kita memperbarui kode di server tanpa menjalankan `queue:restart`, worker lama akan terus mengeksekusi logika kode versi lama yang tersimpan di RAM.
* **File & Baris Kode Terkait:**  
  Berkas [deploy.sh](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/deploy.sh) baris 43:
  ```bash
  php artisan queue:restart
  ```

---

### 6. Setelah `config:cache`, apa yang terjadi pada fungsi `env()` di luar folder `config/`?
* **Jawabannya:**  
  Fungsi `env()` akan selalu mengembalikan nilai `null`. Hal ini karena setelah `config:cache` dijalankan, Laravel memuat seluruh konfigurasi ke berkas cache tunggal di `bootstrap/cache/config.php` dan menonaktifkan parsing berkas `.env` saat runtime demi performa. Oleh sebab itu, seluruh kode di controller atau model wajib menggunakan helper `config('nama.kunci')`.
* **File & Baris Kode Terkait:**  
  Contoh di seluruh controller KampusLMS selalu menggunakan `config('app.name')` atau `config('session.lifetime')`, bukan `env()`.

---

### 7. Ceritakan satu masalah yang ditemui saat deployment dan bagaimana tim melacaknya tanpa menyalakan debug!
* **Jawabannya:**  
  Saat pertama kali live di Railway, web sempat tampil polos tanpa styling CSS karena browser memblokir pemanggilan aset HTTP pada halaman HTTPS (*mixed content*). Kami tidak menyalakan `APP_DEBUG=true`, melainkan memeriksa tab Network pada peramban (Inspect Element) dan menemukan bahwa header `X-Forwarded-Proto` dari reverse proxy Railway belum dikenali oleh Laravel.
* **Langkah Penyelesaian:**  
  Kami menambahkan `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` dan `URL::forceScheme('https')` di `AppServiceProvider.php` sehingga seluruh tautan aset otomatis digenerate menggunakan skema HTTPS yang aman.
