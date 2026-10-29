# Catatan Praktikum Minggu 09

**Nama:** Armansyah  
**NIM:** 10241013  

---

## READ — Telusuri Satu Berkas (30 menit)

Sesuai instruksi modul praktikum (*"Unggah satu berkas materi, lalu tanpa AI"*), saya mengunggah satu berkas materi kuliah baru melalui antarmuka Dosen pada mata kuliah **Pemrograman Web (SI201)**:
* **Mata Kuliah:** Pemrograman Web (ID: 1)
* **Judul Materi:** `materi perkuliahan uji coba`
* **Deskripsi:** `ini materi uts`
* **Nama Berkas Asli:** `PANDUAN_UTS_LENGKAP.pdf`
* **Diunggah oleh:** Dosen Pengampu (Dr. Dosen Pengampu, M.Kom. - ID: 2)

---

### 1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?

Ketika berkas diunggah melalui formulir materi kuliah (`MaterialController@store`), berkas disimpan menggunakan method:
```php
$filePath = $file->store('materials/' . $course->id, 'local');
```

Di Laravel 12, disk `local` berakar pada folder privat `storage/app/private`:
* **Path Relatif di Proyek:**  
  `storage/app/private/materials/1/4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`
* **Path Fisik Absolut di Harddisk:**  
  `D:\PERKULIAHAN\semester 5\PB-Pemrograman Web\kampuslms-kelompok-02\storage\app\private\materials\1\4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`

Berkas fisik disimpan dengan nama acak hash 40 karakter (`4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`) dan berada sepenuhnya di dalam direktori `storage/app/private/materials/1/` yang terkunci di luar jangkauan Document Root web server.

---

### 2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.

Saya memeriksa data baris yang bersangkutan di database MySQL pada tabel `materials` melalui konsol terminal `php artisan tinker`:
```bash
php artisan tinker --execute="App\Models\Material::latest()->first(['id', 'course_id', 'title', 'original_name', 'file_path', 'file_size', 'mime_type'])"
```

**Hasil Keluaran Data di Database:**
* **ID:** `16`
* **Course ID:** `1`
* **Kolom `title`:** `materi perkuliahan uji coba`
* **Kolom `original_name`:** `PANDUAN_UTS_LENGKAP.pdf`
* **Kolom `file_size`:** `194780` bytes (~190.2 KB)
* **Kolom `mime_type`:** `application/pdf`
* **Kolom `file_path`:** `materials/1/4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`

**Perbandingan dengan Lokasi Fisik di Harddisk:**
* **Di Database:** Hanya tersimpan path relatif terhadap root disk privat:  
  `materials/1/4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`
* **Di Harddisk:** Tersimpan pada path absolut sistem operasi:  
  `D:\PERKULIAHAN\semester 5\PB-Pemrograman Web\kampuslms-kelompok-02\storage\app\private\materials\1\4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`

**Analisis & Alasan Desain:**
1. **Portabilitas Lingkungan (*Environment Decoupling*):** Database tidak boleh menyimpan path absolut harddisk (seperti `D:\...` atau `/home/...`). Jika path absolut disimpan di database, aplikasi akan langsung rusak saat dipindahkan (*deploy*) ke server produksi Linux (misalnya di `/var/www/kampuslms/storage/app/private`). Dengan menyimpan path relatif, sistem tetap portabel dan independen dari sistem operasi server.
2. **Pemisahan Identitas Nama Fisik vs Nama Asli:** Nama fisik acak di harddisk mencegah tabrakan nama berkas (*name collision*), penimpaan berkas (*file overwriting*), dan serangan *Path Traversal*, sedangkan nama asli berkas (`original_name` = `PANDUAN_UTS_LENGKAP.pdf`) disimpan di database agar saat mahasiswa atau dosen mengunduh kembali materi tersebut, nama file aslinya tetap dapat dipulihkan.

---

### 3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?

Saya mencoba mengetikkan URL tebakan langsung pada kolom alamat peramban (browser) untuk berkas materi perkuliahan:
1. `http://kampuslms-kelompok-02.test/storage/materials/1/4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`
2. `http://kampuslms-kelompok-02.test/materials/1/4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`

