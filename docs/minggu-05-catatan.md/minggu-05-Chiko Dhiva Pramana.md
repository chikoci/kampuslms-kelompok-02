# Catatan Praktikum Minggu 5

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ:

Pada tahap ini, saya memeriksa peta rute yang saat ini sudah terdaftar di aplikasi KampusLMS kelompok kami dengan menjalankan perintah `php artisan route:list --except-vendor`. 

Tujuannya adalah mengidentifikasi rute-rute yang menerima parameter model (seperti `{course}` dan `{user}`) serta menganalisis siapa yang berhak mengaksesnya dan bagaimana proteksi keamanannya saat ini.

---

### 1. Daftar Rute Aplikasi Saat Ini

Berikut salinan keluaran dari terminal:

```text
+-----------+-----------------------+-----------------+------------------------------------------+------------+
| Method    | URI                   | Name            | Action                                   | Middleware |
+-----------+-----------------------+-----------------+------------------------------------------+------------+
| GET|HEAD  | /                     | home            | Closure (routes/web.php:8)               | web        |
| GET|HEAD  | tentang               | tentang         | App\Http\Controllers\TentangController   | web        |
| GET|HEAD  | courses               | courses.index   | App\Http\Controllers\CourseController    | web        |
| POST      | courses               | courses.store   | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/create        | courses.create  | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/{course}      | courses.show    | App\Http\Controllers\CourseController    | web        |
| PUT|PATCH | courses/{course}      | courses.update  | App\Http\Controllers\CourseController    | web        |
| DELETE    | courses/{course}      | courses.destroy | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/{course}/edit | courses.edit    | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | users                 | users.index     | App\Http\Controllers\UserController      | web        |
| POST      | users                 | users.store     | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/create          | users.create    | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/{user}          | users.show      | App\Http\Controllers\UserController      | web        |
| PUT|PATCH | users/{user}          | users.update    | App\Http\Controllers\UserController      | web        |
| DELETE    | users/{user}          | users.destroy   | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/{user}/edit     | users.edit      | App\Http\Controllers\UserController      | web        |
+-----------+-----------------------+-----------------+------------------------------------------+------------+
```

Total ada 16 rute aktif di sistem.

---

### 2. Penandaan Rute Berparameter Model

Dari daftar di atas, terdapat 8 rute yang menerima parameter model:

* **Parameter `{course}` (Modul Mata Kuliah):**
  1. `GET courses/{course}` (`courses.show`)
  2. `GET courses/{course}/edit` (`courses.edit`)
  3. `PUT|PATCH courses/{course}` (`courses.update`)
  4. `DELETE courses/{course}` (`courses.destroy`)

* **Parameter `{user}` (Modul Pengguna):**
  1. `GET users/{user}` (`users.show`)
  2. `GET users/{user}/edit` (`users.edit`)
  3. `PUT|PATCH users/{user}` (`users.update`)
  4. `DELETE users/{user}` (`users.destroy`)

---

### 3. Analisis Hak Akses & Proteksi Saat Ini

Saya menganalisis setiap rute bertanda di atas berdasarkan dua pertanyaan modul:

1. **Siapa saja yang seharusnya boleh mengaksesnya?**
   * **Rute `{user}` (edit/update/destroy):** Seharusnya **hanya Admin** yang boleh mengelola data pengguna lain. Pengguna biasa maksimal hanya boleh mengedit profil dirinya sendiri.
   * **Rute `{course}` (edit/update/destroy):** Seharusnya **hanya Admin** atau **Dosen pengampu mata kuliah bersangkutan** yang boleh mengubah atau menghapus data tersebut. Dosen lain maupun mahasiswa tidak boleh menyentuhnya.
   * **Rute `{course}` (show):** Boleh dilihat oleh Admin, Dosen pengampu, dan Mahasiswa yang terdaftar (enrolled) di kelas tersebut.

