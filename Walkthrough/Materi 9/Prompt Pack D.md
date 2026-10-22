# Walkthrough Materi 9 - Prompt Pack D: Arsitektur & Troubleshooting Unggah Berkas

Dokumen ini menjelaskan alur siklus hidup pengunggahan berkas secara menyeluruh (dari browser hingga ke harddisk fisik), serta analisis pengaturan sistem yang dapat menggagalkan unggahan sebelum berkas sampai ke framework Laravel.

---

## 1. Studi Kasus Gejala Masalah
* **Kasus**: Berkas PDF berukuran 3 MB gagal diunggah, padahal di kode validasi Laravel sudah diatur `max:5120` (5 MB).
* **Gejala di Browser**: Formulir langsung merespons dengan kesalahan generik tanpa masuk ke validasi form, atau mengembalikan halaman putih / error HTTP 413 (*Content Too Large*) / pesan *"The file failed to upload"*.

---

## 2. Lapisan yang Dilewati Berkas (End-to-End Lifecycle)

Sebuah berkas melewati 5 lapisan utama sebelum benar-benar tersimpan di disk privat:

```
[1. Browser Klien]
        │
        ▼ (HTTP Multipart POST)
[2. Web Server (Nginx / Apache / PHP CLI)]
        │
        ▼ (Tulis ke direktori sementara)
[3. PHP Engine Runtime (/tmp)]
        │
        ▼ (Inisialisasi Request & Middleware)
[4. Framework Laravel (Routing, CSRF, Validasi)]
        │
        ▼ ($file->store('...', 'local'))
[5. Filesystem Harddisk (storage/app/private/)]
```

### Lapisan dan Potensi Kegagalannya:
1. **Lapisan 1: Browser Klien (Formulir HTML)**
   * *Cara Kerja*: Browser membaca file lokal dari harddisk pengguna, membuat header `Content-Type: multipart/form-data`, dan mengirimkan aliran data biner.
   * *Potensi Gagal*: Tag `<form>` lupa menyertakan atribut `enctype="multipart/form-data"`. Jika atribut ini hilang, browser hanya mengirimkan nama teks berkas, bukan isi binernya.
   * *Kegagalan Lain*: Berkas di laptop pengguna sedang dikunci secara eksklusif oleh aplikasi lain (seperti Adobe Acrobat atau Microsoft Word), sehingga browser gagal membaca stream biner berkas.

2. **Lapisan 2: Web Server (Nginx / Apache)**
   * *Cara Kerja*: Menerima paket HTTP mentah dari jaringan dan mengalokasikannya ke buffer memori sebelum diteruskan ke FastCGI/PHP.
   * *Potensi Gagal*: Web server memiliki batas ukuran muatan HTTP (*body payload limit*). Jika ukuran file melampaui batas server, web server langsung memutus koneksi dan mengirimkan respons **HTTP 413 Payload Too Large** sebelum PHP dipanggil sama sekali.

3. **Lapisan 3: Runtime PHP Engine (`php.ini`)**
   * *Cara Kerja*: PHP menerima multipart stream dari web server, lalu secara otomatis menulis data biner tersebut ke folder sementara (*temporary directory*) server dengan nama acak seperti `/tmp/phpYtX81z`. PHP kemudian mengisi variabel superglobal `$_FILES`.
   * *Potensi Gagal*:
     - File melampaui batasan di konfigurasi `php.ini` (`upload_max_filesize` atau `post_max_size`). PHP otomatis mengosongkan `$_POST` dan `$_FILES`, atau memberikan kode status `UPLOAD_ERR_INI_SIZE`.
     - Folder penyimpanan sementara (*temp directory*) penuh (*Disk Full*) atau tidak memiliki izin tulis (*Permission Denied*), menghasilkan `UPLOAD_ERR_CANT_WRITE`.

4. **Lapisan 4: Framework Laravel (Middleware & Controller)**
   * *Cara Kerja*: Laravel membungkus `$_FILES` ke dalam objek `Illuminate\Http\UploadedFile`, memverifikasi token CSRF, menjalankan middleware otentikasi, memanggil Policy, dan menjalankan aturan validasi `$request->validate()`.
   * *Potensi Gagal*: Token CSRF kedaluwarsa (HTTP 419 Page Expired), tipe MIME terdeteksi bukan PDF/DOCX, atau aturan `max:5120` menolak berkas.