**Hasil Pengamatan & Status HTTP:**

* **URL 1 (`/storage/materials/...`): GAGAL DENGAN STATUS HTTP 403 (FORBIDDEN)**  
  Peramban menampilkan halaman kesalahan kustom KampusLMS: *"403 - Akses Ditolak: Anda tidak memiliki hak akses atau izin yang sah untuk membuka sumber daya ini."*
  * **Kenapa (Why 403)?**  
    1. Pada `config/filesystems.php`, disk `local` disetel dengan opsi `'serve' => true`. Melalui `FilesystemServiceProvider`, Laravel secara otomatis mendaftarkan rute internal `/storage/{path}` yang ditangani oleh controller bawaan `Illuminate\Filesystem\ServeFile`.
    2. Di dalam controller `ServeFile`, Laravel memeriksa keabsahan akses berkas:
       ```php
       abort_unless(
           $this->hasValidSignature($request),
           $this->isProduction ? 404 : 403
       );
       ```
    3. Karena berkas ini berada di disk privat `local` (bukan disk publik) dan diakses langsung secara mentah **tanpa tanda tangan digital yang valid (*Signed URL* / `?signature=...`)**, maka pada lingkungan lokal (`APP_ENV=local`), Laravel secara tegas mengeksekusi `abort(403)`.

* **URL 2 (`/materials/...`): GAGAL DENGAN STATUS HTTP 404 (NOT FOUND)**  
  Peramban menampilkan halaman kesalahan *"404 - Halaman Tidak Ditemukan"*.
  * **Kenapa (Why 404)?**  
    1. **Sisi Web Server (Nginx/Herd):** Web server memeriksa folder `public/` untuk mencari berkas statis `materials/1/4rwjUzpO1IYIPtzkXyOsLAliUDjlH3uBZ2CvAMl6.pdf`. Karena berkas fisik aslinya berada di dalam `storage/app/private/` (di luar Document Root), web server tidak menemukannya dan mengoper request ke `index.php` (Laravel Router).
    2. **Sisi Laravel Router:** Laravel mencocokkan URL tersebut dengan tabel rute di `routes/web.php`. Rute yang terdaftar hanya:
       - `GET /materials/{material}` (mengharapkan ID integer untuk melihat detail materi, bukan path file)
       - `GET /materials/{material}/download` (rute resmi untuk mengunduh berkas dengan otorisasi)
       URL tebakan `/materials/1/4rwj...` tidak cocok (*no matching route*) dengan rute mana pun di aplikasi, sehingga Laravel mengembalikan respons `404 Not Found`.

**Kesimpulan:**
Kedua upaya menebak URL langsung di peramban **gagal total**. Sistem dilindungi berlapis: baik melalui rute internal Laravel yang menolak akses berkas privat tanpa tanda tangan digital (**HTTP 403**), maupun melalui isolasi web server dan routing yang menolak path URL statis yang tidak terdaftar (**HTTP 404**). Satu-satunya cara mengakses berkas adalah melalui rute controller terproteksi yang memverifikasi login dan *Policy* (`Gate::authorize('download', $material)`).

---

### 4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?

Pada berkas `config/filesystems.php` baris 33–48, konfigurasi disk `local` dan `public` didefinisikan sebagai berikut:

```php
'disks' => [

    'local' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'serve' => true,
        'throw' => false,
        'report' => false,
    ],

    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
    ],
```

**Tabel Perbedaan Persis Antara Disk `local` dan Disk `public`:**

| Parameter Pembeda | Disk `local` (Penyimpanan Privat) | Disk `public` (Penyimpanan Publik) |
| :--- | :--- | :--- |
| **Lokasi Direktori Root** | `storage/app/private` | `storage/app/public` |
| **Visibilitas Berkas** | Terkunci privat (di luar web root) | Terbuka publik (`'visibility' => 'public'`) |
| **Properti URL Publik** | **Tidak ada** (tidak memiliki atribut `'url'`) | **Ada** (`'url' => env('APP_URL').'/storage'`) |
| **Akses Langsung Browser** | **Tidak Bisa (404 Not Found)** | **Bisa** melalui symlink `public/storage` |
| **Mekanisme Akses** | Wajib lewat Rute + Controller + Policy (`Gate::authorize()`) | Langsung via tag HTML `<a href="/storage/...">` tanpa login |
| **Peruntukan di KampusLMS** | Pengumpulan tugas mahasiswa, berkas materi perkuliahan | Foto profil pengguna, logo kampus, favicon publik |

