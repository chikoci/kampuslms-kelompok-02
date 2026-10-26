# Minggu 09

**Nama:** Dawwas Eryansyah Pratama

**NIM:** 10241019

---

## 1. READ — Telusuri Satu Berkas (30 menit)

### 1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?

Ketika berkas diunggah melalui formulir materi atau tugas (misalnya pengumpulan tugas mahasiswa di `SubmissionController@store`), berkas disimpan menggunakan method `$file->store('submissions')` pada disk default framework (`local`).

Di Laravel 12, konfigurasi disk `local` berakar pada folder privat:
* **Path Relatif di Proyek:** `storage/app/private/submissions/<nama_acak>.pdf`
* **Path Fisik Absolut di Harddisk:**
  ```text
  D:\PROWEB\kampuslms-kelompok-02\storage\app\private\submissions\XyZ123Abc456.pdf
  ```
Berkas fisik disimpan dengan penamaan acak (hash acak 40 karakter) dan berada di dalam direktori `private` yang sepenuhnya berada di luar jangkauan folder publik web server.

---

### 2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.

Ketika memeriksa data pada tabel `submissions` atau `materials` di database:
* **Nilai Kolom `file_path` di Database:**
  ```text
  submissions/XyZ123Abc456.pdf
  ```
* **Nilai Kolom `original_name` di Database:**
  ```text
  Laporan_Praktikum_Dawwas.pdf
  ```
* **Lokasi Fisik Sebenarnya di Disk:**
  ```text
  D:\PROWEB\kampuslms-kelompok-02\storage\app\private\submissions\XyZ123Abc456.pdf
  ```

**Perbandingan & Analisis:**
Database **hanya menyimpan path relatif** terhadap *root* disk penyimpanan lokal, bukan path absolut harddisk (`D:\...`).
Hal ini adalah praktik terbaik (*best practice*) dalam rekayasa perangkat lunak:
1. Menjaga portabilitas aplikasi agar ketika proyek di-deploy ke server Linux produksi (misal path `/var/www/kampuslms/storage/app/private`), referensi berkas di basis data tidak rusak.
2. Memisahkan antara identitas nama berkas fisik yang acak (untuk keamanan) dengan nama asli berkas yang diunggah pengguna (`original_name`) agar saat berkas diunduh kembali, nama aslinya tetap dapat dipulihkan.

---

### 3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?

Saya mencoba mengetik langsung URL tebakan pada kolom alamat peramban (browser):
```text
http://127.0.0.1:8000/storage/submissions/XyZ123Abc456.pdf
http://127.0.0.1:8000/submissions/XyZ123Abc456.pdf
```

* **Hasil Aktual:** **Gagal total (HTTP 404 Not Found)**.
* **Kenapa Hal Ini Terjadi?**
  Karena web server (Nginx/Apache maupun built-in web server PHP) **hanya mengekspos folder `public/` sebagai Document Root**. Folder `storage/app/private/` berada di luar root web server. Tidak ada alias URL, simbolik link, maupun konfigurasi web server yang mengarahkan request HTTP publik langsung ke folder `private` tersebut. Berkas privat **hanya bisa dibuka jika dilewatkan melalui Controller dan Policy Laravel** menggunakan `Storage::download()`.

---

### 4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?

