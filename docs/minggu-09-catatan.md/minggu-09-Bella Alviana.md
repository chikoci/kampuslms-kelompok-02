# Minggu 9
**NAMA:** Bella Alviana  
**NIM:** 10241015  

---

## READ

**1. Temukan berkas fisiknya di sistem berkas. Path lengkapnya apa?**  
Jawab:  
Pada penambahan fitur pengelolaan berkas di KampusLMS, berkas materi kuliah yang diunggah dosen (`MaterialController@store`) dan berkas tugas mahasiswa (`SubmissionController@store`) disimpan ke dalam disk privat `local` menggunakan `$file->store()`.

Di Laravel 12, disk `local` berakar pada direktori `storage/app/private`.
* **Path relatif berkas:**
  * Berkas materi: `storage/app/private/materials/{course_id}/{hash_acak}.pdf`
  * Berkas tugas: `storage/app/private/submissions/{assignment_id}/{hash_acak}.pdf`
* **Path fisik lengkap di hard disk Windows:**
  ```text
  D:\kampuslms-kelompok-02\storage\app\private\submissions\16\EzB1m4120ZDB8GJXjIYPz2aIY42DSzGwvGup7EoT.pdf
  ```
Berkas fisik disimpan dengan penamaan acak hash kriptografis 40 karakter di dalam folder `private` yang posisinya berada di luar Document Root web server.

---

**2. Buka baris database yang bersangkutan. Bandingkan `file_path` dengan lokasi fisiknya.**  
Jawab:  
Ketika data diperiksa pada tabel `submissions` atau `materials` di database menggunakan Tinker:
```php
$sub = App\Models\Submission::latest()->first();
```
* **Nilai pada kolom `file_path` di database:**
  ```text
  submissions/16/EzB1m4120ZDB8GJXjIYPz2aIY42DSzGwvGup7EoT.pdf
  ```
* **Nilai pada kolom `original_name` di database:**
  ```text
  Laporan_Tugas_Bella.pdf
  ```
* **Lokasi fisik berkas di hard disk:**
  ```text
  D:\kampuslms-kelompok-02\storage\app\private\submissions\16\EzB1m4120ZDB8GJXjIYPz2aIY42DSzGwvGup7EoT.pdf
  ```

**Perbandingan & Analisis:**  
Database **hanya mencatat path relatif** terhadap root disk penyimpanan, bukan path absolut (`D:\...`). Hal ini menjaga portabilitas aplikasi agar saat dideploy ke server Linux produksi (misalnya `/var/www/kampuslms/storage/app/private`), path di database tidak rusak. Selain itu, pemisahan kolom `original_name` memastikan nama asli berkas dari pengguna tetap dapat dipulihkan saat berkas diunduh kembali, meskipun nama fisik berkas di hard disk diacak demi keamanan.

---

**3. Coba akses berkas itu langsung lewat browser dengan menebak URL-nya. Berhasil atau tidak? Kenapa?**  
Jawab:  
Ketika mencoba membuka URL berkas secara langsung di browser:
```text
http://127.0.0.1:8000/storage/submissions/16/EzB1m4120ZDB8GJXjIYPz2aIY42DSzGwvGup7EoT.pdf
```
* **Hasil:** **Akses Gagal / Ditolak (HTTP 403 Forbidden - Akses Ditolak).**
* **Kenapa hal ini terjadi:**  
  Web server hanya menjadikan folder `public/` sebagai Document Root. Folder `storage/app/private/` berada di luar root web server dan tidak memiliki tautan simbolik publik. Pada Laravel 12, disk `local` dilengkapi konfigurasi `'serve' => true`. Ketika URL `/storage/...` mengarah ke berkas privat diakses tanpa izin otorisasi atau signed URL, Laravel otomatis mencegat dan menolak akses dengan respons 403. Berkas privat **hanya dapat diakses melalui Controller + Policy** menggunakan `Storage::disk('local')->download()`.

---

**4. Buka `config/filesystems.php`. Identifikasi disk `local` dan `public` — apa persis bedanya?**  
Jawab:  
Pada berkas `config/filesystems.php`, terdapat konfigurasi dua disk utama:
```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root'   => storage_path('app/private'),
        'serve'  => true,
        'throw'  => false,
    ],

    'public' => [
        'driver'     => 'local',
        'root'       => storage_path('app/public'),
        'url'        => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw'      => false,
    ],
]
```

**Tabel Perbedaan Persis Disk `local` dan `public`:**