5. **Lapisan 5: Penyimpanan Akhir (Disk Harddisk)**
   * *Cara Kerja*: Metode `$file->store('submissions/1', 'local')` memindahkan (*move/copy*) berkas dari direktori sementara `/tmp` ke path tujuan permanen di `storage/app/private/`.
   * *Potensi Gagal*: Izin folder `storage/` tidak dapat ditulis oleh user proses web server (`chmod 775` / kepemilikan user `www-data`), atau ruang harddisk server habis.

---

## 3. Pengaturan Server yang Membatalkan Unggahan SEBELUM Dilihat Laravel

Jika berkas gagal diunggah tanpa memunculkan pesan validasi form Laravel, kegagalan hampir pasti terjadi di lapisan Web Server atau PHP Engine melalui pengaturan berikut:

1. **`upload_max_filesize` di `php.ini`**:
   * Nilai bawaan (*default*) PHP sering kali hanya **2M** (2 MegaByte).
   * Meskipun di Laravel Anda mengatur validasi `max:5120` (5 MB), berkas 3 MB akan langsung dipotong oleh PHP Engine di level runtime sebelum Laravel mengeksekusi controller.
2. **`post_max_size` di `php.ini`**:
   * Nilai bawaan PHP sering kali bernilai 8M.
   * `post_max_size` wajib diatur **lebih besar** daripada `upload_max_filesize` karena payload POST mencakup ukuran file ditambah token CSRF, header HTTP, dan field input formulir lainnya.
3. **`client_max_body_size` di Web Server Nginx**:
   * Nilai bawaan Nginx adalah **1m** (1 MegaByte).
   * Jika tidak disetel di blok `http {}` atau `server {}`, Nginx langsung menolak berkas berukuran lebih dari 1 MB dengan HTTP 413 tanpa pernah mengirimkan permintaan ke PHP.
4. **`LimitRequestBody` di Web Server Apache**:
   * Menentukan batas maksimal ukuran byte body request yang diizinkan oleh Apache.
5. **`upload_tmp_dir` dan Izin Direktori Sementara**:
   * Lokasi tempat PHP menyimpan file sementara. Jika path ini tidak didefinisikan atau partisi `/tmp` terkunci (*read-only*), PHP menghentikan unggahan dengan galat `UPLOAD_ERR_NO_TMP_DIR` atau `UPLOAD_ERR_CANT_WRITE`.
6. **`max_input_time` dan `max_execution_time`**:
   * Menentukan batas waktu maksimal bagi PHP untuk menerima input data dari klien. Pada koneksi jaringan lambat, file besar akan terputus di tengah jalan sebelum tuntas diterima.

---

## 4. Pertanyaan Diagnostik untuk Mempersempit Masalah

Untuk melacak di lapisan mana unggahan mengalami kegagalan, jawab pertanyaan diagnostik berikut:

1. **Apa nilai konfigurasi runtime PHP aktif Anda saat ini?**
   Periksa dengan perintah:
   ```bash
   php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL . 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
   ```
2. **Berapa kode galat numerik (`error code`) yang diterima oleh berkas?**
   Di controller atau `dd($request->file('file')->getError())`:
   - `0` = UPLOAD_ERR_OK (Lolos PHP, masalah ada di validasi Laravel).
   - `1` = UPLOAD_ERR_INI_SIZE (Ukuran melebihi `upload_max_filesize` di `php.ini`).
   - `2` = UPLOAD_ERR_FORM_SIZE (Melebihi `MAX_FILE_SIZE` HTML form).
   - `3` = UPLOAD_ERR_PARTIAL (Berkas terputus atau file terkunci di laptop klien).
   - `4` = UPLOAD_ERR_NO_FILE (Formulir tidak mengirim file / atribut `enctype` hilang).
   - `6` / `7` = Masalah izin folder `/tmp`.
3. **Berapa kode status HTTP yang muncul di tab Network (F12) Browser?**
   - Apakah HTTP 413 (*Nginx Client Max Body Size*)?
   - Apakah HTTP 419 (*Token CSRF Expired*)?
   - Apakah HTTP 302 (*Redirect kembali ke form karena validasi Laravel*)?
   - Apakah HTTP 500 (*Server Error / Permission denied di folder storage*)?
