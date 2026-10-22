# Catatan Praktikum Minggu 9: Upload File dan Data Dinamis

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Telusuri Satu Berkas

Pada tahap ini, saya menelusuri bagaimana berkas disimpan di sistem, dicatat di database, dan dilayani oleh Laravel 12.

### 1. Lokasi fisik berkas di sistem
Berkas materi yang diunggah ke disk privat tersimpan di:
* Path relatif: `storage/app/private/materials/{nama-acak}.pdf`
* Path lengkap di laptop saya: `D:\Semester 5\Pemweb\Week1\kampuslms-kelompok-02\storage\app\private\materials/{nama-acak}.pdf`

### 2. Perbandingan database vs fisik
Di tabel `materials` kolom `file_path`, yang tercatat hanya path relatif `materials/{nama-acak}.pdf`. Database tidak menyimpan path lengkap seperti `D:\...` supaya proyek tetap fleksibel saat dipindahkan ke laptop lain atau saat di-deploy ke server Linux.

### 3. Coba akses langsung di browser
Hasilnya gagal dan memunculkan error 404 Not Found. Hal ini terjadi karena berkas tersimpan di `storage/app/private/` yang posisinya berada di luar folder `public/`. Web server hanya membuka file yang berada di dalam folder `public/`, sehingga berkas privat tidak bisa dibuka sembarangan dan wajib dipanggil lewat Controller.

### 4. Beda disk `local` dan `public` di `config/filesystems.php`
* **Disk `local`**: Mengarah ke `storage/app/private`. Bersifat privat, tidak punya alamat URL publik, dan isinya hanya bisa diambil lewat Controller yang dilindungi Gate.
* **Disk `public`**: Mengarah ke `storage/app/public` dan punya alamat URL publik `/storage`. Dibuat khusus untuk file yang boleh dilihat siapa saja secara bebas tanpa login (seperti foto profil).

### 5. Fungsi `php artisan storage:link`
Perintah ini membuat shortcut (*symlink*) dari folder `public/storage` yang mengarah langsung ke `storage/app/public`. 

Materi kuliah dan tugas dilarang disimpan di sini karena file di folder symlink akan langsung dikirim oleh web server sebagai file statis biasa. Akibatnya, siapa pun yang tahu linknya bisa langsung mengunduh berkas tanpa perlu login dan tanpa dicek oleh Policy.

---

## Bagian BREAK: 8 Eksperimen Kerusakan & Keamanan

Berikut 8 eksperimen kerusakan yang saya uji untuk membuktikan ketahanan sistem penyimpanan berkas:

1. **Pindahkan berkas submission ke `storage/app/public`**:
   * *Hasil:* File langsung bisa dibuka dan diunduh di browser tanpa perlu login sama sekali lewat `/storage/...`.
   * *Pelajaran:* Berkas tugas mahasiswa mutlak dilarang ditaruh di disk public agar tidak bocor.

2. **Hapus `Gate::authorize()` dari method download**:
   * *Hasil:* Mahasiswa A bisa mengunduh file tugas Mahasiswa B hanya dengan mengganti ID di URL (`/submissions/2/download`).
   * *Pelajaran:* Terjadi celah IDOR. Gate wajib dipanggil sebelum `Storage::download()`.

3. **Simpan berkas pakai nama asli (`storeAs`) dengan nama `../../test.txt`**:
   * *Hasil:* Terjadi Path Traversal. File melompat ke folder atas di luar folder upload.
   * *Pelajaran:* Wajib pakai `$file->store()` bawaan Laravel yang otomatis mengacak nama berkas fisik.

4. **Unggah file PHP yang di-rename jadi `.pdf`**:
   * *Hasil:* Kalau validasi pakai `extensions:pdf` file lolos, tapi kalau pakai `mimes:pdf` langsung ditolak.
   * *Pelajaran:* Validasi `mimes` jauh lebih aman karena mengecek biner asli (*magic bytes*), bukan cuma nama file.

5. **Ubah validasi `max:5120` jadi `max:5` lalu upload file 1 MB**:
   * *Hasil:* Langsung error ditolak karena ukuran file melebihi 5 Kilobyte.
   * *Pelajaran:* Satuan `max` file di Laravel adalah Kilobyte (KB), jadi `max:5120` artinya 5 MB.

6. **Ubah `upload_max_filesize=1M` di `php.ini`, upload file 3 MB**:
   * *Hasil:* File langsung ditolak oleh PHP sebelum masuk ke validasi Laravel (`UPLOAD_ERR_INI_SIZE`).
   * *Pelajaran:* Pengaturan server PHP selalu menang dan mengalahkan validasi di framework Laravel.

7. **Ganti `updateOrCreate` jadi `create` lalu kumpulkan tugas dua kali**:
   * *Hasil:* Pengumpulan kedua error SQL `Duplicate entry` karena composite unique `(assignment_id, user_id)`.
   * *Pelajaran:* Wajib pakai `updateOrCreate` agar mahasiswa yang kumpul ulang menimpa submission lama.