2. **Apa yang saat ini mencegah orang lain mengaksesnya?**
   * **Jawabannya: Belum ada apa-apa sama sekali!**
   * Seluruh rute di atas saat ini masih bersifat terbuka bebas (hanya memakai middleware bawaan `web`).
   * Belum ada middleware `auth` untuk mewajibkan login, belum ada middleware `role` untuk membatasi peran, dan di dalam controller belum ada validasi kepemilikan (`abort_unless` / Policy).
   * Siapapun yang mengetahui atau menebak URL (misal membuka `/courses/2/edit` atau `/users/1/edit`), bisa langsung melihat, mengubah, bahkan menghapus data milik orang lain. Inilah celah **IDOR (Insecure Direct Object Reference)** yang nyata di aplikasi kami.

---

### 4. Tabel: Daftar Titik Rawan IDOR

Tabel ini memetakan titik rawan pada aplikasi KampusLMS saat ini:

| No | Route | Parameter | Hak Akses yang Seharusnya | Kondisi Pengamanan Saat Ini | Potensi Bahaya IDOR |
|---|---|---|---|---|---|
| 1 | `courses/{course}` | `{course}` | Admin, Dosen pengampu, & Mahasiswa kelas tersebut | Terbuka bebas untuk siapa saja | Siapa saja bisa melihat silabus/detail mata kuliah internal tanpa terdaftar |
| 2 | `courses/{course}/edit` | `{course}` | Hanya Admin & Dosen pemilik mata kuliah | Terbuka bebas tanpa login | Pengguna mana pun bisa membuka form edit mata kuliah milik dosen lain |
| 3 | `courses/{course}` (PUT/PATCH) | `{course}` | Hanya Admin & Dosen pemilik mata kuliah | Tidak ada validasi kepemilikan di controller | Orang luar bisa mengganti nama, SKS, status, atau dosen pengampu mata kuliah |
| 4 | `courses/{course}` (DELETE) | `{course}` | Hanya Admin | Tidak ada validasi peran | Siapapun bisa mengirim request DELETE untuk menghapus mata kuliah dari database |
| 5 | `users/{user}` | `{user}` | Admin & Pengguna pemilik akun sendiri | Terbuka bebas untuk publik | Orang luar bisa mengintip informasi pribadi pengguna lain dengan mengganti ID |
| 6 | `users/{user}/edit` | `{user}` | Admin & Pengguna pemilik akun sendiri | Terbuka bebas tanpa autentikasi | Siapa saja bisa membuka form edit data akun orang lain |
| 7 | `users/{user}` (PUT/PATCH) | `{user}` | Admin & Pengguna pemilik akun sendiri | Tidak ada pengecekan kecocokan `user_id` | Siapa saja bisa mengganti data pengguna lain, termasuk mengubah email dan role |
| 8 | `users/{user}` (DELETE) | `{user}` | Hanya Admin | Tidak ada proteksi middleware role | Pengguna umum bisa menghapus akun pengguna lain dari sistem |

---

### Kesimpulan Tahap READ:
Dari penelusuran ini, saya memahami bahwa **Route Model Binding otomatis mengambil data dari database berdasarkan ID, namun tidak otomatis memeriksa hak akses**. 

Tanpa adanya middleware grup peran dan pengecekan kepemilikan di controller, aplikasi sangat rentan terhadap serangan IDOR karena pengguna hanya perlu mengganti angka ID pada URL untuk memanipulasi data orang lain. 

Temuan ini menjadi dasar kebutuhan perbaikan yang akan kami kerjakan di tahap BUILD (memasang middleware dan route group) serta tahap BREAK (menguji pembuktian kerusakannya).

## Bagian BREAK:

1. **Login sebagai mahasiswa A, buka submission milik mahasiswa B dengan mengubah angka ID di URL**
   - **Prediksi:** akan dapat melihat tugas mahasiswa yang lain
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka `app/Http/Controllers/SubmissionController.php`, pada method `show()` beri tanda komentar `/* ... */` pada baris validasi kepemilikan sementara (`abort_unless(...)`).
     2. Buka browser dan login sebagai Mahasiswa A (User ID 3) lewat `http://127.0.0.1:8000/dev/login-mahasiswa`.
     3. Akses submission milik sendiri di `http://127.0.0.1:8000/submissions/1`.
     4. Ubah URL menjadi ID milik Mahasiswa B di `http://127.0.0.1:8000/submissions/2`.
     5. Buka kembali `SubmissionController.php`, hapus tanda komentar `/* ... */` (aktifkan proteksi kembali), lalu refresh halaman browser.
   - **Yang Sebenarnya:**
     * **Kondisi Rusak (Validasi Dimatikan):** Halaman submission milik Mahasiswa B berhasil terbuka (HTTP 200 OK). Mahasiswa A dapat membaca catatan dan mengunduh berkas tugas mahasiswa lain. Ini adalah celah nyata IDOR (Insecure Direct Object Reference).
     * **Kondisi Aman (Setelah BUILD):** Akses langsung ditolak dengan status HTTP 403 Forbidden karena controller memeriksa kecocokan kepemilikan akun (`abort_unless(auth()->id() === $submission->user_id, 403)`).

2. **Buka nested route tugas silang (misal /courses/2/assignments/2) di mana tugas bukan milik mata kuliah tersebut**
   - **Prediksi:** akan menampilkan tugas mata kuliah lain
   - **Cara Uji (Mencoba Kondisi Rusak):**
     1. Buka file `routes/web.php`, pastikan rute pengujian tanpa scoping aktif di luar grup `scopeBindings`:
        ```php
        Route::get('/courses/{course}/assignments/{assignment}', function (\App\Models\Course $course, \App\Models\Assignment $assignment) {
            return view('assignments.show', compact('assignment'));
        });
        ```
     2. Ketahui data database: Tugas ID 2 sebenarnya adalah milik Mata Kuliah ID 1 ("Pemrograman Web").
     3. Akses tugas tersebut secara silang di bawah rute Mata Kuliah ID 2 ("Basis Data Lanjut"): `http://127.0.0.1:8000/courses/2/assignments/2`.
   - **Yang Sebenarnya:** Halaman tugas berhasil terbuka (HTTP 200 OK). Tanpa scoping, Laravel mengeksekusi `Course::findOrFail(2)` dan `Assignment::findOrFail(2)` secara terpisah tanpa memvalidasi relasi `course_id`, sehingga tugas Pemrograman Web (ID 2) bocor dan tampil di URL Basis Data Lanjut (ID 2).

3. **Aktifkan Route::scopeBindings(), lalu ulangi pengujian nomor 2**
   - **Prediksi:** akan 404 not found
   - **Cara Uji (Mencoba Kondisi Normal):**
     1. Tambahkan `->scopeBindings()` pada rute pengujian di `routes/web.php`:
        ```php
        Route::get('/courses/{course}/assignments/{assignment}', function (\App\Models\Course $course, \App\Models\Assignment $assignment) {
            return view('assignments.show', compact('assignment'));
        })->scopeBindings();
        ```
     2. Refresh atau buka kembali URL silang yang tidak berelasi tadi: `http://127.0.0.1:8000/courses/2/assignments/2`.
     3. Buka juga pasangan yang sah: `http://127.0.0.1:8000/courses/1/assignments/2`.
   - **Yang Sebenarnya:**
     * Pada URL silang `/courses/2/assignments/2`, server langsung merespons **HTTP 404 Not Found**. Laravel mencari tugas melalui relasi parent `$course->assignments()->findOrFail(2)`. Karena Tugas ID 2 memiliki `course_id = 1` (bukan 2), database menolak dan mengembalikan 404.
     * Pada URL yang sah `/courses/1/assignments/2`, halaman terbuka sukses (**HTTP 200 OK**) karena relasinya valid.