Pada berkas `config/filesystems.php`, terdapat pendefinisian dua disk penyimpanan utama:

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'serve' => true,
        'throw' => false,
    ],

    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
]
```

**Tabel Perbedaan Persis Antara Disk `local` dan `public`:**

| Parameter | Disk `local` (Privat) | Disk `public` (Publik) |
|---|---|---|
| **Lokasi Root** | `storage/app/private` | `storage/app/public` |
| **Akses Langsung Browser** | **Tidak Bisa** (Di luar Document Root) | **Bisa** melalui symlink `public/storage` |
| **Properti URL** | Tidak memiliki properti `url` | Memiliki properti `'url' => env('APP_URL').'/storage'` |
| **Mekanisme Akses** | Wajib lewat Route + Controller + Policy | Langsung lewat URL berkas di peramban tanpa login |
| **Peruntukan di KampusLMS** | Materi kuliah privat, pengumpulan tugas mahasiswa | Foto profil pengguna, logo kampus, favicon |

---

### 5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda tidak boleh ada di sana?

Perintah `php artisan storage:link` membuat sebuah **tautan simbolis (*symbolic link* / symlink)** dari folder `public/storage` yang mengarah ke folder `storage/app/public`.

```text
public/storage  ───(symlink)───►  storage/app/public
```

**Kenapa berkas materi dan submission TIDAK BOLEH ditaruh di sana?**
Karena setiap berkas yang berada di dalam `storage/app/public` otomatis dapat diakses oleh **siapa saja di seluruh dunia tanpa perlu login, tanpa middleware `auth`, dan tanpa Policy**, cukup dengan mengetahui URL-nya.
Jika berkas tugas ditaruh di sana:
1. Siapa pun (termasuk orang luar kampus) bisa mengunduh tugas mahasiswa lain cukup dengan menebak URL seperti `/storage/submissions/tugas-dawwas.pdf`.
2. Jika ada mahasiswa yang membagikan tautan berkas di media sosial/WhatsApp, seluruh mahasiswa lain dapat langsung menyalin jawaban tugas tersebut tanpa terdeteksi oleh sistem otorisasi aplikasi.

---

## 2. BREAK — Delapan Kerusakan & Eksperimen Keamanan (50 menit)

Berikut dokumentasi dan pengujian 8 skenario perusakan yang saya telusuri dan buktikan pada aplikasi KampusLMS:

### 1. Pindahkan berkas submission ke `storage/app/public`, akses URL-nya tanpa login
* **Yang Dilakukan:** Memindahkan berkas submission mahasiswa ke direktori `storage/app/public/submissions/tugas1.pdf`, lalu membuka URL `http://127.0.0.1:8000/storage/submissions/tugas1.pdf` pada jendela Incognito (tanpa login).
* **Hasil Pengamatan:** Berkas tugas langsung terunduh dan terbuka bebas di peramban tanpa diminta login sama sekali.
* **Kesimpulan Fatal:** Menyimpan berkas privat di disk `public` merusak seluruh arsitektur keamanan. Seluruh tugas mahasiswa satu angkatan bocor ke publik tanpa perlindungan otorisasi.

---

### 2. Hapus `Gate::authorize()` dari endpoint download, akses submission orang lain
* **Yang Dilakukan:** Menghapus baris `Gate::authorize('download', $submission);` pada method `SubmissionController@download`.
* **Hasil Pengamatan:** Mahasiswa A yang sedang login dapat mengunduh berkas jawaban milik Mahasiswa B cukup dengan menembak URL `/submissions/2/download`.
* **Kesimpulan:** Terjadi celah **IDOR (*Insecure Direct Object Reference*) versi berkas fisik**. Penjagaan otorisasi Policy wajib dipasang sebelum perintah `Storage::download()` dijalankan.

---

### 3. Simpan berkas dengan `storeAs()` memakai nama asli, unggah berkas bernama `../../test.txt`
* **Yang Dilakukan:** Menggunakan `$file->storeAs('submissions', $file->getClientOriginalName())` dan mengunggah berkas yang nama aslinya dimanipulasi menjadi `../../test.txt`.
* **Hasil Pengamatan:** Terjadi serangan **Path Traversal**. Berkas tidak tersimpan di folder `submissions`, melainkan melompat keluar ke folder induk tingkat atas.
* **Kesimpulan:** Nama berkas dari pengguna adalah masukan yang tidak terpercaya (*untrusted input*). Sistem wajib menggunakan method `$file->store()` bawaan Laravel yang selalu menghasilkan nama fisik acak (*hash string*), sementara nama asli hanya disimpan sebagai teks di kolom `original_name`.

---

### 4. Unggah berkas PHP yang diganti namanya jadi `.pdf` dengan validasi `extensions`, lalu dengan `mimes`
* **Yang Dilakukan:** Membuat skrip PHP berbahaya (`payload.php`), mengganti namanya menjadi `payload.pdf`, lalu mengunggahnya pada dua aturan validasi berbeda.
* **Hasil Pengamatan:**
  * Pada validasi `'file' => ['extensions:pdf']`: Berkas **lolos validasi**, karena aturan `extensions` hanya memeriksa teks ekstensi di ujung nama berkas.
  * Pada validasi `'file' => ['mimes:pdf']`: Berkas **langsung ditolak** oleh Laravel.
* **Kesimpulan:** Aturan `mimes` membaca *Magic Bytes* (MIME Content-Type asli) dari struktur biner isi berkas. Berkas PHP yang menyamar menjadi PDF terdeteksi sebagai `text/x-php` dan langsung digagalkan demi mencegah eksekusi kode berbahaya (*Remote Code Execution*).

---

