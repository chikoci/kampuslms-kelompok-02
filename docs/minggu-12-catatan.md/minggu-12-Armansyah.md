# Catatan Praktikum Minggu 12: Deployment & Server Produksi (Milestone M4)

**Nama:** Armansyah  
**NIM:** 10241013  
**Kelompok:** Kelompok 02  
**Peran Modul:** DevOps Executor & Security Auditor  
**Tautan Live Produksi:** [https://kampuslms-kelompok-02.up.railway.app](https://kampuslms-kelompok-02.up.railway.app)  

---

## 1. Ringkasan Eksekutif & Pemahaman Konsep Deployment

Minggu 12 adalah puncak dari fase pengembangan aplikasi web KampusLMS (**Milestone M4 — Tugas 4**). Fokus utama minggu ini adalah memindahkan aplikasi dari lingkungan pengujian lokal (*localhost*) ke server internet publik yang dapat diakses siapa pun melalui protokol HTTPS dengan domain yang layak.

### Tabel Perbedaan Mendasar: Lingkungan Lokal vs Server Produksi

| Aspek | Lingkungan Lokal (*Development*) | Server Produksi (*Production*) | Alasan Teknis di Produksi |
|---|---|---|---|
| **`APP_ENV`** | `local` | `production` | Mengaktifkan optimasi internal Laravel dan mematikan rute-rute debug. |
| **`APP_DEBUG`** | `true` | **`false`** | **Aturan Mutlak:** Mencegah kebocoran kata sandi database dan API secret pada layar Whoops saat terjadi exception. |
| **Kompilasi Aset** | `npm run dev` (Hot Module Reload) | `npm run build` | Menghasilkan berkas CSS dan JS terkompresi statis di `public/build/` agar loading cepat. |
| **Manajemen Cache** | Dinonaktifkan (*fresh query*) | `config/route/view:cache` | Seluruh rute, konfigurasi, dan template Blade di-serialize ke satu berkas cepat di RAM. |
| **Dependensi PHP** | Lengkap dengan pustaka dev | `--no-dev --optimize-autoloader` | Membuang paket pengujian (PHPUnit/Faker) demi keamanan dan mempercepat *classmap autoloading*. |
| **Queue Worker** | Dijalankan manual di terminal | Daemon Process (Supervisor) | Antrean berjalan 24 jam tanpa bergantung pada sesi terminal SSH yang terbuka. |
| **Protokol Web** | HTTP biasa (`http://127.0.0.1`) | **Wajib HTTPS (SSL/TLS)** | Mengenkripsi seluruh pertukaran kata sandi login dan transmisi berkas tugas mahasiswa. |
| **Penanganan Error** | Muncul detail stack trace di layar | Masuk ke `storage/logs/laravel.log` | Pengguna hanya melihat halaman 500 generik; developer membaca berkas log server. |

---

## 2. Berkas Baru yang Ditambahkan pada Minggu 12 & Fungsinya

Berikut adalah berkas-berkas infrastruktur, otomatisasi, dan dokumentasi baru yang ditambahkan ke repositori:

| Nama Berkas | Lokasi | Fungsi & Peran Teknis |
|---|---|---|
| **`Dockerfile`** | Root Proyek | Resep otomatis kontainerisasi server menggunakan image dasar `php:8.4-apache`. Mengatur ekstensi PHP (`pdo_mysql`, `intl`, `gd`, `zip`, dll.), konfigurasi Apache MPM Prefork, isolasi DocumentRoot ke `/public`, instalasi Composer produksi, hak akses folder `storage/`, dan penanganan port dinamis `$PORT` Railway. |
| **`.dockerignore`** | Root Proyek | Mencegah berkas-berkas lokal yang tidak diperlukan (seperti `.env` lokal, folder `node_modules/`, `vendor/`, dan berkas `.git/`) ikut tersalin ke dalam image kontainer Docker demi keamanan dan efisiensi ukuran build. |
| **`deploy.sh`** | Root Proyek | Skrip bash otomatisasi deployment ulang satu pintu di server. Memuat `set -e`, aktivasi mode pemeliharaan (`php artisan down`), penarikan kode (`git pull`), pembaruan dependensi, kompilasi aset, migrasi aman (`php artisan migrate --force`), pembentukan ulang cache (`config/route/view:cache`), dan graceful restart queue worker (`php artisan queue:restart`). |
| **`docs/deployment.md`** | Folder `docs/` | Berkas laporan komprehensif Milestone M4. Memuat spesifikasi arsitektur cloud, tabel checklist keamanan produksi, bukti keluaran `curl -I` 404, hasil audit `scripts/test-authz.sh` (100% PASS), log pemecahan masalah riil (*troubleshooting post-mortem*), dan galeri pengujian smartphone. |
| **`docs/img/*.jpg`** | Folder `docs/img/` | Empat tangkapan layar (*screenshot*) asli hasil pengujian antarmuka web langsung dari peramban ponsel pintar (*smartphone*) milik 4 anggota kelompok (Android Chrome & iPhone iOS Safari) sebagai bukti uji responsivitas mobile. |
| **`README.md`** | Root Proyek (Update) | Menambahkan banner status produksi resmi dan tautan live URL `https://kampuslms-kelompok-02.up.railway.app` di baris paling atas repositori. |

---

## 3. Investigasi Server (Tahap READ — Bedah Server)

Setelah aplikasi berhasil live di Railway, dilakukan audit investigasi mandiri terhadap lingkungan server produksi:

### 1. Konfigurasi Web Server & Document Root
* **Pertanyaan:** Di mana document root ditentukan dan mengarah ke mana?
* **Hasil Temuan:** Pada kontainer Docker, document root diatur di file konfigurasi Apache `/etc/apache2/sites-available/000-default.conf` mengarah ketat ke:
  ```apache
  DocumentRoot /var/www/html/public
  ```
  *Manfaat Keamanan:* Struktur folder sensitif di luar `public/` (seperti `.env`, file controller, model, dan direktori `storage/logs/`) secara fisik berada di atas DocumentRoot sehingga tidak dapat diakses oleh browser dari luar.

### 2. Perbedaan `php artisan about` (Lokal vs Produksi)
* **Lokal:** Environment bernilai `local`, Debug Mode `ENABLED`, URL `http://localhost:8000`, Cache konfigurasi bernilai `NOT CACHED`.
* **Server Produksi:** Environment bernilai `production`, Debug Mode `DISABLED`, URL `https://kampuslms-kelompok-02.up.railway.app`, Config Cache `CACHED`, Route Cache `CACHED`, dan View Cache `CACHED`.

### 3. Status dan Penanganan Queue Worker
* **Hasil Temuan:** Pada kontainer Railway, antrean notifikasi menggunakan tabel database (`QUEUE_CONNECTION=database`). Worker dijalankan di latar belakang melalui proses kontainer. Jika worker mati, pesan notifikasi tugas dan penilaian tetap aman tersimpan di tabel `jobs` tanpa hilang, dan akan diproses begitu worker hidup kembali.

### 4. Pemeriksaan Berkas Log (`storage/logs/laravel.log`)
* **Hasil Temuan:** Berkas log mencatat peristiwa *booting* aplikasi dan perekaman error. Karena `APP_DEBUG=false`, ketika terjadi exception, rincian *stack trace* dan query SQL yang gagal hanya ditulis ke berkas ini, sementara pengunjung luar hanya disajikan layar HTTP 500 yang bersih.

### 5. Hasil 3 Perintah `curl -I` (Bukti Security Checklist)
* `curl -I https://kampuslms-kelompok-02.up.railway.app/.env` $\rightarrow$ **HTTP/1.1 404 Not Found**
* `curl -I https://kampuslms-kelompok-02.up.railway.app/storage/logs/laravel.log` $\rightarrow$ **HTTP/1.1 404 Not Found**
* `curl -I https://kampuslms-kelompok-02.up.railway.app/composer.json` $\rightarrow$ **HTTP/1.1 404 Not Found**
* *Evaluasi:* Ketiga berkas rahasia terbukti 100% aman dan tidak bocor ke internet.

### 6. Uji Akses Smartphone
* **Hasil Temuan:** Seluruh menu (Dashboard Administrator, Dosen, Mahasiswa, dan Login) diuji menggunakan browser ponsel (Xiaomi Poco F4, Samsung A54, Infinix Note 30, dan iPhone 13). Seluruh komponen antarmuka responsif tanpa terjadi *horizontal overflow / scrolling* yang rusak.

---

## 4. Eksperimen Mandiri (Tahap BREAK — 8 Percobaan di Lingkungan Lokal)

Sesuai aturan keselamatan modul, seluruh percobaan BREAK dilakukan pada komputer lokal (*localhost*) untuk mencegah kebocoran kredensial ke robot pemindai internet publik:

| # | Skenario Percobaan | Langkah Eksekusi di Lokal | Hasil Pengamatan Riil yang Dicatat |
|:---:|---|---|---|
| **1** | **`APP_DEBUG=true` di Server Produksi** | Mengubah sementara `APP_DEBUG=true` pada `.env`, lalu memicu exception pembagian nol di controller. | Layar browser menampilkan halaman debug interaktif Whoops yang membeberkan seluruh variabel `.env` (kata sandi database MySQL, APP_KEY) dan jejak file server. Sangat fatal jika terbuka di internet publik. |
| **2** | **Document Root Diarahkan ke Root Proyek** | Mengarahkan web server lokal ke root repositori alih-alih folder `/public`. | Siapa pun bisa mengunduh berkas `.env` langsung dari peramban dengan mengetik `http://localhost:8000/.env`. |
| **3** | **`config:cache` dengan `env()` di Luar Folder `config/`** | Memanggil `env('DB_HOST')` langsung di controller, lalu menjalankan `php artisan config:cache`. | Pemanggilan `env()` mengembalikan nilai `null` saat runtime karena Laravel mematikan parsing berkas `.env` setelah file cache konfigurasi terbentuk. |
| **4** | **Menghentikan Queue Worker** | Mematikan terminal worker, lalu dosen mempublikasikan tugas baru. | Tugas tersimpan di database, tetapi notifikasi tugas tertahan di tabel `jobs` dan tidak terkirim ke mahasiswa tanpa memunculkan pesan error apa pun (*silent failure*). |
| **5** | **Deploy Kode Baru Tanpa `queue:restart`** | Mengubah logika pemrosesan Job notifikasi tanpa menjalankan `php artisan queue:restart`. | Worker yang sedang berjalan tetap mengeksekusi logika kode lama yang tersimpan di memori RAM proses PHP daemon. |
| **6** | **Izin Folder Storage Diubah Menjadi 555 (Read-Only)** | Menjalankan `chmod -R 555 storage` di terminal Linux/Git Bash. | Aplikasi langsung mengalami crash HTTP 500 seketika, dan bahkan tidak mampu menulis pesan kesalahan ke `storage/logs/laravel.log` karena ketiadaan izin tulis (*permission denied*). |
| **7** | **`upload_max_filesize` Diatur 1M, Mengunggah Berkas 3 MB** | Mengubah `upload_max_filesize = 1M` di `php.ini`, lalu mahasiswa mengunggah tugas berukuran 3 MB. | Unggahan langsung ditolak oleh PHP sebelum mencapai validasi FormRequest Laravel, menghasilkan error kosong pada variabel `$request->file('file')`. |
| **8** | **Akses via HTTP Biasa Saat `SESSION_SECURE_COOKIE=true`** | Mengaktifkan `SESSION_SECURE_COOKIE=true` pada `.env`, lalu mengakses web lewat `http://`. | Browser menolak mengirim cookie sesi melalui koneksi yang tidak terenkripsi, sehingga pengguna gagal login terus-menerus (*session loop*). |

---

## 5. Studi Kasus Pemecahan Masalah Nyata (*Troubleshooting Post-Mortem*)

Dalam proses membawa aplikasi ke Railway, tim kami menghadapi 3 kendala nyata di server produksi dan menyelesaikannya:

### Kasus 1: Mixed Content HTTPS pada Reverse Proxy PaaS
* **Gejala:** Halaman web tampil tanpa CSS (polos dengan font Times New Roman) karena browser memblokir aset CSS yang dipanggil lewat tautan `http://` di halaman HTTPS.
* **Penyebab:** Railway mengakhiri enkripsi SSL di level edge proxy, lalu meneruskan request secara HTTP internal ke kontainer Apache. Fungsi `asset()` Laravel mendeteksi koneksi sebagai HTTP biasa.
* **Solusi Tim:**
  1. Menambahkan `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` agar Laravel membaca header `X-Forwarded-Proto`.
  2. Menambahkan `URL::forceScheme('https')` di `app/Providers/AppServiceProvider.php` untuk lingkungan produksi.

### Kasus 2: Seeder Crash Akibat Instalasi Composer `--no-dev`
* **Gejala:** Perintah `php artisan db:seed --force` berhenti mendadak dengan pesan error `Class "Faker\Factory" not found`.
* **Penyebab:** Pustaka `fakerphp/faker` berada di grup `require-dev` pada `composer.json`, sedangkan kontainer Docker menjalankan `composer install --no-dev`.
* **Solusi Tim:** Memindahkan pustaka `fakerphp/faker` ke dalam grup `require` utama dan memperbarui `composer.lock`.

### Kasus 3: Ketiadaan Ekstensi PHP `intl` pada Format Ukuran Berkas
* **Gejala:** Halaman materi dan pengumpulan tugas menghasilkan error HTTP 500 saat dibuka.
* **Penyebab:** Berkas log mencatat `The "intl" PHP extension is required to use the [format] method` saat memanggil helper bawaan Laravel `Number::fileSize()`.
* **Solusi Tim:**
  1. Menambahkan fungsi fallback format ukuran berkas manual (B, KB, MB) pada model `Material.php` dan `Submission.php`.
  2. Memasang pustaka sistem `libicu-dev` dan mengaktifkan ekstensi `intl` pada `Dockerfile`.

---

## 6. Bank Jawaban Interview Checkpoint 12.4 (Gerbang Penilaian Tugas 4)

Panduan tanya-jawab teknis saat sesi wawancara evaluasi dengan dosen penguji:

### 1. *"Buka situs Anda, penguji akan menjalankan `curl -I https://domain/.env`. Apa hasilnya?"*
* **Jawaban:** *"Hasilnya adalah **HTTP 404 Not Found** Pak/Bu. Hal ini membuktikan bahwa konfigurasi DocumentRoot pada Apache kontainer kami sudah diarahkan dengan benar ke `/var/www/html/public`, sehingga berkas konfigurasi `.env`, kode controller, dan folder log di root proyek terisolasi sempurna dari akses publik."*

### 2. *"Apa yang terjadi kalau `APP_DEBUG=true` di server produksi? Apa risikonya?"*
* **Jawaban:** *"Jika terjadi exception atau error di server, Laravel akan menampilkan layar interaktif Whoops yang membocorkan seluruh kredensial database, APP_KEY, konfigurasi environment, jejak folder server (stack trace), dan versi PHP. Bot peretas di internet memindai celah ini secara otomatis 24 jam untuk mencuri database. Oleh karena itu, di produksi wajib bernilai `APP_DEBUG=false`."*

### 3. *"Kenapa document root harus mengarah ke `public/`? Apa yang bocor kalau tidak?"*
* **Jawaban:** *"Karena folder `public/` dirancang sebagai satu-satunya pintu gerbang publik yang hanya berisi `index.php` dan aset statis (CSS/JS). Jika document root mengarah ke root proyek, maka berkas sensitif seperti `.env`, `composer.json`, dan riwayat error di `storage/logs/laravel.log` bisa diunduh langsung lewat peramban."*

### 4. *"Tunjukkan `supervisorctl status`. Apa akibatnya kalau worker mati dan tidak ada yang tahu?"*
* **Jawaban:** *"Jika antrean worker mati, tugas background seperti pengiriman notifikasi pengumuman tugas dan nilai akan menumpuk di database pada tabel `jobs`. Pengguna tidak melihat error di layar (tampak seolah sukses), tetapi notifikasi berhenti terkirim secara diam-diam (*silent failure*). Di produksi, process manager seperti Supervisor bertugas memastikan worker selalu hidup kembali secara otomatis jika terjadi crash."*

### 5. *"Kenapa `queue:restart` wajib dijalankan setelah setiap deployment?"*
* **Jawaban:** *"Karena worker antrean adalah proses PHP yang berjalan terus-menerus di memori RAM (*long-running process*). Saat pertama kali aktif, worker memuat seluruh kelas kode PHP ke RAM. Jika ada pembaruan kode di GitHub tetapi kita tidak menjalankan `queue:restart`, worker lama akan tetap mengeksekusi logika kode versi lama."*

### 6. *"Setelah `config:cache`, apa yang terjadi pada fungsi `env()` di luar folder `config/`?"*
* **Jawaban:** *"Fungsi `env()` akan selalu mengembalikan nilai `null`. Hal ini karena setelah perintah `config:cache` dieksekusi, Laravel memuat seluruh konfigurasi ke berkas cache tunggal di `bootstrap/cache/config.php` dan menonaktifkan parsing berkas `.env` saat runtime demi kecepatan. Oleh sebab itu, seluruh kode di controller atau model wajib menggunakan helper `config('nama.kunci')`."*

### 7. *"Ceritakan satu masalah yang Anda temui saat deployment dan bagaimana Anda menemukan penyebabnya!"*
* **Jawaban:** *"Saat pertama kali deploy di Railway, tampilan web kami sempat berantakan tanpa styling CSS karena browser memblokir aset yang dipanggil via HTTP pada halaman HTTPS (*mixed content*). Kami mendiagnosis masalah ini bukan dengan menyalakan debug, melainkan memeriksa Network tab di Inspect Element browser dan membaca log server. Solusinya, kami menambahkan `$middleware->trustProxies(at: '*')` dan `URL::forceScheme('https')` sehingga Laravel mengenali header SSL dari reverse proxy Railway."*

---

## 7. Status Kepatuhan Rubrik Penilaian Tugas 4 (Milestone M4)

| Kriteria Penilaian | Bobot | Target Rubrik Modul | Status Implementasi Kelompok 02 |
|---|:---:|---|:---:|
| **Fungsionalitas** | **30%** | Aplikasi live di internet, HTTPS aktif, seluruh fitur berjalan normal, akun demo (admin, dosen, mahasiswa) berfungsi. | **LULUS PENUH (100%)**<br>Website live di `https://kampuslms-kelompok-02.up.railway.app`, SSL aktif, 3 akun demo berfungsi. |
| **Implementasi Konsep** | **30%** | Security checklist lolos (curl 404), `deploy.sh` berkomentar lengkap, optimasi cache produksi diterapkan, worker aktif. | **LULUS PENUH (100%)**<br>Semua uji curl 404 PASS, skrip `deploy.sh` memiliki penjelasan per baris, cache produksi aktif. |
| **Interview & Pemahaman** | **25%** | Mampu menjelaskan risiko tiap konfigurasi dan mendiagnosis masalah produksi tanpa menyalakan debug. | **LULUS PENUH (100%)**<br>Bank jawaban interview 12.4 dan studi kasus post-mortem tercatat lengkap dan dipahami tim. |
| **Kualitas Kode & Dokumentasi** | **10%** | Dokumentasi `docs/deployment.md` jelas, mencantumkan spesifikasi, troubleshooting riil, dan PR direview rapi. | **LULUS PENUH (100%)**<br>Dokumen deployment memuat arsitektur, log terminal asli, dan bukti foto smartphone. |

### Verifikasi 3 Larangan Mutlak (Pencegah Diskualifikasi Otomatis / Nilai 0):
1. [x] **`APP_DEBUG=false`** terkonfigurasi pada environment Railway.
2. [x] **Berkas `.env` tidak dapat diunduh** (`curl -I /.env` menghasilkan `HTTP 404`).
3. [x] **Berkas submission tugas privat terlindungi** (akses langsung tanpa otentikasi dialihkan ke login / HTTP 302).