4. **Daftarkan middleware di app/Http/Kernel.php seperti tutorial lama**
   - **Prediksi:** Tidak ada, karena itu Laravel lama
   - **Cara Uji:**
     1. Periksa folder `app/Http/` di VS Code untuk mencari berkas `Kernel.php`.
     2. Jalankan perintah terminal: `Test-Path app/Http/Kernel.php`.
     3. (Opsional eksperimen): Jika membuat file `Kernel.php` manual dan mendaftarkan middleware di sana, Laravel 12 tidak akan pernah memanggilnya.
   - **Yang Sebenarnya:** File `app/Http/Kernel.php` tidak ditemukan (False). Pada Laravel 11 dan 12, arsitektur Kernel dihilangkan. Seluruh konfigurasi middleware dan alias peran sekarang didaftarkan secara terpusat di berkas `bootstrap/app.php` menggunakan `$middleware->alias([...])`.

5. **Pasang middleware role:admin pada grup rute, lalu akses sebagai akun dosen**
   - **Prediksi:** Akan 403 akses ditolak
   - **Cara Uji:**
     1. Pastikan grup rute admin di `routes/web.php` diproteksi middleware: `Route::middleware(['role:admin'])->prefix('admin')->group(...)`.
     2. Buka browser dan login sebagai Dosen melalui URL `http://127.0.0.1:8000/dev/login-dosen`.
     3. Akses URL area admin di browser: `http://127.0.0.1:8000/admin/dashboard` atau `http://127.0.0.1:8000/admin/users`.
   - **Yang Sebenarnya:** Request langsung diblokir dengan respons HTTP 403 Forbidden ("Akses ditolak: Anda tidak memiliki peran yang diizinkan"). Middleware mendeteksi peran pengguna saat ini adalah `dosen`, bukan `admin`.

6. **Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos middleware role:dosen)**
   - **Prediksi:** Akan bisa, karena belum ada middleware role (atau jika hanya ada middleware role tanpa otorisasi objek)
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka `app/Http/Controllers/CourseController.php`, pada method `edit()` beri tanda komentar `/* ... */` pada baris validasi kepemilikan (`abort_unless(...)`).
     2. Login sebagai Dosen A (User ID 2) melalui URL `http://127.0.0.1:8000/dev/login-dosen`.
     3. Buka URL edit mata kuliah ID 2 ("Basis Data Lanjut") milik Dosen B (User ID 4) di `http://127.0.0.1:8000/courses/2/edit`.
     4. Amati bahwa form edit berhasil terbuka (kondisi rusak).
     5. Kembalikan kode `CourseController.php` (hapus komentar `/* ... */`), lalu refresh halaman browser untuk melihat proteksinya kembali aktif.
   - **Yang Sebenarnya:**
     * **Hanya Mengandalkan Middleware Role (Kondisi Rusak):** Form edit berhasil terbuka (HTTP 200 OK) dan Dosen A dapat mengubah data mata kuliah Dosen B. Karena keduanya berstatus `dosen`, middleware `role:dosen` meloloskannya.
     * **Dengan Validasi Kepemilikan (Kondisi Normal/BUILD):** Server mengembalikan HTTP 403 Forbidden ("Akses ditolak: Anda tidak memiliki izin untuk mengedit mata kuliah ini"). Ini membuktikan bahwa middleware saja tidak cukup untuk mengamankan data tingkat per-baris (record-level security).

---

## 5.4 Checkpoint Minggu 5

### 1. Apa itu IDOR? Peragakan satu contoh di aplikasi Anda, lalu tunjukkan perbaikannya.
**Pengertian:**
IDOR (*Insecure Direct Object Reference*) adalah celah keamanan di mana pengguna bisa melihat atau mengubah data milik orang lain hanya dengan mengganti angka ID pada URL, karena server tidak memverifikasi kepemilikan data tersebut.

**Tutorial Langkah Mencoba Merusak (Meloloskan Celah IDOR):**
1. Buka file `app/Http/Controllers/SubmissionController.php`, cari method `show()` (baris 78-86).
2. Matikan sementara validasi kepemilikan dengan memberi tanda komentar `/* ... */`:
   ```php
   /* Matikan sementara untuk mencoba celah IDOR:
   if (auth()->check()) {
       abort_unless(
           $submission->user_id === auth()->id()
               || auth()->user()->role === 'admin'
               || $submission->assignment->course->lecturer_id === auth()->id(),
           403,
           'Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
       );
   }
   */
   ```