---

### 5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda tidak boleh ada di sana?

Ketika menjalankan perintah:
```bash
php artisan storage:link
```
Sistem menghasilkan output:
```text
The [public/storage] link has been connected to [storage/app/public].
```

**Apa yang dibuatnya?**  
Perintah ini membuat sebuah **tautan simbolis (*symbolic link* / symlink)** dari folder `public/storage` yang mengarah langsung ke folder fisik `storage/app/public`:
```text
public/storage  ───(Symbolic Link)───►  storage/app/public
```

**Kenapa berkas materi dan submission TIDAK BOLEH ditaruh di sana?**
Karena semua berkas yang berada di dalam `storage/app/public` otomatis menjadi **milik publik yang terbuka bebas di internet**:
1. **Bypass Autentikasi & Otorisasi:** Siapa pun di seluruh dunia dapat mengunduh berkas tersebut cukup dengan mengetahui URL-nya, tanpa perlu login, tanpa middleware `auth`, dan tanpa dicek oleh `Policy`.
2. **Mudah Ditebak & Tersebar:** Jika ada mahasiswa yang membagikan tautan berkas tugas di media sosial/WhatsApp (misal `/storage/submissions/tugas.pdf`), seluruh mahasiswa lain dari berbagai kelas bahkan orang luar kampus dapat mengunduh dan menyontek tugas tersebut tanpa terdeteksi oleh sistem otorisasi kampus.


---

## BREAK — Delapan Kerusakan & Eksperimen Keamanan (50 menit)

Berikut hasil pengujian 8 skenario perusakan dan simulasi kerentanan keamanan yang diuji pada KampusLMS:

### 1. Pindahkan berkas submission ke `storage/app/public`, akses URL-nya tanpa login
* **Langkah Uji:** Memindahkan berkas submission ke `storage/app/public/submissions/jawaban_uts.pdf`, lalu membuka URL `http://kampuslms-kelompok-02.test/storage/submissions/jawaban_uts.pdf` pada jendela *Incognito* peramban (tanpa login).
* **Hasil Pengamatan:** Berkas tugas langsung terbuka dan terunduh bebas tanpa diminta login sama sekali.
* **Kesimpulan Fatal:** Menyimpan berkas privat di disk `public` merusak total arsitektur keamanan LMS. Seluruh tugas mahasiswa dapat bocor ke publik.

---

### 2. Hapus `Gate::authorize()` dari endpoint download, akses submission orang lain
* **Langkah Uji:** Mengomentari baris `Gate::authorize('download', $submission);` pada `SubmissionController@download`, lalu Mahasiswa A mencoba mengakses URL unduhan milik Mahasiswa B (`/submissions/2/download`).
* **Hasil Pengamatan:** Mahasiswa A berhasil mengunduh berkas jawaban milik Mahasiswa B.
* **Kesimpulan:** Terjadi kerentanan **IDOR (*Insecure Direct Object Reference*) pada berkas privat**. Otorisasi Policy wajib dieksekusi sebelum method `Storage::download()` dijalankan.

---

### 3. Simpan berkas dengan `storeAs()` memakai nama asli, unggah berkas bernama `../../test.txt`
* **Langkah Uji:** Mengganti `$file->store()` menjadi `$file->storeAs('submissions', $file->getClientOriginalName())` dan mengunggah berkas yang nama aslinya dimanipulasi menjadi `../../test.txt`.
* **Hasil Pengamatan:** Berkas tidak tersimpan di dalam folder `submissions/`, melainkan melompat keluar ke direktori induk di atasnya (*Path Traversal*).
* **Kesimpulan:** Nama berkas dari pengguna adalah masukan yang tidak terpercaya (*untrusted input*). Sistem wajib menggunakan method `$file->store()` yang selalu mengacak nama fisik berkas menggunakan hash acak.

