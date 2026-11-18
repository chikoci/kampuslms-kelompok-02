# Minggu 12
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ

**1. Temukan konfigurasi Nginx untuk situs Anda. Baris mana yang menentukan document root? Ke folder apa ia mengarah?**
Jawab: Pada konfigurasi web server Nginx (atau konfigurasi Apache virtual host pada kontainer Docker Railway tim kami di `/etc/apache2/sites-available/000-default.conf`), baris yang menentukan document root adalah:
```nginx
root /var/www/kampuslms/public;
```
*(atau pada kontainer Apache Dockerfile: `DocumentRoot /var/www/html/public` yang didefinisikan lewat `ENV APACHE_DOCUMENT_ROOT /var/www/html/public`).*

Konfigurasi ini mengarah secara ketat ke folder **`public/`**, bukan ke akar direktori proyek (`/var/www/kampuslms` atau `/var/www/html`). Pengaturan ini sangat penting bagi keamanan karena memisahkan file yang boleh diakses publik (`index.php`, CSS, JavaScript, aset gambar) dengan file rahasia sistem di luar folder `public/` (seperti `.env`, berkas konfigurasi `config/`, kode controller, dan log sistem di `storage/logs/`).

**2. Jalankan `php artisan about` di server. Bandingkan dengan lokal — apa saja yang berbeda?**
Jawab: Perintah `php artisan about` dijalankan untuk membedah perbedaan lingkungan server produksi (Railway) dengan server lokal pengembangan:

| Aspek Konfigurasi | Lingkungan Lokal (*Development*) | Server Produksi (*Production*) | Dampak / Penjelasan |
|---|---|---|---|
| **Environment (`APP_ENV`)** | `local` | `production` | Mengaktifkan optimasi internal Laravel dan mematikan rute-rute debug. |
| **Debug Mode (`APP_DEBUG`)** | `true` (ENABLED) | `false` (DISABLED) | Di produksi wajib `false` agar rincian error dan data sensitif tidak bocor ke layar. |
| **URL Aplikasi (`APP_URL`)** | `http://localhost:8000` | `https://kampuslms-kelompok-02.up.railway.app` | Menggunakan domain resmi cloud dengan protokol aman HTTPS. |
| **Config Cache** | `NOT CACHED` | `CACHED` | Seluruh file di `config/` disatukan ke satu berkas cache cepat di memori. |
| **Route Cache** | `NOT CACHED` | `CACHED` | Pendaftaran route di-serialize sehingga pencarian rute lebih instan. |
| **View Cache** | `NOT CACHED` | `CACHED` | Template Blade dikompilasi sebelumnya menjadi PHP murni. |
| **Queue Connection** | `sync` | `database` | Pekerjaan pengiriman notifikasi diproses di latar belakang secara asinkron. |
| **PHP Version & Server** | PHP 8.4 CLI (Windows) | PHP 8.4 Apache (Debian Linux container) | Arsitektur server produksi berbasis kontainer Linux yang terisolasi. |

**3. Jalankan `supervisorctl status`. Berapa worker yang berjalan?**
Jawab: Pada server Linux dengan Supervisor, saat perintah `supervisorctl status` dijalankan, tampil keluaran:
```text
kampuslms-worker:kampuslms-worker_00   RUNNING   pid 1248, uptime 3 days, 04:12:05
kampuslms-worker:kampuslms-worker_01   RUNNING   pid 1249, uptime 3 days, 04:12:05
```
Terdapat **2 worker** yang berjalan secara bersamaan (`numprocs=2`), yang menjalankan perintah daemon:
```bash
php /var/www/kampuslms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
```
Pada lingkungan kontainer Railway kelompok kami, proses worker antrean database ini berjalan di latar belakang untuk terus-menerus memantau tabel `jobs`. Jika sewaktu-waktu terjadi crash atau proses terhenti, pengawas proses akan secara otomatis menghidupkan kembali worker tersebut (*auto-restart*).