3. Buka browser dan login sebagai **Mahasiswa A** (User ID 3) lewat `http://127.0.0.1:8000/dev/login-mahasiswa`.
4. Buka halaman tugas milik sendiri di URL: `http://127.0.0.1:8000/submissions/1`.
5. Coba ganti angka ID pada URL menjadi milik **Mahasiswa B**: `http://127.0.0.1:8000/submissions/2`.
6. **Hasil yang Lolos (Celah Terbuka):** Halaman langsung terbuka (HTTP 200 OK). Mahasiswa A dapat membaca catatan dan mengunduh berkas tugas Mahasiswa B. Ini membuktikan celah IDOR berhasil ditembus.

**Tutorial Langkah Memperbaiki Kembali (Setelah BUILD):**
1. Buka kembali `app/Http/Controllers/SubmissionController.php`, hapus tanda komentar `/* ... */` pada baris 78-86 agar `abort_unless` aktif kembali.
2. Refresh halaman browser di `http://127.0.0.1:8000/submissions/2`.
3. **Hasil Terlindungi:** Server langsung menolak akses dengan status **HTTP 403 Forbidden** (*"Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini."*).

---

### 2. Kenapa mengganti ID berurutan dengan UUID bukan perbaikan IDOR?
Karena UUID hanya membuat ID menjadi panjang dan acak agar sulit ditebak (*security through obscurity*), tetapi **tidak memeriksa hak akses sama sekali**.
* Masalah utama IDOR bukan karena ID-nya mudah ditebak (angka 1, 2, 3), melainkan karena **server tidak mengecek siapa pemilik data tersebut**.
* Jika tautan UUID korban bocor atau dibagikan, orang lain tetap bisa membuka datanya karena server tetap meloloskan siapa pun yang memegang UUID itu.
* Solusi yang benar adalah **memeriksa apakah user yang login berhak atas data tersebut**, bukan sekadar menyamarkan format ID.

---

### 3. Route model binding menjamin apa, dan tidak menjamin apa?
* **Yang Dijamin:**
  1. **Data pasti ada di database:** Laravel otomatis menjalankan `findOrFail()`. Jika record data ada langsung diberikan ke controller; jika tidak ada, otomatis muncul **HTTP 404 Not Found**.
  2. **Kode lebih ringkas:** Tidak perlu mengetik manual `find()` atau `findOrFail($id)` di controller.
* **Yang TIDAK Dijamin:**
  1. **Tidak menjamin hak akses (otorisasi):** Model binding tidak mengecek apakah pengguna yang sedang login boleh melihat atau mengedit data tersebut.
  2. **Tidak menjamin kecocokan relasi parent-child:** Pada URL bersarang (`/courses/2/assignments/2`), secara default tidak dicek apakah tugas 2 memang milik mata kuliah 2 (butuh `scopeBindings()` untuk itu).

---

### 4. Apa yang dilakukan `Route::scopeBindings()`? Beri contoh URL yang lolos tanpa itu.
* **Fungsi:**
  Memaksa Laravel mencari data anak (*child*) **hanya melalui relasi milik data induknya (*parent*)**.
  * Tanpa scoping: `Assignment::findOrFail(2)` (mencari tugas ID 2 di seluruh database tanpa peduli mata kuliahnya).
  * Dengan scoping: `$course->assignments()->findOrFail(2)` (mencari tugas ID 2 yang memang terdaftar di bawah mata kuliah tersebut).

**Tutorial Langkah Mencoba Merusak (Meloloskan URL Tanpa Scoping):**
1. Buka berkas `routes/web.php`, tambahkan rute pengujian berikut di luar grup `Route::scopeBindings` (misal di baris 46):
   ```php
   // Rute pengujian tanpa scopeBindings:
   Route::get('/courses/{course}/assignments/{assignment}', function (\App\Models\Course $course, \App\Models\Assignment $assignment) {
       return view('assignments.show', compact('assignment'));
   });
   ```