### 5. Ubah `max:5120` menjadi `max:5`, unggah berkas 1 MB
* **Yang Dilakukan:** Mengubah aturan validasi ukuran berkas menjadi `'file' => ['max:5']`, lalu mencoba mengunggah berkas dokumen berukuran 1 MB.
* **Hasil Pengamatan:** Unggahan gagal dan Laravel menampilkan pesan error: *"The file field must not be greater than 5 kilobytes."*
* **Kesimpulan:** Satuan aturan `max` untuk berkas di Laravel adalah **Kilobyte (KB)**, bukan Megabyte. `max:5120` berarti 5.120 KB (5 MB). Jika hanya menulis `max:5`, batas maksimalnya menjadi 5 KB.

---

### 6. Set `upload_max_filesize=1M` di php.ini, unggah berkas 3 MB dengan validasi `max:5120`
* **Yang Dilakukan:** Mengatur direktif `upload_max_filesize = 1M` pada konfigurasi `php.ini`, lalu mencoba mengunggah berkas berukuran 3 MB di aplikasi yang validasinya `max:5120` (5 MB).
* **Hasil Pengamatan:** Unggahan langsung ditolak oleh server PHP dengan error `UPLOAD_ERR_INI_SIZE` sebelum kode validasi Laravel sempat dieksekusi. Array `$request->file('file')` bernilai `null`.
* **Kesimpulan:** **Konfigurasi server (PHP/Nginx) selalu mengalahkan validasi aplikasi Laravel.** Batas `upload_max_filesize` dan `post_max_size` di server wajib disetel lebih besar atau sama dengan batas validasi form di Laravel.

---

### 7. Hapus `updateOrCreate`, ganti `create`, kumpulkan tugas dua kali
* **Yang Dilakukan:** Mengganti method `updateOrCreate` pada `SubmissionController@store` menjadi `create()`, lalu mahasiswa mencoba mengumpulkan tugas sebanyak dua kali pada penugasan yang sama.
* **Hasil Pengamatan:** Pengumpulan kedua langsung gagal dengan error database:  
  `Integrity constraint violation: 1062 Duplicate entry ... for key 'submissions_assignment_id_user_id_unique'`.
* **Kesimpulan:** Constraint *composite unique* `['assignment_id', 'user_id']` yang dibuat pada migrasi Minggu 3 berhasil menyelamatkan integritas database. Penggunaan method `updateOrCreate` adalah keharusan agar mahasiswa yang mengumpulkan ulang memperbarui baris data yang ada, bukan menumpuk baris baru.

---

### 8. Hapus satu submission dari database lewat SQL langsung, cek folder storage
* **Yang Dilakukan:** Menghapus satu baris pengumpulan tugas langsung lewat kueri SQL `DELETE FROM submissions WHERE id=1;`, kemudian memeriksa folder penyimpanan di `storage/app/private/submissions/`.
* **Hasil Pengamatan:** Baris data di MySQL terhapus, tetapi berkas fisik dokumen di harddisk masih tetap ada.
* **Kesimpulan:** Terjadi **Berkas Yatim (*Orphan Files*)**. Penghapusan langsung di tingkat database (maupun cascade foreign key) tidak memicu event model Eloquent (`deleting`). Berkas fisik harus dihapus secara terprogram menggunakan `Storage::delete($submission->file_path)` sebelum record-nya dihapus dari database.

---

## 3. CHECKPOINT MINGGU 09 — Evaluasi Pemahaman Mandiri

Berikut adalah rangkuman jawaban komprehensif atas seluruh instrumen evaluasi Minggu 9:

### 1. Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya.

* **Alasan:**  
  Direktori `storage/app/public` ditautkan secara langsung ke folder publik web server (`public/storage`) melalui symlink. Siapa pun di internet dapat mengunduh berkas di sana tanpa perlu login, tanpa dicegat middleware, dan tanpa evaluasi Policy.
* **Peragaan Kebocoran:**  
  1. Ambil berkas tugas di `storage/app/public/submissions/jawaban_uts.pdf`.
  2. Buka jendela Incognito (tanpa login sama sekali).
  3. Masukkan URL: `http://localhost:8000/storage/submissions/jawaban_uts.pdf`.
  4. Berkas langsung terunduh secara bebas. Sebaliknya, pada disk `local` (`storage/app/private`), URL tersebut menghasilkan **HTTP 404 Not Found** karena berkas wajib diunduh melalui controller yang memanggil `Gate::authorize('download', $submission)`.

---

### 2. Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?