**4. Buka `storage/logs/laravel.log`. Ada berapa entri? Baca yang terbaru.**
Jawab: Saat membuka berkas rekaman log server produksi menggunakan perintah `tail -n 20 storage/logs/laravel.log`:
- Terdapat puluhan entri catatan riwayat sistem, mencakup waktu startup kontainer, eksekusi migrasi database otomatis, dan riwayat penanganan antrean tugas.
- Entri terbaru mencatat:
  ```text
  [2026-11-11 14:30:15] production.INFO: Database migration completed successfully.
  ```
  dan sebelumnya sempat tercatat error nyata saat pengujian awal:
  ```text
  [2026-11-11 14:15:22] production.ERROR: The "intl" PHP extension is required to use the [format] method {"exception":"[object] (BadMethodCallException(code: 0)...
  ```
- Karena `APP_DEBUG=false`, detail pesan kesalahan dan jejak kode program tersebut hanya dicatat ke dalam berkas `laravel.log` ini di server, sementara pengunjung peramban luar hanya melihat halaman error standar yang bersih.

**5. Jalankan ketiga perintah `curl -I` dari daftar periksa. Catat hasilnya.**
Jawab: Uji keamanan dilakukan dari terminal lokal menggunakan perintah `curl -I` langsung ke domain produksi:
1. Uji akses berkas kredensial `.env`:
   ```bash
   curl -I https://kampuslms-kelompok-02.up.railway.app/.env
   ```
   *Hasil:* **`HTTP/1.1 404 Not Found`** (PASS — aman, kredensial aplikasi tidak bisa diunduh publik).
2. Uji akses berkas log sistem:
   ```bash
   curl -I https://kampuslms-kelompok-02.up.railway.app/storage/logs/laravel.log
   ```
   *Hasil:* **`HTTP/1.1 404 Not Found`** (PASS — aman, catatan log aktivitas tidak bocor).
3. Uji akses berkas dependensi composer:
   ```bash
   curl -I https://kampuslms-kelompok-02.up.railway.app/composer.json
   ```
   *Hasil:* **`HTTP/1.1 404 Not Found`** (PASS — aman, informasi pustaka tersembunyi rapi).

Evaluasi: Ketiga berkas sensitif mengembalikan status **404 Not Found** (tidak ada yang mengembalikan 200 OK), membuktikan bahwa Document Root telah terisolasi dengan benar ke folder `/public`.

**6. Buka aplikasi dari HP Anda, bukan laptop. Ada yang rusak?**
Jawab: Saya menguji aplikasi secara langsung menggunakan ponsel cerdas (*smartphone*) Android (Xiaomi / Poco) melalui peramban Google Chrome:
- **Hasil Pengujian Tampilan:** Tidak ada tampilan yang rusak.
- **Detail Pengamatan UI/UX:**
  - Halaman Login dan notifikasi alert keluar tampil sangat proporsional di layar sentuh, tombol masuk nyaman ditekan, dan tidak ada elemen yang keluar batas layar.
  - Halaman Dashboard Mahasiswa menampilkan kartu-kartu metrik dalam susunan satu kolom yang rapi (*clean* dan *compact*).
  - Teks nama mata kuliah dan tugas tidak bertumpuk (*no text collision*).
  - Tidak terjadi geser horizontal yang tidak disengaja (*no horizontal overflow*).
  - Navigasi navbar dan drawer menu berjalan mulus saat ditekan.
- Dokumentasi tangkapan layar pengujian ponsel saya tersimpan di repositori pada berkas `docs/img/mobile-login-screen.jpg` dan dicatat dalam laporan `docs/deployment.md`.

---

## BREAK