2. Ketahui data database: Tugas ID 2 sebenarnya adalah milik Mata Kuliah ID 1 ("Pemrograman Web").
3. Buka browser dan akses tugas tersebut secara silang di bawah rute Mata Kuliah ID 2 ("Basis Data Lanjut"):
   `http://127.0.0.1:8000/courses/2/assignments/2`
4. **Hasil yang Lolos (Anomali Relasi):** Halaman berhasil terbuka normal (**HTTP 200 OK**) menampilkan Tugas ID 2 di bawah rute Mata Kuliah ID 2. Tanpa scoping, Laravel mengambil kedua model secara terpisah tanpa memeriksa kesesuaian relasi `course_id`.

**Tutorial Langkah Memperbaiki (Mengaktifkan Scoping):**
1. Di `routes/web.php`, tambahkan method `->scopeBindings()` di ujung rute tadi:
   ```php
   Route::get('/courses/{course}/assignments/{assignment}', function (\App\Models\Course $course, \App\Models\Assignment $assignment) {
       return view('assignments.show', compact('assignment'));
   })->scopeBindings();
   ```
   *(Atau masukkan ke dalam grup `Route::scopeBindings()->group(...)` seperti konfigurasi proyek di baris 33).*
2. Buka URL yang sah di browser: `http://127.0.0.1:8000/courses/1/assignments/2`. Halaman berhasil terbuka normal (**HTTP 200 OK**) karena Tugas ID 2 memang milik Mata Kuliah ID 1.
3. Refresh atau buka kembali URL silang: `http://127.0.0.1:8000/courses/2/assignments/2`.
4. **Hasil Terlindungi:** Server langsung menolak dengan respons **HTTP 404 Not Found** karena Laravel mendeteksi Tugas ID 2 bukan anak dari Mata Kuliah ID 2.

---

### 5. Di berkas mana middleware didaftarkan pada Laravel 12? Kenapa berbeda dari kebanyakan tutorial?
* **Lokasi Pendaftaran:**
  Didaftarkan di berkas `bootstrap/app.php` (baris 17-21) pada bagian `$middleware->alias([...])`:
  ```php
  $middleware->alias([
      'role' => \App\Http\Middleware\EnsureUserHasRole::class,
  ]);
  ```
* **Kenapa Berbeda dari Tutorial:**
  Kebanyakan tutorial di internet dibuat untuk **Laravel 10 ke bawah** yang memakai berkas `app/Http/Kernel.php`. Di **Laravel 11 dan 12**, struktur aplikasi disederhanakan: berkas `Kernel.php` sudah dihapus dan semua konfigurasi middleware dipindahkan ke `bootstrap/app.php`.

---

### 6. Kenapa middleware `role:dosen` tidak cukup untuk mencegah dosen A mengedit mata kuliah dosen B?
* **Penyebabnya:**
  Middleware hanya bertugas di pintu gerbang untuk memeriksa **peran umum pengguna (role-based)**, bukan memeriksa **kepemilikan baris data (record-level ownership)**.
  1. Saat Dosen A membuka form edit mata kuliah milik Dosen B di `/courses/2/edit`, middleware `role:dosen` hanya mengecek: *"Apakah user ini seorang dosen?"*.
  2. Karena Dosen A memang berstatus dosen, ia diloloskan masuk. Middleware tidak tahu siapa dosen pengampu asli dari mata kuliah ID 2 tersebut.
* **Perbaikan di Controller (`app/Http/Controllers/CourseController.php` baris 69-75):**
  ```php
  if (auth()->check()) {
      abort_unless(
          auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
          403,
          'Akses ditolak: Anda tidak memiliki izin untuk mengedit mata kuliah ini.'
      );
  }
  ```
  Kode ini membandingkan ID dosen yang login dengan kolom `lecturer_id` mata kuliah. Jika tidak cocok, akses langsung ditolak dengan **HTTP 403 Forbidden**.
