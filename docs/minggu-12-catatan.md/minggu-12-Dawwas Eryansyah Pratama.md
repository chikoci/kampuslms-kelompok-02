# Catatan Praktikum Minggu 12: Deployment & Keamanan Produksi

**Nama:** Dawwas Eryansyah Pratama  
**NIM:** 10241019  
**Kelompok:** Kelompok 02 (Kelas B / SI)  
**Tautan Live Produksi:** [https://kampuslms-kelompok-02.up.railway.app](https://kampuslms-kelompok-02.up.railway.app)  
**Fokus Kontribusi:** Konfigurasi Lingkungan Produksi, Manajemen Environment Variables di Railway, Audit Keamanan HTTP & Isolasi Berkas (`curl` checklist), serta Pengujian Otorisasi Server Live.

---

## Bagian READ: Bedah Server Produksi & Verifikasi Lingkungan

Pada tahap READ ini, saya membedah dan memverifikasi konfigurasi server produksi KampusLMS yang telah dideploy ke platform PaaS Railway menggunakan kontainer berbasis `php:8.4-apache` yang terhubung ke instance basis data terkelola MySQL 8.0.

### 1. Konfigurasi Document Root Web Server
* **Lokasi Pengaturan:** Diatur pada konfigurasi VirtualHost Apache kontainer Docker:
  ```apache
  DocumentRoot /var/www/html/public
  <Directory /var/www/html/public>
      AllowOverride All
      Require all granted
  </Directory>
  ```
* **Folder Tujuan:** Mengarah secara tepat ke folder **`/public`**, bukan ke akar proyek `/var/www/html`.
* **Analisis Keamanan:** Seluruh kode aplikasi (`app/`), template (`resources/`), penyimpanan lokal (`storage/`), dan file konfigurasi sensitif (`.env`) berada satu tingkat di atas Document Root, sehingga secara fisik tidak dapat diakses atau diunduh dari internet.

### 2. Perbandingan Lingkungan: Lokal vs Produksi (`php artisan about`)
Perbedaan konfigurasi antara laptop pengembang dan server produksi Railway:

| Parameter | Lingkungan Lokal (Dawwas) | Server Produksi (Railway Live) | Analisis Dampak |
|---|---|---|---|
| **`APP_ENV`** | `local` | `production` | Mengaktifkan optimasi internal framework Laravel. |
| **`APP_DEBUG`** | `true` | `false` | Menutup celah kebocoran stack trace dan kredensial rahasia. |
| **`APP_URL`** | `http://127.0.0.1:8000` | `https://kampuslms-kelompok-02.up.railway.app` | Memastikan URL generator menghasilkan tautan HTTPS yang valid. |
| **Asset Delivery** | Vite Dev Server (`npm run dev`) | Minified Build Assets (`npm run build`) | Aset CSS & JS statis berukuran kecil dan terkompresi. |
| **Config/Route Cache** | Not Cached (Dinonaktifkan) | Cached (`config:cache`, `route:cache`) | Mempercepat waktu bootstrapping hingga 3-5x lipat. |
| **Database Host** | `127.0.0.1` (MySQL Local) | `mysql.railway.internal` (Internal Cloud Network) | Terhubung lewat jaringan privat internal Railway yang terisolasi. |
| **Session Cookie** | `SESSION_SECURE_COOKIE=false` | `SESSION_SECURE_COOKIE=true` | Cookie autentikasi hanya boleh dikirim melalui enkripsi TLS/HTTPS. |

### 3. Pemeriksaan Proses Antrean (Queue Worker)
* **Driver Antrean:** Menggunakan `QUEUE_CONNECTION=database` pada basis data produksi.
* **Proses Latar Belakang:** Worker dijalankan menggunakan skrip startup kontainer:
  ```bash
  php artisan queue:work --sleep=3 --tries=3 --max-time=3600
  ```
* **Status:** Aktif memproses antrean notifikasi pendaftaran tugas dan pengiriman evaluasi tanpa memblokir permintaan HTTP pengguna.

### 4. Pemeriksaan Log Server (`storage/logs/laravel.log`)
* Log aplikasi dapat dibaca langsung melalui dashboard Railway Deployments Log atau melalui terminal CLI:
  ```bash
  railway logs
  ```
* Seluruh error operasional dicatat secara rapi ke berkas log harian dengan format terstruktur, tanpa pernah dimunculkan ke layar pengguna.

### 5. Pengujian Keamanan HTTP Langsung (`curl -I`)
Pengujian verifikasi keamanan dilakukan langsung dari terminal lokal terhadap domain publik:

#### A. Uji Kebocoran Berkas Kredensial `.env`
```bash
curl -I https://kampuslms-kelompok-02.up.railway.app/.env
```
* **Hasil Terminal:**
  ```http
  HTTP/1.1 404 Not Found
  Server: railway-hikari
  Date: Thu, 12 Nov 2026 05:40:12 GMT
  ```
* **Kesimpulan:** **PASS (404 Not Found)**. File `.env` terlindungi 100% dan tidak bocor ke publik.

#### B. Uji Kebocoran Catatan Error `storage/logs/laravel.log`
```bash
curl -I https://kampuslms-kelompok-02.up.railway.app/storage/logs/laravel.log
```
* **Hasil Terminal:**
  ```http
  HTTP/1.1 404 Not Found
  ```
* **Kesimpulan:** **PASS (404 Not Found)**. Berkas log internal tersembunyi dari perayap web.

#### C. Uji Kebocoran Metadata Paket `composer.json`
```bash
curl -I https://kampuslms-kelompok-02.up.railway.app/composer.json
```
* **Hasil Terminal:**
  ```http
  HTTP/1.1 404 Not Found
  ```
* **Kesimpulan:** **PASS (404 Not Found)**. Daftar versi pustaka PHP tidak dapat dibaca oleh pihak luar.

### 6. Pengujian Akses Pengguna Nyata dari Smartphone (Mobile Safari & Chrome)
Aplikasi diuji langsung dari ponsel pintar (iPhone & Android) menggunakan jaringan seluler (bukan Wi-Fi lokal):
* Seluruh halaman (Login, Dashboard Mahasiswa, Halaman Nilai, dan Portal Admin) termuat secara responsif.
* Gembok hijau HTTPS valid tanpa peringatan sertifikat keamanan (*Let's Encrypt / Railway Edge TLS Certificate*).

---

## Bagian BREAK: 8 Percobaan Eksperimen Mandiri

Eksperimen berikut dilakukan pada lingkungan terisolasi untuk membuktikan dan mengamati secara nyata bahaya kesalahan konfigurasi di server produksi:

---

### Percobaan 1: Mengaktifkan `APP_DEBUG=true` di Server Publik
* **Prediksi:** Jika terjadi error sistem, Laravel akan menampilkan halaman debug "Whoops" yang membocorkan seluruh kredensial database dan variabel rahasia ke publik.
* **Cara Uji:**
  1. Pada tab Variables Railway, ubah sementara `APP_DEBUG=true`.
  2. Buka jendela incognito, lalu akses URL yang sengaja dibuat error: `https://.../courses/999999` (id tidak ditemukan).
* **Hasil Pengamatan yang Terjadi:**
  * Muncul layar merah debug Whoops Laravel.
  * Di tab **Environment**, terlihat secara gamblang: `DB_PASSWORD`, `APP_KEY`, `DB_USERNAME`, serta struktur path direktori lengkap server.
  * **Tindakan Mitigasi Segera:** Langsung kembalikan `APP_DEBUG=false` dan lakukan rotasi kata sandi database jika sempat terpapar.

---

### Percobaan 2: Mengarahkan Document Root Web Server ke Akar Proyek (`/`)
* **Prediksi:** Jika web server tidak diarahkan ke `/public`, berkas `.env` dapat langsung diunduh siapa saja dari browser.
* **Cara Uji:**
  1. Ubah konfigurasi Document Root Apache menjadi `/var/www/html` (akar proyek).
  2. Buka terminal laptop dan jalankan: `curl -s https://domain-uji/.env`.
* **Hasil Pengamatan yang Terjadi:**
  * Terminal langsung mencetak isi teks mentah file `.env` tanpa halangan HTTP 404. Siapa pun di internet bisa menyalin kredensial database dalam hitungan detik.
  * **Tindakan Mitigasi Segera:** Wajib mengunci Document Root secara mutlak ke `/var/www/html/public`.

---

### Percobaan 3: Menjalankan `config:cache` dengan Fungsi `env()` di Luar `config/`
* **Prediksi:** Begitu `config:cache` aktif, pemanggilan `env()` di dalam controller/model akan mengembalikan `null`.
* **Cara Uji:**
  1. Tambahkan baris kode pengujian di controller: `$nama = env('APP_NAME');`.
  2. Jalankan perintah optimasi: `php artisan config:cache`.
  3. Akses halaman controller tersebut di browser.
* **Hasil Pengamatan yang Terjadi:**
  * Nilai variabel `$nama` menjadi kosong (`null`), meskipun di file `.env` nilainya berisi "KampusLMS".
  * **Penjelasan Teknis:** Saat konfigurasi di-cache, Laravel tidak lagi membaca file `.env` pada setiap request untuk menghemat I/O disk. Seluruh nilai disatukan ke dalam file cache `bootstrap/cache/config.php`. Karena itu, pemanggilan harus melalui `config('app.name')`.

---

### Percobaan 4: Menghentikan Queue Worker Saat Tugas Baru Dibuat
* **Prediksi:** Listener notifikasi tidak akan dieksekusi, pekerjaan tertahan di tabel `jobs`, dan notifikasi berhenti terkirim tanpa ada pesan error di layar.
* **Cara Uji:**
  1. Hentikan sementara worker antrean di server.
  2. Login sebagai Dosen, lalu buat satu tugas baru.
  3. Buka basis data, periksa tabel `jobs`.
* **Hasil Pengamatan yang Terjadi:**
  * Dosen berhasil membuat tugas tanpa kendala (*redirect 200 OK*).
  * Namun, di tabel `jobs` muncul baris antrean baru yang tidak kunjung berkurang (*attempts=0*).
  * Mahasiswa tidak menerima notifikasi di dashboard mereka (*silent failure*).
  * Begitu worker dinyalakan kembali, baris di tabel `jobs` langsung hilang dan notifikasi terkirim.

---

### Percobaan 5: Melakukan Deployment Kode Baru Tanpa Perintah `queue:restart`
* **Prediksi:** Worker lama yang sedang berjalan di memori RAM akan terus mengeksekusi antrean menggunakan kode lama.
* **Cara Uji:**
  1. Ubah teks pesan notifikasi di kode listener `SendAssignmentNotification.php`.
  2. Lakukan `git push` dan deploy kode baru, tetapi sengaja lewati perintah `php artisan queue:restart`.
  3. Terbitkan tugas baru.
* **Hasil Pengamatan yang Terjadi:**
  * Notifikasi yang terkirim ke mahasiswa masih menggunakan format teks pesan lama.
  * **Penjelasan Teknis:** Worker daemon PHP menahan file script di memori RAM server. Tanpa `queue:restart`, proses worker tidak pernah melakukan reload berkas PHP baru.

---

### Percobaan 6: Mengubah Izin Direktori `storage/` Menjadi Read-Only (`555`)
* **Prediksi:** Server akan melempar error fatal HTTP 500 dan bahkan tidak mampu menulis berkas log error.
* **Cara Uji:**
  1. Di terminal server, jalankan: `chmod -R 555 storage/`.
  2. Akses halaman utama aplikasi di browser.
* **Hasil Pengamatan yang Terjadi:**
  * Browser menampilkan pesan: *"The stream or file '/var/www/html/storage/logs/laravel.log' could not be opened in append mode: Failed to open stream: Permission denied"*.
  * Server lumpuh total karena proses web server `www-data` tidak memiliki izin tulis ke folder cache view, session, dan log.
  * **Solusi Standar:** Izin folder wajib dikembalikan: `chmod -R 775 storage bootstrap/cache`.

---

### Percobaan 7: Menyetel `upload_max_filesize=1M` dan Mengunggah Berkas 3 MB
* **Prediksi:** Unggahan berkas ditolak langsung di level web server PHP sebelum sempat divalidasi oleh aturan `max:5120` di Laravel.
* **Cara Uji:**
  1. Di `php.ini`, setel: `upload_max_filesize = 1M` dan `post_max_size = 2M`.
  2. Login sebagai mahasiswa, unggah berkas tugas berukuran 3 MB (aturan di Laravel memperbolehkan hingga 5 MB).
* **Hasil Pengamatan yang Terjadi:**
  * Laravel melempar exception `PostTooLargeException` sebelum form request diproses. Pesan error di browser membingungkan mahasiswa.
  * **Solusi Produksi:** Konfigurasi `upload_max_filesize` dan `post_max_size` di `php.ini` server produksi wajib disetel lebih besar atau minimal sama dengan validasi aplikasi (disetel `20M`).

---

### Percobaan 8: Mengakses Situs Melalui Protokol `http://` Setelah `SESSION_SECURE_COOKIE=true`
* **Prediksi:** Sesi login pengguna tidak akan pernah terbentuk jika diakses melalui koneksi tidak terenkripsi.
* **Cara Uji:**
  1. Pastikan `SESSION_SECURE_COOKIE=true` aktif di `.env`.
  2. Coba login melalui jalur HTTP biasa: `http://domain-uji/...`.
* **Hasil Pengamatan yang Terjadi:**
  * Pengguna memasukkan email dan password dengan benar, namun setelah menekan tombol "Masuk", sistem langsung mengarahkan kembali ke halaman login (*login loop*).
  * **Penjelasan Teknis:** Atribut `Secure` pada cookie memberitahu browser untuk menolak mengirimkan cookie session jika protokol bukan HTTPS. Ini adalah fitur keamanan penting untuk mencegah pembajakan sesi (*session hijacking*) di jaringan publik.

---

## Bagian CHECKPOINT (CP) — Gerbang Interview Milestone M4

Berikut adalah jawaban taktis dan argumentatif atas pertanyaan kunci yang diujikan dosen/penguji pada sesi interview Tugas 4:

### ❓ 1. Penguji menjalankan `curl -I https://domain/.env`. Hasilnya harus apa dan mengapa?
> **Jawaban:**  
> Hasilnya **wajib HTTP 404 Not Found**.  
> Hal ini membuktikan bahwa web server (Nginx/Apache) mengarahkan *Document Root* secara benar ke subfolder `/public`, bukan ke akar proyek. Jika mengembalikan HTTP 200, maka seluruh berkas konfigurasi rahasia (`.env`) berisi kredensial database dan enkripsi sistem dapat diunduh langsung oleh siapa saja di internet.

### ❓ 2. Apa yang terjadi jika `APP_DEBUG=true` di produksi?
> **Jawaban:**  
> Sangat berisiko tinggi. Saat terjadi error runtime atau exception yang dipicu oleh penyerang, Laravel akan menampilkan halaman debug interaktif (Whoops) yang membocorkan isi environment variable secara lengkap: password database, `APP_KEY`, token API, serta path internal direktori server. Di internet, terdapat bot otomatis yang terus-menerus memindai website publik untuk mengeksploitasi halaman debug Laravel yang terbuka.

### ❓ 3. Kenapa Document Root harus mengarah ke `public/`? Apa yang bocor kalau tidak?
> **Jawaban:**  
> Karena di dalam struktur folder Laravel, hanya folder `public/` yang dirancang untuk diakses oleh publik (berisi `index.php`, CSS, JS, dan gambar publik).  
> Jika Document Root mengarah ke akar proyek, maka berkas-berkas sensitif seperti `.env`, `composer.json`, `package.json`, berkas sumber kode di `app/`, dan catatan error di `storage/logs/laravel.log` akan terbuka dan bisa diunduh langsung melalui browser.

### ❓ 4. Apa akibatnya jika Queue Worker mati dan tidak ada yang tahu?
> **Jawaban:**  
> Terjadi kegagalan tersembunyi (*silent failure*). Aplikasi web tetap berjalan lancar dan pengguna mengira tugasnya berhasil dikirim, namun seluruh pekerjaan asinkron (seperti pengiriman email notifikasi tugas dan pemrosesan antrean) akan tertimbun selamanya di tabel `jobs` basis data tanpa pernah dieksekusi. Oleh karena itu, di server produksi wajib menggunakan process manager seperti **Supervisor** atau daemon kontainer yang otomatis menyalakan ulang worker jika prosesnya mati.

### ❓ 5. Kenapa `php artisan queue:restart` wajib dijalankan setelah setiap deployment?
> **Jawaban:**  
> Proses queue worker berjalan terus-menerus di memori RAM server sebagai daemon proses. Worker tidak membaca ulang file PHP dari disk pada setiap job. Jika kita memperbarui kode aplikasi di GitHub dan melakukan pull ke server, worker lama masih menyimpan kode versi lama di memorinya. Perintah `queue:restart` mengirimkan sinyal instruksi agar worker menyelesaikan job saat ini lalu me-restart dirinya sendiri dengan memuat kode aplikasi terbaru.

### ❓ 6. Setelah `config:cache`, apa yang terjadi pada `env()` di luar folder `config/`?
> **Jawaban:**  
> Fungsi `env()` di luar folder `config/` (seperti di controller, model, atau view) akan **mengembalikan nilai `null`**.  
> Hal ini terjadi karena saat `config:cache` dieksekusi, Laravel mengompilasi seluruh konfigurasi menjadi satu file cache statis dan berhenti memuat file `.env`. Seluruh kode di aplikasi KampusLMS kami sudah dibersihkan dan selalu menggunakan fungsi helper `config('...')`.

### ❓ 7. Ceritakan satu masalah yang ditemui saat deployment dan bagaimana menemukan penyebabnya!
> **Jawaban (Studi Kasus Deployment Railway):**  
> **Masalah:** Saat pertama kali aplikasi dideploy ke Railway, halaman login mengalami *error mixed content* dan formulir form submission tidak bisa dikirim karena tombol submit mengarah ke URL `http://` bukan `https://`.  
> **Cara Menemukan Penyebab:** Saya memeriksa tab *Console* dan *Network* di Inspect Element browser, terlihat pesan peringatan bahwa resource HTTPS memanggil endpoint HTTP.  
> **Solusi:** Di file `app/Providers/AppServiceProvider.php`, kami menambahkan konfigurasi paksa skema HTTPS saat di lingkungan produksi:
> ```php
> if (app()->isProduction()) {
>     \URL::forceScheme('https');
> }
> ```
> Selain itu, kami menyetel `TrustProxies` ke `*` agar Laravel mengenali header SSL Edge dari Railway.

### ❓ 8. Tunjukkan satu bagian yang ditulis dengan bantuan AI. Apa yang diubah dan kenapa?
> **Jawaban:**  
> **Bagian yang Dibuat dengan Bantuan AI:** Skrip pengujian otomatis keamanan HTTP (`curl`) dan skrip konfigurasi entrypoint Dockerfile.  
> **Apa yang Diubah:** AI awalnya menyarankan perintah upload file dengan permission `chmod -R 777 storage`. Saya mengubahnya menjadi `chmod -R 775 storage` dan mengubah kepemilikan user menjadi `chown -R www-data:www-data`.  
> **Alasannya:** Permission `777` adalah celah keamanan berbahaya karena memberikan izin eksekusi dan tulis kepada semua pengguna sistem (termasuk potensi proses asing di server). Memberikan permission yang pas (`775` dengan user grup web server) jauh lebih aman dan memenuhi standar keamanan produksi.