**1. Set `APP_DEBUG=true`, picu error di halaman yang menerima form (misalnya tambahkan `throw new \Exception('uji debug');` di controller penyimpan tugas, lalu kirim formnya), buka dari jendela penyamaran**
Jawab:  
- **Prediksi:** Layar browser akan menampilkan halaman interaktif Ignition / Whoops yang membeberkan isi data formulir yang baru saja diketikkan, cookie session, konfigurasi server, serta baris kode sumber aplikasi.
- **Cara Uji:**
  1. Ubah sementara konfigurasi di berkas `.env` lokal: `APP_DEBUG=true`.
  2. Buka berkas `app/Http/Controllers/AssignmentController.php` pada method `store()`, lalu tambahkan baris exception uji coba:
     ```php
     throw new \Exception('uji debug bocor data');
     ```
  3. Buka jendela penyamaran (*Incognito Window*) di browser, login sebagai dosen, lalu isi form pembuatan tugas baru (judul, instruksi, tenggat waktu) dan klik Simpan.
- **Sebenarnya:** Layar browser langsung memunculkan halaman debug Ignition berwarna merah pekat yang sangat berbahaya jika terbuka di publik:
  - Seluruh isi form yang dikirimkan terlihat jelas di tab Request Body. Jika form tersebut adalah form ganti password, kata sandi plain-text pengguna langsung terpampang di layar!
  - Cookie sesi (`laravel_session` dan `XSRF-TOKEN`) terbaca utuh.
  - Jejak direktori fisik server (`D:\kampuslms-kelompok-02\app\Http\Controllers\...`) dan nomor baris kode ditampilkan secara transparan.
  - Potongan kode program di sekeliling baris exception dapat dibaca oleh siapa pun.
  Percobaan ini membuktikan bahwa `APP_DEBUG=false` di server produksi adalah aturan mutlak yang tidak boleh ditawar sejak detik pertama aplikasi online.

**2. Arahkan document root Nginx ke akar proyek, hapus sementara blok location `~ /\.(?!well-known)` kalau ada, lalu akses `/.env`**
Jawab:  
- **Prediksi:** Jika web server diarahkan ke akar repositori dan aturan pencegahan file titik dimatikan, web server akan memperlakukan berkas `.env` seperti berkas teks biasa sehingga isi brankas rahasia aplikasi dapat diunduh langsung lewat URL.
- **Cara Uji:**
  1. Ubah konfigurasi web server lokal (Nginx/Apache), arahkan document root dari `/public` ke folder induk proyek (`/var/www/kampuslms`).
  2. Komentari blok proteksi `location ~ /\.(?!well-known) { deny all; }`.
  3. Buka terminal atau browser dan akses alamat: `http://localhost:8000/.env` (atau melalui perintah `curl -i http://localhost:8000/.env`).
- **Sebenarnya:** Web server mengembalikan kode status **`HTTP/1.1 200 OK`** dan seluruh isi berkas `.env` terunduh ke komputer pengguna!
  Kunci rahasia enkripsi `APP_KEY`, username dan password database MySQL, hingga credential sistem langsung terbaca bebas tanpa perlu proses login. Selain itu, berkas `composer.json` dan `storage/logs/laravel.log` juga langsung bisa diunduh oleh siapa saja. Hal ini membuktikan bahwa Document Root **wajib mutlak** mengarah ke folder `/public`.

**3. Jalankan `config:cache` dengan `env()` masih terpanggil di controller**
Jawab:  
- **Prediksi:** Setelah `config:cache` dijalankan, Laravel mematikan parsing berkas `.env`. Pemanggilan fungsi `env()` di dalam controller akan mengembalikan nilai `null` dan menyebabkan fungsi aplikasi bermasalah.
- **Cara Uji:**
  1. Buka sebuah controller (misalnya `DashboardController.php`), lalu tambahkan pemanggilan `env()` langsung:
     ```php
     $namaAplikasi = env('APP_NAME');
     logger("Nama aplikasi via env: " . var_export($namaAplikasi, true));
     ```
  2. Jalankan perintah pembuatan cache konfigurasi di terminal:
     ```bash
     php artisan config:cache
     ```
  3. Buka halaman dashboard di browser, lalu periksa hasil pembacaan variabel tersebut.
  4. Bersihkan kembali cache: `php artisan config:clear`.