---

### 4. Unggah berkas PHP yang diganti namanya jadi `.pdf` dengan validasi `extensions`, lalu dengan `mimes`
* **Langkah Uji:** Membuat skrip PHP berbahaya (`shell.php`), me-rename namanya menjadi `shell.pdf`, kemudian mengunggahnya pada dua aturan validasi:
  * Uji A: Aturan `'file' => ['extensions:pdf']`
  * Uji B: Aturan `'file' => ['mimes:pdf']`
* **Hasil Pengamatan:**
  * Pada Uji A (`extensions`): Berkas **lolos validasi** karena sistem hanya membaca teks akhiran `.pdf`.
  * Pada Uji B (`mimes`): Berkas **langsung ditolak** oleh Laravel dengan pesan error format berkas tidak valid.
* **Kesimpulan:** Aturan `mimes` membaca *Magic Bytes* (MIME Content-Type biner asli) berkas menggunakan `finfo`. File PHP yang menyamar menjadi PDF terdeteksi sebagai `text/x-php` dan langsung digagalkan demi mencegah eksekusi kode berbahaya (*Remote Code Execution*).

---

### 5. Ubah `max:5120` menjadi `max:5`, unggah berkas 1 MB
* **Langkah Uji:** Mengubah aturan validasi ukuran menjadi `'file' => ['max:5']`, lalu mengunggah dokumen tugas berukuran 1 MB (1024 KB).
* **Hasil Pengamatan:** Unggahan gagal dan Laravel menampilkan pesan: *"Ukuran berkas tugas tidak boleh melebihi 5 kilobytes."*
* **Kesimpulan:** Satuan aturan `max` untuk berkas di Laravel adalah **Kilobyte (KB)**, bukan Megabyte. `max:5120` berarti 5.120 KB = 5 MB. Menulis `max:5` membatasi berkas maksimal hanya 5 KB.

---

### 6. Set `upload_max_filesize=1M` di php.ini, unggah berkas 3 MB dengan validasi `max:5120`
* **Langkah Uji:** Mengatur direktif `upload_max_filesize = 1M` pada konfigurasi `php.ini`, lalu mencoba mengunggah berkas 3 MB pada form yang divalidasi `max:5120` di Laravel.
* **Hasil Pengamatan:** Unggahan langsung ditolak oleh server PHP dengan kode error `UPLOAD_ERR_INI_SIZE`. Objek `$request->file('file')` bernilai `null` bahkan sebelum kode validasi Laravel sempat dieksekusi.
* **Kesimpulan:** **Batas konfigurasi web server (PHP/Nginx) selalu mengalahkan validasi Laravel.** Konfigurasi `upload_max_filesize` dan `post_max_size` di server harus disetel lebih besar atau sama dengan batas form aplikasi.

---

### 7. Hapus `updateOrCreate`, ganti `create`, kumpulkan tugas dua kali
* **Langkah Uji:** Mengganti method `updateOrCreate` pada `SubmissionController@store` menjadi `create()`, lalu mahasiswa mengumpulkan tugas sebanyak dua kali pada penugasan yang sama.
* **Hasil Pengamatan:** Pengumpulan kedua menghasilkan error database:  
  `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry ... for key 'submissions_assignment_id_user_id_unique'`.
* **Kesimpulan:** Constraint *composite unique* `['assignment_id', 'user_id']` dari migrasi database berhasil melindungi integritas data. Penggunaan `updateOrCreate` wajib diterapkan agar pengumpulan ulang memperbarui rekaman yang ada alih-alih mencoba membuat baris baru.

---

### 8. Hapus satu submission dari database lewat SQL langsung, cek folder storage
* **Langkah Uji:** Menghapus baris data submission langsung melalui kueri SQL `DELETE FROM submissions WHERE id=1;`, kemudian memeriksa folder `storage/app/private/submissions/`.
* **Hasil Pengamatan:** Baris data di MySQL terhapus, tetapi berkas fisik dokumen di harddisk masih tetap tertinggal.
* **Kesimpulan:** Terjadi **Berkas Yatim (*Orphan Files*)**. Penghapusan langsung di level database (termasuk *foreign key cascade*) tidak memicu event model Eloquent (`static::deleting`). Berkas fisik hanya otomatis terhapus jika penghapusan dilakukan melalui Eloquent model `$submission->delete()`.