| Parameter | Disk `local` (Privat) | Disk `public` (Publik) |
|---|---|---|
| **Lokasi Root Direktori** | `storage/app/private` | `storage/app/public` |
| **Akses Langsung Browser** | Tidak bisa (di luar Document Root) | Bisa melalui symlink `public/storage` |
| **Konfigurasi URL** | Tidak ada URL publik | Memiliki URL `'url' => env('APP_URL').'/storage'` |
| **Mekanisme Akses** | Wajib melewati Route + Controller + Gate/Policy | Langsung lewat URL berkas di peramban tanpa login |
| **Penggunaan di KampusLMS** | Materi perkuliahan dosen, pengumpulan tugas mahasiswa | Foto profil user, logo instansi, aset terbuka |

---

**5. Jalankan `php artisan storage:link`. Apa yang dibuatnya? Kenapa materi Anda tidak boleh ada di sana?**  
Jawab:  
Perintah `php artisan storage:link` membuat sebuah **tautan simbolis (*symbolic link* / Junction)** dari folder `public/storage` yang mengarah ke folder fisik `storage/app/public`:
```text
public/storage  ───(symlink)───►  storage/app/public
```
**Kenapa materi dan submission TIDAK BOLEH ditaruh di sana?**  
Karena web server akan melayani setiap berkas di dalam `public/storage` secara langsung tanpa menyentuh PHP. Permintaan berkas tidak akan melewati filter route `routes/web.php`, tidak melewati middleware `auth`, dan tidak mengecek otorisasi `Gate::authorize()` / `Policy`. Siapa pun yang memiliki atau menebak tautan berkas tersebut dapat mengunduh materi perkuliahan berbayar dan lembar jawaban tugas teman sekelasnya tanpa perlu login.

---

## BREAK

**1. Pindahkan berkas submission ke `storage/app/public`, akses URL-nya tanpa login**  
Jawab:  