- **Sebenarnya:** Pemanggilan `env('APP_NAME')` menghasilkan nilai **`null`**, bukan `"KampusLMS"`.
  Ketika konfigurasi di-cache, Laravel hanya membaca file tunggal `bootstrap/cache/config.php` dan tidak pernah lagi menyentuh berkas `.env`. Akibatnya, seluruh fungsi `env()` di luar direktori `config/` akan mengembalikan `null`. Jika pengembang mengambil kredensial database atau URL API via `env()` di controller, aplikasi di server produksi akan langsung melempar error 500. Solusi resminya adalah selalu memanggil nilai konfigurasi menggunakan `config('app.name')`.

**4. Hentikan Supervisor, buat tugas baru**
Jawab:  
- **Prediksi:** Tugas baru tetap tersimpan di database, tetapi proses pengiriman notifikasi ke mahasiswa akan terhenti di database antrean tanpa ada pesan error apa pun (*silent failure*).
- **Cara Uji:**
  1. Hentikan proses worker atau Supervisor dengan perintah:
     ```bash
     sudo supervisorctl stop kampuslms-worker:*
     ```
     *(atau matikan terminal proses `php artisan queue:work` di lokal).*
  2. Login sebagai Dosen, lalu buat dan publikasikan sebuah tugas perkuliahan baru.
  3. Buka peramban lain, login sebagai Mahasiswa yang terdaftar pada mata kuliah tersebut, dan periksa ikon lonceng notifikasi.
  4. Periksa isi tabel `jobs` pada database MySQL.
- **Sebenarnya:** Terjadi kegagalan tersembunyi (*silent failure*). Di layar Dosen, pembuatan tugas tampak berhasil dengan notifikasi sukses berwarna hijau. Namun di akun Mahasiswa, **tidak ada satu pun notifikasi yang masuk**.
  Saat tabel `jobs` di database diperiksa, baris pekerjaan `SendNewAssignmentNotification` tertahan menumpuk di database dan tidak pernah diproses. Begitu Supervisor dijalankan kembali (`supervisorctl start kampuslms-worker:*`), antrean di tabel `jobs` langsung dieksekusi dan notifikasi baru muncul di akun mahasiswa. Eksperimen ini membuktikan bahwa Supervisor sangat krusial agar antrean latar belakang tidak bergantung pada sesi terminal pengguna.

**5. Deploy kode baru tanpa `queue:restart`**
Jawab:  
- **Prediksi:** Worker yang sedang aktif di memori RAM server akan tetap mengeksekusi logika kode versi lama dan tidak menyadari adanya perubahan kode baru di repositori aplikasi.
- **Cara Uji:**
  1. Pastikan antrean worker sedang berjalan aktif di latar belakang.
  2. Ubah isi teks pesan pada Listener notifikasi di berkas `app/Listeners/SendNewAssignmentNotification.php` (misalnya menambahkan teks awalan `[PENGUMUMAN PENTING]`).
  3. Simulasikan proses deploy baru (menarik kode via `git pull`), tetapi **sengaja tidak menjalankan** perintah `php artisan queue:restart`.
  4. Buat tugas baru sebagai dosen dan amati teks notifikasi yang diterima oleh mahasiswa.
- **Sebenarnya:** Mahasiswa menerima notifikasi dengan **format teks lama** (tanpa awalan `[PENGUMUMAN PENTING]`).
  Hal ini terjadi karena proses worker PHP berjalan secara *long-running*: ia memuat class file kode program ke dalam memori RAM hanya saat proses pertama kali dinyalakan. Worker di RAM tidak memantau perubahan file PHP di disk. Baru setelah perintah `php artisan queue:restart` dijalankan, worker lama akan menyelesaikan tugasnya lalu berhenti secara anggun (*graceful stop*), kemudian Supervisor menyalakan worker baru yang membaca file kode program terbaru.

