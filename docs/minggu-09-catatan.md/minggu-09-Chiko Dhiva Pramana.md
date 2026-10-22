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

## Bagian CHECKPOINT: Evaluasi Pemahaman & Peragaan Mandiri

Berikut jawaban checkpoint yang to the point dan siap saya peragakan langsung saat presentasi di depan dosen:

### 1. Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya!
* **Alasannya:**
  Folder `storage/app/public` punya jalan pintas (*symlink*) ke web publik di `public/storage`. Siapa pun di internet bisa mengunduh file di sana tanpa login, tanpa middleware, dan tanpa dicegat Policy.
* **Langkah Memperagakannya ke Dosen:**
  1. Buat file dummy di `storage/app/public/submissions/tugas_rahasia.pdf`.
  2. Buka browser jendela **Incognito** (mode penyamaran tanpa login).
  3. Buka URL: `http://127.0.0.1:8000/storage/submissions/tugas_rahasia.pdf`.
  4. Perlihatkan ke dosen: file langsung terunduh bebas!
  5. Sekarang tunjukkan file tugas asli kita di disk privat `storage/app/private`: buka URL yang sama, hasilnya pasti **404 Not Found** karena tidak ada akses publik langsung.

### 2. Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?
* **Alasannya:**
  Nama file yang dikirim dari laptop pengguna adalah data yang tidak boleh dipercaya (*untrusted input*). Sistem kita memakai `$file->store()`, bukan `storeAs()` nama asli.
* **Serangan yang Dicegah:**
  1. **Path Traversal:** Mencegah hacker menamai file `../../test.txt` untuk menimpa file penting sistem di folder lain.
  2. **File Overwrite (Tabrakan Berkas):** Mencegah file Mahasiswa A tertimpa oleh Mahasiswa B yang mengunggah file dengan nama yang kebetulan sama persis (misal sama-sama bernama `tugas1.pdf`).
  3. **Penebakan URL Langsung:** Hacker tidak bisa menebak nama file fisik karena berupa kode hash acak 40 karakter.

### 3. Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?
* **Bedanya:**
  * `extensions`: Hanya membaca teks tulisan di ujung nama file (misal kata `.pdf`). Sangat mudah dibohongi cuma dengan mengganti nama file.
  * `mimes`: Membaca langsung biner asli berkas (*magic bytes*) menggunakan modul PHP `fileinfo`.
* **Mana yang Lebih Aman:**
  Jelas **`mimes`**.
* **Langkah Memperagakannya ke Dosen:**
  1. Buat file script PHP palsu di terminal:
     ```bash
     echo "<?php phpinfo(); ?>" > script_jahat.pdf
     ```
  2. Coba unggah file `script_jahat.pdf` ini ke form pengumpulan tugas.
  3. Perlihatkan ke dosen: Laravel langsung menolaknya dengan pesan error *"Format berkas harus berupa PDF, DOC, atau DOCX."* karena Laravel tahu biner aslinya adalah skrip teks/PHP, bukan dokumen PDF biner.

### 4. `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?
* **Artinya:**
  Satuan `max` untuk file di Laravel adalah **Kilobyte (KB)**.  
  `5120 KB` / `1024` = **5 Megabyte (MB)**.
* **Yang Membatalkan di Level Server:**
  Pengaturan di file konfigurasi `php.ini` server:
  1. `upload_max_filesize` (bawaan PHP sering kali cuma `2M`).
  2. `post_max_size` (bawaan PHP biasanya `8M`).
  *Contoh:* Jika `upload_max_filesize` disetel `2M`, mahasiswa yang mengunggah file 3 MB akan langsung ditolak mental oleh server PHP sebelum kode validasi Laravel sempat membacanya.

### 5. Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain!
* **Langkah Memperagakannya ke Dosen:**
  1. **Buka file `app/Http/Controllers/SubmissionController.php` baris method `download()`:**
     Tunjukkan baris penjaganya:
     ```php
     Gate::authorize('download', $submission);
     ```
  2. **Buka file `app/Policies/SubmissionPolicy.php` baris method `view()`:**
     Tunjukkan logikanya:
     ```php
     if ($user->role === 'mahasiswa') {
         return $user->id === $submission->user_id;
     }
     ```
  3. **Demokan Langsung di Browser:**
     - Login sebagai Mahasiswa A, buka tugas miliknya: berhasil diunduh.
     - Ubah ID di URL menjadi submission milik Mahasiswa B: `http://127.0.0.1:8000/submissions/2/download`.
     - Tunjukkan hasilnya ke dosen: seketika muncul layar merah **HTTP 403 Forbidden**.

### 6. Kenapa `is_late` dihitung di server, bukan dikirim dari browser?
* **Alasannya:**
  Waktu di komputer pengguna (browser) sangat mudah dicurangi. Mahasiswa bisa dengan mudah memundurkan jam di laptopnya atau mengubah nilai input tersembunyi via inspect element.
* **Solusinya di Kode Kita:**
  Server menghitung keterlambatan secara adil dan objektif menggunakan jam server:
  ```php
  $isLate = now()->greaterThan($assignment->due_at);
  ```
  Dengan cara ini, mahasiswa tidak bisa memalsukan status pengumpulan tepat waktu.