---

## 3. CHECKPOINT MINGGU 09 — Evaluasi Pemahaman Mandiri

Berikut jawaban komprehensif atas instrumen evaluasi materi Minggu 09:

### 1. Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya.

* **Alasan Keamanan:**  
  Direktori `storage/app/public` terhubung langsung ke web root via symlink `public/storage`. Berkas yang disimpan di sana dapat diakses langsung oleh peramban tanpa autentikasi, tanpa middleware sesi, dan tanpa evaluasi Policy.
* **Peragaan Kebocoran:**  
  1. Letakkan berkas jawaban mahasiswa di `storage/app/public/submissions/2/k7xjWh5EDJNAsyfFTWYt4kgaPwfdntLLfRWiRAOv.pdf`.
  2. Buka jendela *Incognito* pada peramban (tanpa login).
  3. Akses tautan: `http://kampuslms-kelompok-02.test/storage/submissions/2/k7xjWh5EDJNAsyfFTWYt4kgaPwfdntLLfRWiRAOv.pdf`.
  4.  Berkas langsung terunduh secara bebas tanpa login. Sebaliknya, pada disk `local` (`storage/app/private`), akses langsung URL `/storage/submissions/...` menghasilkan **HTTP 403 (Forbidden)** karena controller `ServeFile` di Laravel 12 memblokir akses berkas privat yang tidak memiliki *Signed URL*, sehingga berkas hanya dapat diakses melalui rute controller berotorisasi (`Gate::authorize`).

---

### 2. Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?

* **Alasan Penamaan Acak:**  
  Nama berkas asli dari pengguna merupakan masukan yang tidak terpercaya (*untrusted input*). Method `$file->store()` otomatis menghasilkan string hash acak 40 karakter.
* **Serangan yang Dicegah:**
  1. **Path Traversal (*Directory Traversal*):** Mencegah penyerang memberi nama berkas manipulatif seperti `../../../../etc/passwd` atau `../../.env` untuk menimpa file konfigurasi server.
  2. **File Overwriting (Tabrakan Berkas):** Mencegah berkas milik Mahasiswa A tertimpa ketika Mahasiswa B mengunggah file dengan nama yang sama persis (misal `tugas1.pdf`).
  3. **Direct File Guessing:** Mencegah pengguna menebak URL fisik berkas mahasiswa lain di server.

---

### 3. Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?

* **Perbedaan:**
  * **`extensions`:** Hanya mencocokkan teks ekstensi di ujung nama file (misal huruf `pdf`). Sangat rentan karena berkas berbahaya `script.php` dapat diakali cukup dengan mengubah namanya menjadi `script.pdf`.
  * **`mimes`:** Memeriksa struktur biner konten asli (*Magic Bytes*) menggunakan modul PHP `finfo` (MIME sniffing).
* **Mana yang Lebih Aman:**  
  **`mimes` jauh lebih aman**, karena meskipun penyerang mengganti nama skrip PHP menjadi `laporan.pdf`, aturan `mimes:pdf` akan mendeteksi MIME aslinya sebagai `text/x-php` dan langsung membatalkan unggahan demi mencegah serangan *Remote Code Execution* (RCE).

---

### 4. `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?

* **Perhitungan Ukuran:**  
  Satuan validasi berkas di Laravel adalah **Kilobyte (KB)**:  
  $$\text{Ukuran} = \frac{5120\text{ KB}}{1024} = \mathbf{5\text{ Megabyte (MB)}}$$
* **Faktor Pembatal di Level Server:**  
  Batas validasi Laravel ini menjadi tidak berguna jika direktif PHP pada `php.ini` menyetel batas yang lebih kecil:
  1. **`upload_max_filesize`:** Batas ukuran maksimum per berkas unggahan (misal `2M`).
  2. **`post_max_size`:** Batas total ukuran payload HTTP POST (misal `4M`).  
  Jika berkas 4 MB diunggah pada konfigurasi tersebut, server PHP akan langsung menolaknya sebelum siklus request Laravel dimulai.