* **Prediksi:** Jika berkas disimpan di disk publik, berkas tugas mahasiswa dapat dibuka langsung dari browser mode Incognito tanpa perlu login.
* **Langkah Percobaan:**
  1. Mengubah sementara baris penyimpanan di `SubmissionController.php` dari disk `'local'` menjadi `'public'`:
     ```php
     $filePath = $file->store('submissions/' . $assignment->id, 'public');
     ```
  2. Mengunggah satu berkas tugas baru di browser sebagai mahasiswa.
  3. Mengambil path berkas fisik yang dihasilkan melalui Tinker:
     ```powershell
     php artisan tinker --execute="echo App\Models\Submission::latest()->first()->file_path;"
     ```
     *(Misalkan menghasilkan: `submissions/16/uji_bocor_123.pdf`)*.
  4. Membuka jendela **Incognito (tanpa login)** dan mengakses URL:
     ```text
     http://127.0.0.1:8000/storage/submissions/16/uji_bocor_123.pdf
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  Berkas PDF tugas **langsung terbuka dan terbaca bebas di layar browser dengan status `HTTP 200 OK`**. Web server sama sekali tidak meminta login dan tidak melakukan pengecekan hak akses.
* **Penjelasan / Analisis:**  
  Menyimpan berkas privat di dalam `storage/app/public` merusak proteksi otorisasi secara total. Berkas tugas yang seharusnya hanya bisa dilihat oleh mahasiswa bersangkutan dan dosen pengampu menjadi terbuka untuk seluruh publik di internet.

---

**2. Hapus `Gate::authorize()` dari endpoint download, akses submission orang lain**  
Jawab:  

* **Prediksi:** Mahasiswa A dapat mengunduh berkas jawaban milik Mahasiswa B hanya dengan mengganti nomor ID pada URL unduhan (terjadi celah IDOR).
* **Langkah Percobaan:**
  1. Mengomentari baris `Gate::authorize` pada method `download()` di `SubmissionController.php`:
     ```php
     public function download(Submission $submission)
     {
         // Gate::authorize('download', $submission);
         ...
         return Storage::disk('local')->download($submission->file_path, $submission->original_name);
     }
     ```
  2. Menyiapkan akun Mahasiswa B yang sudah mengumpulkan tugas (misal ID submission = `2`).
  3. Login ke browser sebagai **Mahasiswa A**.
  4. Mengakses langsung URL unduhan milik Mahasiswa B:
     ```text
     http://127.0.0.1:8000/submissions/2/download
     ```
* **Hasil Sebenarnya / Pengamatan:**  
  * **Ketika `Gate::authorize` dimatikan:** Mahasiswa A langsung berhasil mengunduh berkas tugas milik Mahasiswa B (`HTTP 200 OK`).
  * **Ketika `Gate::authorize` diaktifkan kembali:** Sistem langsung menghentikan permintaan dengan respon **`HTTP 403 Forbidden`** (*This action is unauthorized*).
* **Penjelasan / Analisis:**  
  Ini membuktikan kerentanan **IDOR (*Insecure Direct Object Reference*) versi berkas**. Validasi otorisasi di level controller sebelum `Storage::download()` dipanggil adalah garis pertahanan wajib untuk memastikan pengunduh adalah pemilik sah berkas, dosen pengampu, atau admin.

---

**3. Simpan berkas dengan `storeAs()` memakai nama asli, lalu dua akun mahasiswa berbeda mengunggah `tugas.pdf` dengan isi berbeda ke tugas yang sama**  
Jawab:  

* **Prediksi:** Berkas fisik milik mahasiswa pertama di hard disk server akan tertimpa dan terhapus oleh berkas mahasiswa kedua karena memiliki nama yang sama.
* **Langkah Percobaan:**
  1. Mengubah sementara kode simpan di `SubmissionController.php` menjadi:
     ```php
     $filePath = $file->storeAs('submissions/' . $assignment->id, $file->getClientOriginalName(), 'local');
     ```
  2. Menyiapkan dua file PDF di komputer dengan nama sama `tugas.pdf`:
     * File 1: Berisi teks *"Jawaban Asli Mahasiswa 1"*.
     * File 2: Berisi teks *"Jawaban Milik Mahasiswa 2 - Berbeda Isi"*.
  3. Login sebagai Mahasiswa 1, kumpulkan berkas `tugas.pdf` pertama pada Tugas 16.
  4. Memeriksa isi file fisik di `storage/app/private/submissions/16/tugas.pdf` (berisi data Mahasiswa 1).
  5. Logout, lalu login sebagai Mahasiswa 2, kumpulkan berkas `tugas.pdf` kedua pada Tugas 16 yang sama.
  6. Membuka kembali file fisik `storage/app/private/submissions/16/tugas.pdf`.
* **Hasil Sebenarnya / Pengamatan:**  
  Isi berkas fisik `tugas.pdf` di server berubah total menjadi milik Mahasiswa 2. Berkas lembar jawaban Mahasiswa 1 **tertimpa dan hilang permanen dari server**.
* **Penjelasan / Analisis:**  
  Terjadi masalah tabrakan nama (*File Overwrite Collision*). Nama berkas asli dari pengguna adalah input yang tidak terpercaya. Menggunakan bawaan `$file->store()` yang membuat nama acak kriptografis mencegah tabrakan berkas antar mahasiswa dan menutup celah manipulasi nama berkas seperti *Path Traversal* (`../../test.txt`).

---

**4. Unggah berkas PHP yang diganti namanya jadi `.pdf` dengan validasi `extensions`, lalu dengan `mimes`**  
Jawab:  

* **Prediksi:** Validasi `extensions` akan tertipu oleh nama file `.pdf`, sedangkan validasi `mimes` akan menggagalkan unggahan karena memeriksa isi berkas sebenarnya.
* **Langkah Percobaan:**
  1. Membuat berkas teks skrip PHP bernama `uji.php` berisi:
     ```php
     <?php echo "Uji script backdoor PHP"; ?>
     ```
  2. Mengganti nama berkas tersebut menjadi `uji.pdf`.
  3. **Uji Kasus A (Validasi `extensions`):**  
     Mengatur validasi controller: `'file' => ['required', 'file', 'extensions:pdf']`.  
     Mengunggah berkas `uji.pdf`.
  4. **Uji Kasus B (Validasi `mimes`):**  
     Mengatur validasi controller: `'file' => ['required', 'file', 'mimes:pdf']`.  
     Mencoba mengunggah berkas `uji.pdf` yang sama.
* **Hasil Sebenarnya / Pengamatan:**  
  * **Pada Kasus A (`extensions`):** Berkas **lolos validasi** dan berhasil tersimpan di server.
  * **Pada Kasus B (`mimes`):** Berkas **langsung ditolak** oleh Laravel dengan pesan error validasi: *"Format berkas harus berupa PDF, DOC, atau DOCX."*
* **Penjelasan / Analisis:**  
  Aturan `extensions` hanya memeriksa teks ekstensi di ujung nama file. Sebaliknya, aturan `mimes` membaca *Magic Bytes* (header biner isi file) menggunakan PHP Fileinfo. Karena isi berkas `uji.pdf` terbaca sebagai teks PHP (`text/x-php`), berkas ditolak mentah-mentah sehingga mencegah eksekusi kode berbahaya (*Remote Code Execution*).

---

**5. Ubah `max:5120` menjadi `max:5`, unggah berkas 1 MB**  
Jawab:  

* **Prediksi:** Berkas 1 MB akan ditolak karena sistem mengira batas maksimalnya adalah 5 KB, bukan 5 MB.
* **Langkah Percobaan:**
  1. Mengubah aturan validasi ukuran di controller menjadi:
     ```php
     'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5']
     ```
  2. Menyiapkan berkas dokumen PDF normal berukuran 1 MB (1.024 KB).
  3. Mencoba mengunggah berkas 1 MB tersebut pada formulir pengumpulan tugas.
* **Hasil Sebenarnya / Pengamatan:**  
  Formulir langsung gagal dikirim dan menampilkan pesan kesalahan:  
  *`The file field must not be greater than 5 kilobytes.`*
* **Penjelasan / Analisis:**  
  Satuan parameter `max` untuk berkas di Laravel adalah **Kilobyte (KB)**, bukan Megabyte (MB). Aturan `max:5120` berarti $5120\text{ KB} = 5\text{ MB}$. Kesalahan tafsir programmer yang menulis `max:5` menyebabkan batas unggahan menjadi hanya 5 KB (5.120 byte), sehingga dokumen normal mahasiswa ditolak.

---

**6. Set `upload_max_filesize=1M` di `php.ini`, unggah berkas 3 MB dengan validasi `max:5120`**  
Jawab:  

* **Prediksi:** Pengunggahan berkas 3 MB akan gagal di gerbang server PHP sebelum kode validasi `max:5120` Laravel sempat dijalankan.
* **Langkah Percobaan:**
  1. Membuka file `php.ini` (`C:\Users\Lenovo\.config\herd\bin\php84\php.ini`), mengubah konfigurasi menjadi:
     ```ini
     upload_max_filesize = 1M
     post_max_size = 8M
     ```
  2. Menyimpan file dan me-restart server `php artisan serve`.
  3. Memastikan validasi di Laravel tetap `max:5120` (5 MB).
  4. Mengunggah berkas PDF berukuran 3 MB di halaman tugas.
* **Hasil Sebenarnya / Pengamatan:**  
  Berkas langsung ditolak oleh runtime PHP. PHP membuang aliran file dan menghasilkan status error `UPLOAD_ERR_INI_SIZE` (kode error 1). Objek `$request->file('file')` menjadi `null` atau `invalid` di Laravel, dan pengguna menerima pesan bahwa berkas tidak dipilih atau melebihi batas server.
* **Penjelasan / Analisis:**  
  **Konfigurasi server runtime (PHP/Nginx) selalu mengalahkan validasi aplikasi Laravel.** Validasi `max:5120` tidak berguna jika batas di `php.ini` lebih kecil. Nilai `upload_max_filesize` dan `post_max_size` di server harus selalu disetel lebih besar atau sama dengan batas validasi form aplikasi.

---

**7. Hapus `updateOrCreate`, ganti `create`, kumpulkan tugas dua kali**  
Jawab:  

* **Prediksi:** Pengumpulan tugas kedua akan memicu error database karena menabrak aturan unik gabungan (*composite unique key*) antara assignment_id dan user_id.
* **Langkah Percobaan:**
  1. Mengubah method simpan di `SubmissionController.php` dari `updateOrCreate` menjadi `create()`:
     ```php
     $submission = $assignment->submissions()->create([
         'user_id'       => $userId,
         'file_path'     => $filePath,
         'original_name' => $originalName,
         'file_size'     => $fileSize,
         'submitted_at'  => now(),
         'is_late'       => $isLate,
     ]);
     ```
  2. Login sebagai mahasiswa, kumpulkan tugas pertama kali (berhasil tersimpan).
  3. Mengumpulkan kembali berkas revisi pada tugas yang sama tanpa menghapus data sebelumnya.
* **Hasil Sebenarnya / Pengamatan:**  
  Aplikasi langsung mengalami crash dan menampilkan layar error HTTP 500 (*QueryException*):  
  ```text
  SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '16-3' for key 'submissions_assignment_id_user_id_unique'
  ```
* **Penjelasan / Analisis:**  
  Constraint database `$table->unique(['assignment_id', 'user_id']);` yang dibuat pada migrasi Minggu 3 berhasil menyelamatkan integritas database dari duplikasi data saat controller memiliki bug. Method `updateOrCreate` wajib digunakan agar pengumpulan ulang menggantikan baris data lama, bukan menumpuk baris baru yang merusak relasi penilaian.

---

**8. Hapus satu submission dari database lewat SQL langsung, cek folder storage**  
Jawab:  

* **Prediksi:** Baris data di database akan hilang, namun berkas fisik dokumen di hard disk server tetap tertinggal (menjadi berkas yatim).
* **Langkah Percobaan:**
  1. Mengunggah satu tugas baru, lalu mencatat ID dan `file_path`-nya lewat Tinker:
     ```php
     $sub = App\Models\Submission::latest()->first();
     [$sub->id, $sub->file_path];
     ```
  2. Memastikan berkas fisiknya ada di hard disk (`storage/app/private/submissions/...`).
  3. Menghapus baris data submission tersebut langsung lewat kueri SQL mentah di Tinker:
     ```php
     DB::delete('DELETE FROM submissions WHERE id = ?', [$sub->id]);
     ```
  4. Memeriksa kembali folder penyimpanan fisik di hard disk.
* **Hasil Sebenarnya / Pengamatan:**  
  Data di tabel `submissions` sudah terhapus menjadi 0 baris, **tetapi berkas fisiknya masih tetap utuh di hard disk**.
* **Penjelasan / Analisis:**  
  Terjadi **Berkas Yatim (*Orphan Files*)**. Penghapusan langsung melalui kueri SQL atau foreign key `cascadeOnDelete` tidak memicu model lifecycle event Eloquent (`deleting`). Akibatnya, disk server lama-kelamaan akan dipenuhi oleh berkas sampah tanpa pemilik. Berkas fisik harus selalu dihapus secara eksplisit menggunakan `Storage::disk('local')->delete()` sebelum record-nya dihapus dari basis data.

---

## CHECKPOINT

**1. Kenapa submission tidak boleh disimpan di `storage/app/public`? Peragakan kebocorannya.**  
Jawab:  
Folder `storage/app/public` terhubung ke Document Root web server melalui symlink `public/storage`. Web server menyajikan berkas di sana secara statis dan langsung melewati seluruh lapisan otentikasi dan otorisasi Laravel (tanpa login, tanpa middleware, dan tanpa Policy). Siapa pun yang mengetahui URL berkas dapat langsung mengunduhnya.

*Peragaan Kebocoran:*
1. Simpan berkas di disk publik: ubah `$file->store('submissions/' . $assignment->id, 'local')` menjadi `'public'`.
2. Unggah satu berkas tugas sebagai mahasiswa.
3. Ambil path berkasnya lewat Tinker (misal: `submissions/16/tugas_bocor.pdf`).
4. Buka peramban di mode Incognito (tanpa login sama sekali).
5. Masukkan URL: `http://127.0.0.1:8000/storage/submissions/16/tugas_bocor.pdf`.
6. Berkas PDF langsung terbuka dan terbaca bebas di peramban (`HTTP 200 OK`). Sebaliknya, jika disimpan di `storage/app/private`, URL tersebut menghasilkan **HTTP 403 Forbidden** karena wajib diunduh melewati controller yang dilindungi Policy.

