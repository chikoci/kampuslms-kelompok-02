# Catatan Praktikum Minggu 7: Autentikasi, Otorisasi, dan Keamanan IDOR

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  
**Peran Kelompok:** Project Lead  
**Fokus Modul:** Autentikasi Sistem, Kebijakan Otorisasi Mata Kuliah (`CoursePolicy`), Pencegahan IDOR Tugas (`AssignmentPolicy`), dan Scoping Rute Berbasis Peran.

---

## Bagian READ: Bedah Alur Autentikasi & Keamanan

### 1. Berkas & Method yang Menangani POST Login
* **Rute:** `Route::post('/login', [AuthController::class, 'login'])` di `routes/web.php`.
* **Controller:** `app/Http/Controllers/AuthController.php` pada method `login()`.
* **Alur Singkat:**
  1. Validasi input email dan password.
  2. Dilindungi *Rate Limiter* internal (maksimal 5 kali percobaan gagal per menit) untuk menangkal serangan *brute force*.

### 2. Pemanggilan `Auth::attempt()`
* **Lokasi Kode:** `app/Http/Controllers/AuthController.php` baris 52.
* **Cara Kerjanya:**
  Method ini mencari email di tabel `users`, lalu mencocokkan password mentah yang diketik dengan hash Bcrypt di database menggunakan `Hash::check()`. Kalau cocok, session pengguna langsung aktif.

### 3. Fungsi `session()->regenerate()`
* **Lokasi Kode:** Tepat setelah `Auth::attempt()` berhasil.
* **Fungsinya:**
  Mencegah serangan **Session Fixation**. Sistem membuang ID session lama dan membuatkan ID session baru yang acak, sehingga hacker yang menjebak kita dengan ID session lama tidak bisa membajak akun kita saat kita berhasil login.

### 4. Di Mana Kata Sandi Di-hash?
* **Lokasi Kode:** `app/Models/User.php` pada method `casts()`:
  ```php
  'password' => 'hashed',
  ```
* **Cara Kerjanya:**
  Di Laravel 12, casting `'password' => 'hashed'` otomatis meng-hash password memakai algoritma Bcrypt setiap kali password diisi atau diubah, jadi kita tidak perlu lagi mengetik `Hash::make()` manual.

### 5. Nilai Cookie Sebelum vs Sesudah Login
* **Sebelum Login (Guest):** Cookie `kampuslms_session` bernilai string acak sementara untuk pengunjung anonim.
* **Sesudah Login (Authenticated):** Nilai cookie `kampuslms_session` **berubah total** karena perintah `session()->regenerate()`.
* **Flag Keamanan:** Cookie dilengkapi flag `HttpOnly` (agar tidak bisa dicuri JavaScript/XSS) dan `SameSite=Lax` (pencegah CSRF).

### 6. Logout Lalu Tekan Tombol Back di Browser
* **Hasil Pengamatan:** Browser menampilkan halaman sebelumnya dari memori cache lokal, tetapi saat tombol aksi apa pun diklik (seperti edit/hapus), server langsung me-redirect ke halaman login karena sesi di server sudah dihancurkan oleh `Auth::logout()` dan `session()->invalidate()`.

---

## Bagian BREAK: 8 Eksperimen Kerusakan & Keamanan

Berikut 8 eksperimen kerusakan yang saya uji untuk membuktikan pertahanan sistem:

1. **Hapus token `@csrf` dari form login:**
   * *Hasil:* Submit form langsung memunculkan error **HTTP 419 Page Expired**.
   * *Pelajaran:* Token CSRF wajib ada agar form tidak bisa dipalsukan oleh website asing.

2. **Hapus middleware `auth` dari rute dashboard:**
   * *Hasil:* Siapa pun tanpa login bisa langsung membuka halaman dashboard.
   * *Pelajaran:* Middleware `auth` adalah palang pintu pertama untuk halaman tertutup.

3. **Dosen A mencoba mengedit mata kuliah milik Dosen B:**
   * *Hasil:* Server langsung menolak dengan status **HTTP 403 Forbidden**.
   * *Pelajaran:* `CoursePolicy@update` berhasil mencegah dosen mengacak-acak kelas milik dosen lain.

4. **Mahasiswa mencoba membuka submission milik mahasiswa lain:**
   * *Hasil:* Server menolak dengan status **HTTP 403 Forbidden**.
   * *Pelajaran:* Celah IDOR tertutup total oleh `SubmissionPolicy@view`.

5. **Ubah `CourseController@index` jadi `Course::paginate()` polos tanpa filter:**
   * *Hasil:* Mahasiswa bisa melihat semua mata kuliah se-kampus (termasuk kelas yang tidak diambil).
   * *Pelajaran:* Kueri harus di-scope berdasarkan role (`$user->courses()` untuk mahasiswa, `$user->taughtCourses()` untuk dosen).

6. **Kirim payload `role=admin` pada form edit profil (Privilege Escalation):**
   * *Hasil:* Nama berhasil diubah, tapi kolom `role` di database tetap `mahasiswa`.
   * *Pelajaran:* Kolom `role` tidak boleh ada di `$fillable` dan pengubahan role dipagari oleh `UserPolicy@updateRole`.