---

### 5. Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain.

Pencegahan diterapkan secara berlapis pada Controller dan Policy:

1. **Di Controller ([app/Http/Controllers/SubmissionController.php:162](file:///d:/PERKULIAHAN/semester%205/PB-Pemrograman%20Web/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php#L162)):**
   ```php
   public function download(Submission $submission)
   {
       Gate::authorize('download', $submission); // Memeriksa izin Policy

       if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
           return back()->with('error', 'Berkas submission tidak ditemukan pada penyimpanan server.');
       }

       return Storage::disk('local')->download($submission->file_path, $submission->original_name);
   }
   ```

2. **Di Policy ([app/Policies/SubmissionPolicy.php:119-124](file:///d:/PERKULIAHAN/semester%205/PB-Pemrograman%20Web/kampuslms-kelompok-02/app/Policies/SubmissionPolicy.php#L119-L124)):**
   ```php
   public function download(User $user, Submission $submission): bool
   {
       return $this->view($user, $submission);
   }
   ```
   Dan aturan pada method `view()` di baris ke-54:
   ```php
   if ($user->role === 'mahasiswa') {
       return $user->id === $submission->user_id; // Hanya pemilik sah yang diizinkan!
   }
   ```
   Jika Mahasiswa A mencoba mengunduh submission milik Mahasiswa B, sistem langsung melempar pengecualian otorisasi berupa **HTTP 403 Forbidden**.

---

### 6. Kenapa `is_late` dihitung di server, bukan dikirim dari browser?

* **Alasan Keamanan (*Tampering Prevention*):**  
  Waktu pada perangkat klien (browser) sangat mudah dimanipulasi oleh pengguna (misalnya memundurkan jam sistem operasi laptop atau memodifikasi nilai form payload HTTP via Burp Suite/Postman).
* **Solusi Server-Side ([app/Http/Controllers/SubmissionController.php:55](file:///d:/PERKULIAHAN/semester%205/PB-Pemrograman%20Web/kampuslms-kelompok-02/app/Http/Controllers/SubmissionController.php#L55)):**  
  Penentuan status keterlambatan dihitung secara mutlak dan objektif menggunakan jam resmi server:
  ```php
  $isLate = now()->greaterThan($assignment->due_at);
  ```
  Dengan cara ini, manipulasi waktu dari sisi klien tidak akan memengaruhi integritas pencatatan status keterlambatan tugas.

---

## 4. VERIFIKASI PENGUJIAN OTOMATIS (Automated Test Suite)

Untuk menjamin seluruh fungsionalitas dan keamanan berkas privat memenuhi standar industri, saya telah menambahkan rangkaian pengujian otomatis fitur penyimpanan pada `tests/Feature/FileStorageTest.php`:

```bash
php artisan test --filter=FileStorageTest
```

**Hasil Pengujian (100% Passed):**
```text
   PASS  Tests\Feature\FileStorageTest
  ✓ submission stored in private disk with randomized filename                                                   11.70s  
  ✓ submission validation rejects disallowed mimes and over 5mb                                                  0.48s  
  ✓ resubmission uses update or create and deletes old physical file                                             0.29s  
  ✓ submission calculates is late server side                                                                    0.42s  
  ✓ submission rejected when past due and allow late is false                                                    0.19s  
  ✓ submission download authorizes policy and streams file                                                       1.11s  
  ✓ physical file deleted when submission record is deleted                                                      0.33s  
  ✓ material file upload download and deletion                                                                   0.39s  

  Tests:    8 passed (41 assertions)
  Duration: 16.62s
```
Semua aspek kepatuhan modul praktikum Minggu 09 (penyimpanan privat, nama acak, validasi ukuran/MIME, penanganan resubmission, kalkulasi `is_late`, proteksi anti-IDOR, dan penghapusan berkas fisik) telah terverifikasi bekerja sempurna.