8. **Hapus baris submission langsung lewat SQL di database**:
   * *Hasil:* Baris di database hilang, tapi file fisik di harddisk masih tertinggal (menjadi berkas yatim).
   * *Pelajaran:* Menghapus via database tidak memicu event model. Wajib hapus lewat Eloquent `$submission->delete()` agar event `deleting` aktif menghapus file fisik.

---

## Bagian CHECKPOINT: Evaluasi Pemahaman & Panduan Peragaan Live

Berikut panduan jawaban Checkpoint Minggu 9 lengkap dengan **tempat peragaan, akun yang dipakai, perintah copy-paste, dan kalimat yang diucapkan saat presentasi di depan dosen**:

---

### 1. Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya!
* **Alasannya:**
  Folder `storage/app/public` terhubung langsung ke web server publik lewat symlink (`public/storage`). Siapa pun di internet bisa langsung mengunduh berkas tanpa perlu login dan tanpa dicek oleh sistem otorisasi (Policy).
* **Tempat Peragaan:** Terminal Git Bash & Browser Chrome (Mode Incognito).
* **Langkah Praktik Langsung (Tinggal Tempel):**
  1. Buka Terminal Git Bash di folder proyek, buat berkas tes tiruan di folder public:
     ```bash
     mkdir -p storage/app/public/submissions && echo "Ini dokumen rahasia bocor" > storage/app/public/submissions/bocor.pdf
     ```
  2. Buka Browser Chrome biasa (atau mode Incognito), lalu ketik/tempel URL ini:
     ```text
     http://127.0.0.1:8000/storage/submissions/bocor.pdf
     ```
  3. **Hasil di Layar:** Berkas langsung terbuka dan terunduh bebas tanpa diminta login sama sekali!
  4. Sekarang bandingkan dengan berkas tugas kita di disk privat (`storage/app/private`). Coba ketik URL langsung di browser:
     ```text
     http://127.0.0.1:8000/storage/app/private/submissions/bocor.pdf
     ```
  5. **Hasil di Layar:** Muncul **HTTP 404 Not Found**.
* **Kalimat ke Dosen:**
  *"Bisa dilihat Pak/Bu, berkas di disk public bisa diunduh siapa saja tanpa login lewat symlink, sedangkan berkas tugas mahasiswa kita simpan di disk privat yang otomatis menghasilkan 404 jika ditembak langsung, karena wajib lewat Controller yang dilindungi Gate."*
* **Cara Membersihkan Kembali Berkas Uji Coba (Cleanup):**
  Setelah peragaan selesai, hapus folder uji coba tersebut lewat Terminal Git Bash agar penyimpanan bersih kembali:
  ```bash
  rm -rf storage/app/public/submissions
  ```
  *(Setelah dihapus, jika halaman browser tadi di-refresh, statusnya seketika kembali menjadi 404 Not Found).*

---

### 2. Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?
* **Alasannya:**
  Nama berkas asli yang dikirim dari laptop pengguna adalah masukan yang tidak terpercaya (*untrusted input*). Sistem kita menggunakan `$file->store()`, bukan `storeAs()` dengan nama asli.
* **Serangan yang Dicegah:**
  1. **Path Traversal:** Mencegah hacker menamai berkas `../../test.txt` untuk menimpa berkas sistem di folder induk.
  2. **File Overwrite (Tabrakan Berkas):** Mencegah berkas Mahasiswa A tertimpa oleh Mahasiswa B yang mengunggah berkas dengan nama yang kebetulan sama persis (misalnya sama-sama mengunggah `tugas1.pdf`).
  3. **Penebakan URL Langsung:** Hacker tidak bisa menebak nama fisik berkas karena berbentuk string hash acak 40 karakter.
* **Tempat Peragaan:** VS Code & File Explorer.
* **Langkah Praktik Langsung:**
  1. Di VS Code, buka file `app/Http/Controllers/SubmissionController.php` baris 108:
     Tunjukkan kode: `$filePath = $file->store('submissions/' . $assignment->id, 'local');`.
  2. Di File Explorer, buka folder: `storage/app/private/submissions/`.
  3. Tunjukkan ke dosen: nama berkas fisiknya berupa kode acak unik (seperti `a8Bc...pdf`), sedangkan nama aslinya disimpan terpisah di kolom database `original_name`.
* **Kalimat ke Dosen:**
  *"Nama fisik berkas sengaja kami acak dengan hash 40 karakter untuk mencegah serangan Path Traversal, mencegah berkas saling menimpa saat dua mahasiswa mengunggah nama file yang sama, dan agar URL fisik tidak bisa ditebak hacker."*

---