7. **Hapus cast `hashed` dari password lalu buat akun baru:**
   * *Hasil:* Password tersimpan di MySQL berupa teks polos biasa (*plaintext*).
   * *Pelajaran:* Berbahaya sekali kalau database bocor. Cast `hashed` mutlak diperlukan.

8. **Salin cookie session login ke browser lain (Session Hijacking):**
   * *Hasil:* Browser kedua langsung otomatis login tanpa password.
   * *Pelajaran:* Cookie session adalah identitas akses. Makanya wajib pakai flag `HttpOnly` dan HTTPS di server produksi.

---

## Bagian CHECKPOINT: Evaluasi Pemahaman & Panduan Peragaan Live

Berikut panduan jawaban Checkpoint Minggu 7 lengkap dengan **tempat peragaan, akun yang dipakai, perintah copy-paste, dan kalimat yang diucapkan saat presentasi di depan dosen**:

---

### 1. Apa beda autentikasi dan otorisasi? Tunjukkan contohnya di kode Anda!
* **Bedanya Singkatnya:**
  * **Autentikasi (*Authentication* - "Siapa kamu?"):** Membuktikan identitas akun pengguna (proses login dengan email & password).
  * **Otorisasi (*Authorization* - "Boleh ngapain aja?"):** Menentukan hak akses pengguna yang sudah login (apakah boleh buka, edit, atau hapus suatu data).
* **Tempat Peragaan:** VS Code.
* **Langkah Praktik Langsung:**
  1. Di VS Code, buka file `app/Http/Controllers/AuthController.php` baris 52:
     Tunjukkan baris:
     ```php
     if (Auth::attempt($credentials, $remember))
     ```
     *(Ini Autentikasi: mengecek apakah email dan password cocok).*
  2. Buka file `app/Http/Controllers/CourseController.php` baris 70:
     Tunjukkan baris:
     ```php
     Gate::authorize('update', $course);
     ```
     *(Ini Otorisasi: meskipun dosen sudah login, kalau dia bukan pengampu mata kuliah ini, dia tetap dilarang mengedit).*
* **Kalimat ke Dosen:**
  *"Autentikasi adalah pembuktian identitas saat login lewat `Auth::attempt()`, sedangkan Otorisasi adalah pemeriksaan hak akses objek menggunakan `Gate::authorize()` seperti pada edit mata kuliah ini."*

---

### 2. Tunjukkan Policy yang Anda tulis dan jelaskan logikanya!
* **Tempat Peragaan:** VS Code.
* **Langkah Praktik Langsung:**
  1. Buka file `app/Policies/CoursePolicy.php`.
  2. Arahkan kursor ke method `update()`:
     ```php
     public function update(User $user, Course $course): bool
     {
         if ($user->role === 'admin') {
             return true;
         }

         return $user->role === 'dosen' && $user->id === $course->lecturer_id;
     }
     ```
* **Penjelasan Logika ke Dosen:**
  1. Baris `if ($user->role === 'admin') return true;` memberi hak penuh kepada Admin untuk mengelola semua kelas.
  2. Baris `$user->role === 'dosen' && $user->id === $course->lecturer_id;` memastikan hanya Dosen yang ID-nya tercatat sebagai pemilik (`lecturer_id`) yang boleh mengedit kelas tersebut.
* **Kalimat ke Dosen:**
  *"Policy ini memastikan Dosen A tidak bisa mengubah data mata kuliah milik Dosen B, sementara Admin memiliki hak akses penuh untuk mengelola seluruh kelas."*

---

### 3. Kenapa `@can` di Blade saja tidak cukup? Peragakan kebocorannya!
* **Alasannya:**
  Direktif `@can` di Blade **hanya menyembunyikan tombol di tampilan HTML layar**. Hacker tidak membutuhkan tombol; mereka bisa langsung mengirimkan HTTP Request langsung (menggunakan cURL atau Postman) ke URL endpoint tersebut.
* **Tempat Peragaan:** Browser Chrome & Terminal Git Bash.
* **Akun yang Dipakai:** `mahasiswa@kampuslms.test` | Password: `password`.
* **Langkah Praktik Langsung:**
  1. Di Browser, login sebagai `mahasiswa@kampuslms.test`.
  2. Buka halaman detail mata kuliah: `http://127.0.0.1:8000/courses/1`.
  3. Perlihatkan ke dosen: tombol **"Hapus Mata Kuliah"** memang tidak ada di tampilan layar mahasiswa (karena disembunyikan oleh `@can`).
  4. Sekarang buka Terminal Git Bash, simulasikan hacker yang menembak langsung rute hapus via cURL:
     ```bash
     # 1. Login via cURL dan simpan cookie mahasiswa
     curl -s -c mhs.cookie -X POST "http://127.0.0.1:8000/login" -F "email=mahasiswa@kampuslms.test" -F "password=password"

     # 2. Coba kirim request DELETE langsung ke rute courses
     curl -i -b mhs.cookie -X DELETE "http://127.0.0.1:8000/courses/1"
     ```
  5. **Hasil di Layar:** Server membalas **HTTP/1.1 403 Forbidden**!