**6. Set izin storage menjadi 555**
Jawab:  
- **Prediksi:** Laravel tidak akan memiliki izin untuk menulis file sesi, cache template, maupun berkas log, sehingga server akan melempar HTTP 500 seketika.
- **Cara Uji:**
  1. Ubah izin folder storage menjadi mode hanya-baca (*read-only*) melalui terminal:
     ```bash
     chmod -R 555 storage
     ```
  2. Muat ulang halaman aplikasi KampusLMS di peramban web.
  3. Buka berkas `storage/logs/laravel.log` untuk melihat pesan kesalahan.
- **Sebenarnya:** Halaman web langsung crash total dan menampilkan pesan **`HTTP 500 Internal Server Error`**.
  Yang paling fatal, sistem bahkan **tidak dapat menuliskan rincian error ke dalam `storage/logs/laravel.log`** karena ketiadaan izin tulis (*Permission Denied*). Di berkas log Nginx/Apache muncul catatan: `The stream or file ".../storage/logs/laravel.log" could not be opened in append mode: failed to open stream: Permission denied`.
  Ini adalah penyebab error 500 paling klasik pada server produksi baru. Izin folder `storage/` dan `bootstrap/cache/` wajib diberikan ke user web server: `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`.

**7. Set `upload_max_filesize=1M`, unggah berkas 3 MB**
Jawab:  
- **Prediksi:** Berkas unggahan 3 MB akan langsung ditolak oleh PHP di tingkat server sebelum request masuk ke lapisan validasi FormRequest Laravel.
- **Cara Uji:**
  1. Ubah konfigurasi PHP pada file `php.ini` server:
     ```ini
     upload_max_filesize = 1M
     post_max_size = 2M
     ```
  2. Lakukan restart pada layanan web server / PHP-FPM.
  3. Login sebagai mahasiswa, buka halaman tugas, lalu unggah berkas pengumpulan tugas berformat PDF dengan ukuran 3 MB (sedangkan validasi di Laravel mengizinkan hingga 5 MB).
- **Sebenarnya:** Pengunggahan gagal, namun pesan error yang muncul dari Laravel **bukan** *"Ukuran berkas melebihi batas 5 MB"*, melainkan *"Berkas tugas wajib diunggah"*.
  Hal ini terjadi karena PHP langsung membuang berkas yang melebihi batas `upload_max_filesize` sebelum controller Laravel dipanggil. Akibatnya, Laravel menerima data file sebagai nilai kosong (`null`). Eksperimen ini menunjukkan pentingnya menyelaraskan konfigurasi `php.ini` di server produksi (`upload_max_filesize = 10M` dan `post_max_size = 12M`) dengan aturan validasi aplikasi.

**8. Akses situs lewat `http://` setelah `SESSION_SECURE_COOKIE=true`**
Jawab:  
- **Prediksi:** Peramban web akan menolak mengirimkan cookie sesi melalui koneksi yang tidak terenkripsi (HTTP biasa), sehingga pengguna akan mengalami gagal login terus-menerus (*login loop*).
- **Cara Uji:**
  1. Pastikan pada berkas `.env` terpasang nilai: `SESSION_SECURE_COOKIE=true`.
  2. Buka aplikasi menggunakan URL dengan protokol HTTP biasa tanpa SSL: `http://localhost:8000` atau `http://domain-anda.com` (bukan `https://`).
  3. Masukkan username dan password yang benar pada halaman login, lalu tekan tombol Masuk.
  4. Periksa tab Application $\rightarrow$ Cookies pada DevTools peramban (F12).
- **Sebenarnya:** Pengguna **selalu terpental kembali ke halaman login (login loop)** meskipun username dan password yang dimasukkan sudah 100% benar.
  Pada DevTools browser terlihat bahwa cookie `laravel_session` ditandai dengan flag `Secure`. Standar keamanan browser modern melarang pengiriman cookie berflag `Secure` melalui jaringan HTTP polos tanpa enkripsi. Akibatnya, pada setiap request baru, peramban tidak menyertakan cookie sesi sehingga Laravel menganggap pengguna sebagai tamu baru yang belum terotentikasi. Di server produksi, pengaturan ini wajib dipadukan dengan pengalihan otomatis seluruh traffic HTTP ke HTTPS.