### 3. Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?
* **Bedanya:**
  * `extensions`: Hanya membaca teks tulisan di ujung nama berkas (misal kata `.pdf`). Sangat mudah dibohongi hanya dengan me-rename file.
  * `mimes`: Membaca langsung biner asli berkas (*magic bytes*) menggunakan modul PHP `fileinfo`.
* **Mana yang Lebih Aman:** Jelas **`mimes`**.
* **Tempat Peragaan:** Terminal Git Bash & Browser Chrome.
* **Akun yang Dipakai:** `mahasiswa@kampuslms.test` | Password: `password`.
* **Langkah Praktik Langsung:**
  1. Buka Terminal Git Bash, buat skrip PHP palsu berektensi `.pdf`:
     ```bash
     echo "<?php phpinfo(); ?>" > script_jahat.pdf
     ```
  2. Buka Browser, login dengan email `mahasiswa@kampuslms.test` dan password `password`.
  3. Buka halaman tugas: `http://127.0.0.1:8000/assignments/1`.
  4. Pada form pengumpulan, pilih berkas `script_jahat.pdf` tadi, lalu klik tombol **Kumpulkan Tugas**.
  5. **Hasil di Layar:** Muncul kotak peringatan merah: *"Format berkas harus berupa PDF, DOC, atau DOCX."*
* **Kalimat ke Dosen:**
  *"Meskipun ekstensi file sudah saya samarkan menjadi .pdf, validasi mimes di Laravel membaca magic bytes biner aslinya dan mengetahui bahwa ini adalah skrip teks/PHP, sehingga langsung ditolak demi mencegah eksekusi malware di server."*

---

### 4. `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?
* **Artinya:**
  Satuan `max` untuk berkas di Laravel adalah **Kilobyte (KB)**.  
  `5120 KB` / `1024` = **5 Megabyte (MB)**.
* **Yang Membatalkan di Level Server:**
  Pengaturan pada berkas `php.ini` server:
  1. `upload_max_filesize` (bawaan PHP sering kali hanya disetel `2M`).
  2. `post_max_size` (bawaan PHP biasanya `8M`).
* **Tempat Peragaan:** Terminal Git Bash.
* **Langkah Praktik Langsung:**
  1. Di Terminal Git Bash, ketik/tempel perintah ini:
     ```bash
     php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL . 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
     ```
  2. Perlihatkan outputnya ke dosen.
* **Kalimat ke Dosen:**
  *"`max:5120` di Laravel itu artinya 5 Megabyte. Namun jika di server direktif `upload_max_filesize` disetel 2M, maka berkas 3 MB akan langsung ditolak mental oleh server PHP sebelum kode validasi Laravel sempat membacanya."*

---

### 5. Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain!
* **Tempat Peragaan:** VS Code & Browser Chrome.
* **Akun yang Dipakai:** `mahasiswa@kampuslms.test` (User ID: 3, pemilik Submission ID: 1).
* **Langkah Praktik Langsung:**
  1. Di VS Code, buka `app/Http/Controllers/SubmissionController.php` baris method `download()`:
     ```php
     Gate::authorize('download', $submission);
     ```
  2. Buka `app/Policies/SubmissionPolicy.php` baris method `view()`:
     ```php
     if ($user->role === 'mahasiswa') {
         return $user->id === $submission->user_id;
     }
     ```
  3. Di Browser Chrome, login sebagai Mahasiswa biasa.
  4. Ketik/tempel URL download tugas milik mahasiswa lain di address bar:
     ```text
     http://127.0.0.1:8000/submissions/2/download
     ```
  5. Tekan Enter.
  6. **Hasil di Layar:** Muncul layar merah **HTTP 403 Forbidden** (*This action is unauthorized*).
* **Kalimat ke Dosen:**
  *"Ini pembuktian proteksi anti-IDOR kami Pak/Bu. Saat mahasiswa mencoba mengganti ID pengumpulan di URL menjadi milik temannya, sistem langsung mencegatnya lewat Gate dan melempar status 403 Forbidden."*

---

### 6. Kenapa `is_late` dihitung di server, bukan dikirim dari browser?
* **Alasannya:**
  Waktu di komputer pengguna (browser) sangat mudah dimanipulasi. Mahasiswa bisa dengan mudah memundurkan jam laptopnya atau mengubah nilai input formulir via inspect element agar tampak tepat waktu.
* **Tempat Peragaan:** VS Code.
* **Langkah Praktik Langsung:**
  1. Di VS Code, buka `app/Http/Controllers/SubmissionController.php` baris 55.
  2. Tunjukkan baris kodenya:
     ```php
     $isLate = now()->greaterThan($assignment->due_at);
     ```
* **Kalimat ke Dosen:**
  *"Status keterlambatan kami hitung secara independen di server menggunakan fungsi `now()` yang mengacu pada jam server, sehingga mahasiswa tidak bisa mencurangi batas waktu meskipun jam di laptopnya dimundurkan."*