---

**2. Kenapa nama berkas fisik dibuat acak? Serangan apa yang dicegah?**  
Jawab:  
Nama berkas asli dari komputer pengguna (`getClientOriginalName()`) adalah masukan yang tidak terpercaya (*untrusted input*). Method bawaan Laravel `$file->store()` selalu menghasilkan string hash acak 40 karakter demi menjaga keamanan penyimpanan fisik di hard disk server.

*Serangan dan masalah yang dicegah:*
1. **Path Traversal:** Penyerang mengirim nama berkas seperti `../../.env` atau `../../public/shell.php` untuk menimpa file penting server atau meletakkan webshell.
2. **File Overwrite Collision:** Mencegah tertimpanya berkas mahasiswa lain jika mengunggah dokumen dengan nama yang sama (misal `tugas.pdf`).
3. **File Scraping / Enumeration:** Mencegah pihak luar menebak nama file mahasiswa lain secara berurutan untuk menyedot jawaban tugas.

---

**3. Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?**  
Jawab:  
* **Perbedaan:**
  * `extensions`: Hanya membaca teks ekstensi nama berkas di ujung string (contoh: teks `.pdf`).
  * `mimes`: Membaca struktur byte awal (*Magic Bytes*) di dalam berkas menggunakan ekstensi PHP Fileinfo untuk memastikan tipe MIME sesungguhnya.