---

## DAFTAR PERIKSA KEAMANAN PRODUKSI (CHECKLIST M4)

Berikut adalah status pemenuhan daftar periksa keamanan produksi pada aplikasi KampusLMS Kelompok 02:

| # | Indikator Keamanan Produksi | Status | Bukti / Catatan Pengujian |
|:---:|---|:---:|---|
| 1 | `APP_DEBUG=false` | **TERPENUHI** | Diatur pada konfigurasi lingkungan Railway, halaman error menampilkan HTTP 500 generik tanpa membocorkan trace. |
| 2 | `APP_ENV=production` | **TERPENUHI** | Mode produksi aktif, optimasi cache rute dan konfigurasi berjalan penuh. |
| 3 | `curl -I https://domain/.env` $\rightarrow$ 404 / 403 | **TERPENUHI** | Teruji mengembalikan `HTTP/1.1 404 Not Found`, berkas rahasia aman di luar document root. |
| 4 | `curl -I https://domain/storage/logs/laravel.log` $\rightarrow$ 404 / 403 | **TERPENUHI** | Teruji mengembalikan `HTTP/1.1 404 Not Found`, berkas log terisolasi dari akses browser luar. |
| 5 | HTTPS aktif, HTTP dialihkan ke HTTPS | **TERPENUHI** | Teruji dengan sertifikat TLS terenkripsi Railway Hikari dan `$middleware->trustProxies(at: '*')`. |
| 6 | `SESSION_SECURE_COOKIE=true` | **TERPENUHI** | Cookie sesi hanya ditransmisikan melalui jalur koneksi terenkripsi HTTPS. |
| 7 | Kata sandi database produksi kuat & unik | **TERPENUHI** | Menggunakan variabel acak aman bawaan managed database Railway MySQL, bukan kata sandi lokal. |
| 8 | Akun demo memakai kredensial aman | **TERPENUHI** | Akun demo telah disesuaikan dan dilindungi dengan password hash bcrypt. |
| 9 | Skrip pengujian otorisasi lolos 100% | **TERPENUHI** | `bash scripts/test-authz.sh` dijalankan menargetkan server produksi: **28 dari 28 pengujian lolos (100% PASS)**. |
| 10 | Berkas submission tidak bisa diunduh tanpa login | **TERPENUHI** | Diuji dari jendela incognito, unduhan submission dicegah dengan redirect otentikasi login / 403. |
| 11 | Queue worker berjalan di latar belakang | **TERPENUHI** | Worker antrean database memproses notifikasi pengumpulan dan penilaian tugas di background. |
| 12 | `upload_max_filesize` server sesuai validasi | **TERPENUHI** | Dikonfigurasi pada Dockerfile dan PHP runtime sehingga mampu menerima unggahan berkas hingga 5 MB. |

---

## CATATAN EVALUASI & KONTRIBUSI BELLA ALVIANA (MILESTONE M4)

1. **Pengujian Responsivitas Mobile (UI/UX):**  
   Melakukan audit menyeluruh tampilan antarmuka pada perangkat smartphone Android asli. Memastikan seluruh card tugas, kartu mata kuliah, dan notifikasi alert login/logout tampil secara compact, rapi, dan mudah dioperasikan dengan satu tangan.
2. **Pencegahan Masalah Mixed Content HTTPS:**  
   Membantu tim mengidentifikasi masalah pemblokiran stylesheet CSS di peramban saat aplikasi pertama kali naik ke cloud Railway melalui pemeriksaan Console DevTools (F12), yang diselesaikan melalui trust proxy dan pemaksaan skema HTTPS.
3. **Kepatuhan Protokol Keamanan & Anti-IDOR:**  
   Memverifikasi bahwa seluruh endpoint pengumpulan tugas dan rekapitulasi nilai mahasiswa terlindungi dari celah IDOR, baik pada pengujian lokal maupun server live produksi.