* **Alasan Penamaan Acak:**  
  Nama berkas yang dikirimkan oleh browser pengguna adalah masukan yang tidak boleh dipercaya (*untrusted user input*). Method `$file->store('submissions')` secara otomatis menghasilkan nama acak kriptografis 40 karakter (misal: `7zKqW1m9P...pdf`).
* **Serangan yang Dicegah:**
  1. **Path Traversal / Directory Traversal:** Mencegah nama berkas jahat seperti `../../../../etc/passwd` atau `../../.env` yang dapat menimpa file konfigurasi sensitif di server.
  2. **File Overwrite:** Mencegah berkas milik mahasiswa A tertimpa oleh mahasiswa B yang kebetulan mengunggah file dengan nama yang sama persis (misal `tugas1.pdf`).
  3. **Direct File Execution:** Mencegah penyerang menebak URL fisik berkas secara langsung di server.

---

### 3. Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?

* **Perbedaan:**
  * **`extensions` (Ekstensi Saja):** Hanya memeriksa teks ekstensi di belakang nama file (misal `.pdf`). Aturan ini sangat mudah dibobol hanya dengan me-rename file `virus.exe` atau `script.php` menjadi `script.pdf`.
  * **`mimes` (Pemeriksaan Konten Nyata / MIME):** Membaca *Magic Bytes* (struktur biner header berkas) menggunakan PHP `finfo` untuk memastikan tipe konten aslinya.
* **Mana yang Lebih Aman:**  
  **`mimes` jauh lebih aman**, karena meskipun penyerang mengganti nama file skrip PHP berbahaya menjadi `laporan.pdf`, aturan `mimes:pdf` akan membaca bahwa isi aslinya adalah `text/x-php` dan langsung membatalkan unggahan.

---

### 4. `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?

* **Besaran Ukuran:**  
  Satuan aturan `max` untuk berkas di Laravel adalah **Kilobyte (KB)**.  
  `max:5120` = $5120 \div 1024 = \mathbf{5\text{ Megabyte (MB)}}$.
* **Faktor Pembatal di Level Server:**  
  Batas validasi ini akan dibatalkan (tidak berguna) jika konfigurasi PHP server pada berkas `php.ini` menyetel batas lebih kecil daripada Laravel:
  1. **`upload_max_filesize`:** Batas ukuran maksimum satu berkas yang diunggah (misal disetel `2M`).
  2. **`post_max_size`:** Batas ukuran keseluruhan payload HTTP POST (misal disetel `4M`).
  Jika pengguna mengunggah berkas 4 MB, server PHP akan langsung menolaknya sebelum request masuk ke Laravel.

---

### 5. Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain.

Pencegahan dilakukan secara berlapis pada Controller dan Policy:

1. **Di Controller ([app/Http/Controllers/SubmissionController.php:104](file:///d:/PROWEB/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php#L104)):**
   ```php
   public function download(Submission $submission)
   {
       Gate::authorize('download', $submission); // Memanggil gerbang otorisasi
       ...
       return Storage::download($submission->file_path, $submission->original_name);
   }
   ```

2. **Di Policy ([app/Policies/SubmissionPolicy.php:119-122](file:///d:/PROWEB/kampuslms-kelompok-02/app/Policies/SubmissionPolicy.php#L119-L122)):**
   ```php
   public function download(User $user, Submission $submission): bool
   {
       return $this->view($user, $submission);
   }
   ```
   Dan pada method `view()` di baris ke-54:
   ```php
   if ($user->role === 'mahasiswa') {
       return $user->id === $submission->user_id; // Hanya pemilik sah yang diizinkan!
   }
   ```
Jika Mahasiswa A mencoba mengunduh submission milik Mahasiswa B, sistem langsung melempar status **HTTP 403 Forbidden**.

---

### 6. Kenapa `is_late` dihitung di server, bukan dikirim dari browser?

* **Alasan Keamanan (*Tampering Prevention*):**  
  Waktu dan tanggal di komputer klien (browser) sangat mudah dimanipulasi oleh pengguna (misalnya memundurkan jam sistem operasi komputer atau memodifikasi payload HTTP request).
* **Solusi di Server:**  
  Status keterlambatan wajib dihitung secara independen dan objektif menggunakan jam resmi server:
  ```php
  $isLate = now()->greaterThan($assignment->due_at);
  ```
Dengan menghitung di server, mahasiswa tidak dapat mencurangi batas waktu pengumpulan tugas meskipun memanipulasi jam di laptopnya.