* **Kalimat ke Dosen:**
  *"Jika proteksi hanya dipasang di Blade menggunakan `@can`, mahasiswa masih bisa menghapus kelas dengan menembak URL langsung via cURL. Maka dari itu, penjagaan di Controller menggunakan `Gate::authorize()` adalah keharusan mutlak agar server tetap membalas 403 Forbidden."*

---

### 4. Buka `docs/keamanan.md`. Pilih satu baris, jelaskan penutupannya, lalu buktikan dengan cURL!
* **Baris yang Dipilih:** Baris No. 7 di tabel matriks keamanan:
  `PUT /courses/{course}` (Pengeditan Mata Kuliah).
* **Potensi Bahaya IDOR:**
  Dosen 2 login, lalu mencoba mengubah nama atau data mata kuliah Course 1 milik Dosen 1.
* **Cara Kode Menutupnya:**
  Dipagari oleh `CoursePolicy@update` yang mengecek `$user->id === $course->lecturer_id`.
* **Tempat Peragaan:** Terminal Git Bash.
* **Langkah Praktik Langsung (Tinggal Tempel):**
  ```bash
  # 1. Login sebagai Dosen tambahan (Dosen yang bukan pengampu Course 1)
  curl -s -c dosen_lain.cookie -X POST "http://127.0.0.1:8000/login" \
    -F "email=dosen@kampuslms.test" \
    -F "password=password"

  # 2. Ambil token CSRF
  CSRF_TOKEN=$(curl -s -b dosen_lain.cookie "http://127.0.0.1:8000/courses" | grep -oP 'name="_token" value="\K[^"]+' | head -n 1)

  # 3. Coba kirim request edit ke Course ID 2 (milik Dosen lain)
  curl -i -b dosen_lain.cookie -X PUT "http://127.0.0.1:8000/courses/2" \
    -F "_token=${CSRF_TOKEN}" \
    -F "name=Mata Kuliah Disabotase"
  ```
* **Hasil di Layar:** Mengembalikan **HTTP/1.1 403 Forbidden**.
* **Kalimat ke Dosen:**
  *"Sesuai dokumen matriks keamanan kami, rute update mata kuliah terlindungi anti-IDOR. Dosen pengampu kelas lain yang mencoba menembak rute ini langsung ditolak dengan status 403 Forbidden."*

---

### 5. Kenapa kata sandi di-hash, bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?
* **Kenapa di-hash:**
  * **Enkripsi** bersifat bolak-balik (bisa didekripsi kembali menjadi teks asli jika kunci bocor).
  * **Hashing** bersifat satu arah (*one-way*). Secara matematika dan kriptografi, nilai hash **mustahil dikembalikan** ke teks password aslinya.
* **Konsekuensinya untuk Lupa Password:**
  Karena server sendiri tidak tahu dan tidak bisa memulihkan password asli pengguna, sistem **tidak boleh mengirimkan password lama ke email**. Sistem wajib menggunakan mekanisme **Password Reset Token** agar pengguna diarahkan untuk **membuat kata sandi baru**.
* **Tempat Peragaan:** VS Code & Database MySQL.
* **Langkah Praktik Langsung:**
  1. Di VS Code, buka `app/Models/User.php` baris 48:
     Tunjukkan kode: `'password' => 'hashed'`.
  2. Buka tabel `users` di database / terminal tinker:
     Tunjukkan bahwa password tersimpan sebagai string `$2y$12$...` (hash Bcrypt).
* **Kalimat ke Dosen:**
  *"Password kami hash satu arah menggunakan Bcrypt agar jika database bocor, password pengguna tetap aman. Konsekuensinya, fitur lupa password wajib menggunakan link reset token untuk membuat kata sandi baru."*

---

### 6. Apa fungsi `session()->regenerate()` saat login?
* **Fungsi Utamanya:**
  Membuat ID sesi baru yang segar dan memusnahkan ID sesi lama tepat saat pengguna berhasil login.
* **Mencegah Serangan Apa:**
  Menangkal serangan **Session Fixation**: mencegah penyerang membajak akun korban menggunakan session ID lama yang sudah disiapkan penyerang sebelum korban login.
* **Tempat Peragaan:** Browser Chrome (DevTools F12).
* **Langkah Praktik Langsung:**
  1. Buka Browser Chrome, tekan **F12** -> buka tab **Application** -> klik menu **Cookies** -> pilih `http://127.0.0.1:8000`.
  2. Sebelum login, lihat nilai cookie `kampuslms_session` (misal berawalan `eyJpdi...A`).
  3. Isi form login dan klik tombol **Masuk**.
  4. Perlihatkan ke dosen di tab Application: nilai cookie `kampuslms_session` **langsung berganti total dengan string baru** (berawalan `eyJpdi...B`).
* **Kalimat ke Dosen:**
  *"Bisa dilihat di DevTools Pak/Bu, saat login berhasil, nilai cookie session langsung digenerasi ulang total oleh `session()->regenerate()` untuk memastikan penyerang tidak bisa membajak sesi login pengguna."*