* **Mana yang lebih aman:**  
  Aturan **`mimes` jauh lebih aman**. Jika penyerang menyamarkan skrip PHP berbahaya menjadi `skrip.pdf`, aturan `extensions` akan tertipu dan meloloskannya, sedangkan aturan `mimes` membaca *magic bytes* internal file, mendeteksi tipe aslinya sebagai `text/x-php`, dan langsung menolaknya sehingga mencegah serangan *Remote Code Execution*.

---

**4. `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?**  
Jawab:  
Satuan parameter `max` untuk berkas di Laravel adalah Kilobyte (KB).
$$\frac{5120 \text{ KB}}{1024} = \mathbf{5 \text{ MB}}$$
Maka `max:5120` berarti batas maksimalnya adalah **5 Megabyte (5 MB)**.

*Yang membatalkan batas ini di level server:*  
Konfigurasi runtime server dieksekusi sebelum Laravel berjalan. Batas aplikasi dapat batal jika:
1. `upload_max_filesize` di `php.ini` disetel lebih kecil (misalnya `1M` atau `2M`).
2. `post_max_size` di `php.ini` bernilai lebih kecil dari ukuran berkas yang dikirim.
3. `client_max_body_size` pada web server Nginx bernilai terlalu kecil (misalnya bawaan default `1M`).

---

**5. Tunjukkan di kode Anda apa yang mencegah mahasiswa mengunduh submission mahasiswa lain.**  
Jawab:  
Pencegahan IDOR unduhan submission dilindungi oleh **Gate** di controller dan **Policy** di model:

1. **Di Controller (`SubmissionController.php` method `download`):**
   ```php
   public function download(Submission $submission)
   {
       Gate::authorize('download', $submission);

       if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
           return back()->with('error', 'Berkas submission tidak ditemukan pada penyimpanan server.');
       }

       return Storage::disk('local')->download($submission->file_path, $submission->original_name);
   }
   ```
2. **Di Policy (`SubmissionPolicy.php` method `download` & `view`):**
   ```php
   public function download(User $user, Submission $submission): bool
   {
       return $this->view($user, $submission);
   }

   public function view(User $user, Submission $submission): bool
   {
       if ($user->role === 'admin') return true;
       if ($user->role === 'dosen') return $user->id === $submission->assignment->course->lecturer_id;
       
       // Mahasiswa HANYA boleh mengunduh jika ia adalah pemilik sah berkas tersebut
       if ($user->role === 'mahasiswa') {
           return $user->id === $submission->user_id;
       }

       return false;
   }
   ```
Jika Mahasiswa A mencoba mengunduh submission milik Mahasiswa B, kondisi `$user->id === $submission->user_id` bernilai `false`, dan Laravel seketika menghentikan permintaan dengan respon **`HTTP 403 Forbidden`**.

---

**6. Kenapa `is_late` dihitung di server, bukan dikirim dari browser?**  
Jawab:  
Lingkungan peramban (*client-side*) berada sepenuhnya di bawah kendali pengguna dan tidak boleh dipercaya (*Zero Trust*):
1. **Manipulasi Jam Klien:** Jam di laptop mahasiswa dapat diatur mundur dengan mudah melalui pengaturan tanggal sistem operasi. Jika aplikasi mempercayai waktu dari browser, mahasiswa yang terlambat dapat mengelabui sistem agar dianggap tepat waktu.
2. **Pencegahan Manipulasi Form:** Jika status keterlambatan dikirim dari form (misalnya input hidden `is_late`), mahasiswa dapat mengedit nilai payload menggunakan Inspect Element, Postman, atau curl menjadi `is_late = false` meskipun batas waktu penugasan telah berakhir.
3. **Integritas Waktu Server:** Server memiliki waktu acuan tunggal yang tersinkronisasi melalui protokol NTP (*Network Time Protocol*). Perhitungan pada controller:
   ```php
   $isLate = now()->greaterThan($assignment->due_at);
   ```
   Menjamin kepastian tenggat waktu yang adil, seragam, dan mustahil dimanipulasi dari sisi klien.
