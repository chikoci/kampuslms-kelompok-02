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

## Bagian CHECKPOINT: Evaluasi Pemahaman & Peragaan Mandiri

Berikut jawaban checkpoint yang to the point dan siap saya peragakan langsung saat presentasi di depan dosen:

### 1. Apa beda autentikasi dan otorisasi? Tunjukkan contohnya di kode Anda!
* **Bedanya Singkatnya:**
  * **Autentikasi (*Authentication* - "Siapa kamu?"):** Membuktikan identitas akun pengguna (proses login).
  * **Otorisasi (*Authorization* - "Boleh ngapain aja?"):** Menentukan hak akses pengguna yang sudah login (apakah boleh buka, edit, atau hapus suatu data).
* **Contoh di Kode Kita:**
  1. *Autentikasi:* Di `AuthController.php`:
     ```php
     Auth::attempt($credentials);
     ```
  2. *Otorisasi:* Di `CourseController.php`:
     ```php
     Gate::authorize('update', $course);
     ```
     Meskipun dosen sudah login, kalau dia bukan pengampu mata kuliah tersebut, sistem menolak dengan HTTP 403.

### 2. Tunjukkan Policy yang Anda tulis dan jelaskan logikanya!
* **Saya menunjukkan `app/Policies/CoursePolicy.php`:**
  ```php
  // Hanya Dosen pengampu dan Admin yang boleh mengedit mata kuliah
  public function update(User $user, Course $course): bool
  {
      if ($user->role === 'admin') {
          return true;
      }

      return $user->role === 'dosen' && $user->id === $course->lecturer_id;
  }
  ```
* **Penjelasan Barisnya:**
  1. Baris `if ($user->role === 'admin') return true;` memberi izin penuh kepada Administrator.
  2. Baris `$user->role === 'dosen' && $user->id === $course->lecturer_id;` memastikan bahwa hanya dosen yang ID-nya tercatat sebagai pemilik (`lecturer_id`) yang berhak mengedit kelas tersebut.

### 3. Kenapa `@can` di Blade saja tidak cukup? Peragakan kebocorannya!
* **Alasannya:**
  Direktif `@can` di Blade **hanya menyembunyikan tombol di tampilan layar**. Hacker tidak butuh tombol; mereka bisa langsung menembak URL endpoint secara langsung menggunakan cURL atau Postman.
* **Langkah Memperagakannya ke Dosen:**
  1. Buka `CourseController.php` method `destroy()`, hapus sementara baris `Gate::authorize('delete', $course);`.
  2. Login sebagai Mahasiswa di browser. Di layar, tombol "Hapus Kelas" memang tidak terlihat.
  3. Buka terminal, lalu kirim request hapus langsung via cURL:
     ```bash
     curl -X DELETE http://127.0.0.1:8000/courses/1 -b mhs.cookie
     ```
  4. Perlihatkan ke dosen: Course ID 1 langsung terhapus dari database!
  5. Kembalikan lagi `Gate::authorize()` dan ulangi cURL: server langsung membalas **403 Forbidden**. Ini bukti mutlak bahwa penjagaan wajib ada di Controller, bukan cuma di Blade.

### 4. Buka `docs/keamanan.md`. Pilih satu baris, jelaskan penutupannya, lalu buktikan dengan cURL!
* **Baris yang Dipilih:** Rute `PUT /courses/{course}` (Pengeditan Mata Kuliah).
* **Potensi Bahaya IDOR:**
  Dosen A login, lalu mencoba mengedit atau menyabotase mata kuliah milik Dosen B dengan mengganti ID di URL menjadi `/courses/2`.
* **Cara Kode Menutupnya:**
  Dipagari oleh `CoursePolicy@update`: server mengecek apakah `$user->id === $course->lecturer_id`.
* **Perintah cURL Pembuktian:**
  ```bash
  # Dosen 1 mencoba mengedit Course 2 milik Dosen 2
  curl -i -b dosen1.cookie -X PUT "http://127.0.0.1:8000/courses/2" \
    -F "_token=TOKEN_CSRF" \
    -F "name=Mata Kuliah Bajakan"
  ```
  **Hasil:** Server membalas **HTTP/1.1 403 Forbidden**.

### 5. Kenapa kata sandi di-hash, bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?
* **Kenapa di-hash:**
  * **Enkripsi** bersifat bolak-balik (bisa di-decrypt kembali jika tahu kuncinya). Kalau kunci server bocor, semua password pengguna langsung ketahuan.
  * **Hashing** bersifat satu arah (*one-way*). Sekali di-hash, nilainya secara matematika **mustahil dikembalikan** ke password aslinya.
* **Konsekuensinya untuk Lupa Password:**
  Karena server sendiri tidak tahu dan tidak bisa mendekripsi password asli pengguna, sistem **tidak bisa mengirimkan password lama ke email**. Sistem wajib mengirimkan **link reset token** agar pengguna **membuat kata sandi baru**.

### 6. Apa fungsi `session()->regenerate()` saat login?
* **Fungsi Utamanya:**
  Membuat ID sesi baru dan memusnahkan ID sesi lama tepat saat pengguna berhasil login.
* **Manfaatnya:**
  Menangkal serangan **Session Fixation**: mencegah hacker yang sudah menyiapkan ID sesi palsu sebelum korban login untuk membajak akun korban setelah korban berhasil masuk.
