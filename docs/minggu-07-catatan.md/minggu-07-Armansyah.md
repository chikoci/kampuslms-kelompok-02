# Catatan Praktikum Minggu 7
**Nama:** Armansyah  
**NIM:** 10241013  


---

# READ — Bedah Starter Kit & Mekanisme Keamanan

Pada tahap ini, saya melakukan penelusuran arsitektur kode autentikasi dan pengelolaan sesi bawaan aplikasi KampusLMS secara langsung pada repositori lokal:

## 1. Berkas mana yang menangani POST login? Method apa?

* **Rute Pendaftaran:** Didefinisikan di `routes/web.php` baris 33:
  ```php
  Route::post('/login', [AuthController::class, 'login'])->name('login.post');
  ```
* **Berkas Controller:** `app/Http/Controllers/AuthController.php`
* **Method Penangan:** `public function login(Request $request)`
* **Alur Eksekusi:**
  1. Permintaan HTTP `POST /login` diterima oleh route middleware `guest`.
  2. Input divalidasi (`email` wajib berupa format email valid, `password` wajib diisi).
  3. Menggunakan *rate limiter* internal (`RateLimiter::tooManyAttempts($throttleKey, 5)`) untuk membatasi maksimal 5 percobaan login gagal per menit dari kombinasi IP dan email penyerang.

---

## 2. Di baris mana `Auth::attempt()` atau setaranya dipanggil?

* **Lokasi Baris:** `app/Http/Controllers/AuthController.php` baris 52:
  ```php
  if (Auth::attempt($credentials, $remember)) {
      RateLimiter::clear($throttleKey);

      // Cegah serangan Session Fixation dengan regenerasi session ID
      $request->session()->regenerate();

      return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
  }
  ```
* **Cara Kerja:**
  `Auth::attempt()` menerima array `$credentials` (`['email' => ..., 'password' => ...]`). Laravel secara otomatis:
  1. Mencari baris record di tabel `users` berdasarkan kolom `email`.
  2. Mengambil hash kata sandi yang tersimpan di database.
  3. Memverifikasi apakah string password masukan cocok dengan hash tersebut menggunakan `Hash::check()` (Bcrypt).
  4. Jika cocok, Laravel mengaitkan identitas pengguna ke dalam sesi aktif saat ini dan mengembalikan nilai `true`.

---

## 3. Temukan `session()->regenerate()`. Kenapa ia ada di situ?

* **Lokasi Baris:** `app/Http/Controllers/AuthController.php` baris 56, tepat setelah blok `Auth::attempt()` bernilai sukses.
* **Alasan Keberadaannya (*Why*):**
  Baris ini wajib ada untuk menangkal kerentanan kritis **Session Fixation Attack**.
* **Mekanisme Serangan Tanpa `session()->regenerate()`:**
  1. Penyerang mengunjungi situs KampusLMS terlebih dahulu untuk mendapatkan Session ID anonim tertentu (misal: `ID_SESI_123`).
  2. Penyerang menjebak korban melalui phishing atau URL bertarget agar membuka situs menggunakan `ID_SESI_123` tersebut.
  3. Ketika korban berhasil login, jika aplikasi **tidak** meregenerasi session ID, maka hak akses pengguna yang sudah terotentikasi akan melekat pada `ID_SESI_123` lama.
  4. Penyerang yang masih memegang `ID_SESI_123` di komputernya langsung memiliki akses penuh sebagai korban (*Session Hijacking*).
* **Solusi dengan `session()->regenerate()`:**
  Laravel langsung menghancurkan ID sesi lama dan menerbitkan ID sesi acak yang sama sekali baru di browser korban, sehingga ID lama yang dipegang penyerang seketika menjadi tidak berlaku.

---

## 4. Di mana kata sandi di-hash? Cari cast `hashed` di model `User`

* **Lokasi Berkas:** `app/Models/User.php` baris 46–52:
  ```php
  protected function casts(): array
  {
      return [
          'email_verified_at' => 'datetime',
          'password' => 'hashed',
      ];
  }
  ```
* **Penjelasan Teknis:**
  Di Laravel 12, casting atribut didefinisikan melalui method `casts(): array`. Cast `'password' => 'hashed'` memastikan bahwa setiap kali properti `$user->password = 'kata_sandi_baru'` diisi, Laravel secara otomatis membungkus nilai tersebut dengan algoritma hashing satu arah (Bcrypt dengan cost factor 12) sebelum kueri SQL `INSERT` atau `UPDATE` dikirimkan ke database MySQL. Kita tidak perlu lagi memanggil `Hash::make()` secara manual.

---

## 5. Buka DevTools → Cookies sebelum dan sesudah login. Bandingkan nilai cookie session

Pengujian dilakukan menggunakan Google Chrome Developer Tools pada domain lokal `http://kampuslms-kelompok-02.test`:

| Status Pengguna | Nama Cookie | Karakteristik Nilai Cookie | Flag Keamanan |
|---|---|---|---|
| **Sebelum Login (Tamu / Guest)** | `kampuslms_session` | String terenkripsi pendek (misal: `eyJpdiI6I...`) berumur sementara | `HttpOnly`, `SameSite=Lax` |
| **Setelah Login (Admin/Dosen/Mhs)** | `kampuslms_session` | **Nilai berubah 100% total** karena eksekusi `session()->regenerate()` | `HttpOnly`, `SameSite=Lax` |
| **Setelah Login + Ingat Saya** | `remember_web_...` | Cookie tambahan berisi token persistensi unik berumur panjang (5 tahun) | `HttpOnly`, `SameSite=Lax` |

**Pengamatan Penting:**
Perubahan nilai string pada cookie `kampuslms_session` membuktikan bahwa session ID lama telah dimusnahkan dan sesi baru yang aman telah diterbitkan. Flag `HttpOnly` aktif memastikan script JavaScript jahat (serangan XSS) tidak bisa membaca isi cookie ini lewat `document.cookie`.

---

## 6. Logout, lalu tekan tombol *back*. Apa yang terjadi? Kenapa?

* **Langkah Uji:**
  1. Login sebagai `admin@kampuslms.test`.
  2. Masuk ke halaman `/dashboard`.
  3. Tekan tombol "Keluar" (mengirim `POST /logout`).
  4. Halaman dialihkan ke `/login` dengan pesan *"Anda telah berhasil keluar."*
  5. Tekan tombol **Back** (Kembali) pada browser.
* **Hasil Pengamatan:**
  Browser mungkin menampilkan cuplikan visual dashboard sesaat karena cache lokal browser (*Back-Forward Cache / bfcache*). Namun, saat kita mengklik tautan apapun atau melakukan refresh, **sistem langsung menolak dan me-redirect paksa kembali ke `/login`**.
* **Penyebab Teknis:**
  Pada method `AuthController::logout()` baris 74–79:
  ```php
  Auth::logout();
  $request->session()->invalidate();
  $request->session()->regenerateToken();
  ```
  1. `Auth::logout()` menghapus identitas autentikasi pengguna dari memori server.
  2. `session()->invalidate()` menghapus seluruh data sesi di server dan merusak session ID.
  3. `session()->regenerateToken()` mereset token CSRF baru.
  Ketika request baru dikirimkan, middleware `auth` memeriksa sesi di server, mendapati bahwa pengguna sudah tidak memiliki tiket sesi valid, lalu mengembalikan status redirect HTTP 302 ke halaman login.

---

# BREAK — Delapan Kerusakan & Eksperimen Keamanan (50 menit)

Berikut dokumentasi pengujian 8 skenario perusakan yang saya uji langsung pada aplikasi KampusLMS:

## 1. Hapus `session()->regenerate()` dari login
* **Yang Dilakukan:** Memberi tanda komentar pada baris `$request->session()->regenerate();` di `AuthController.php`.
* **Prediksi:** Session ID browser tidak akan berubah antara sebelum dan sesudah login.
* **Hasil Pengamatan:** Nilai cookie `kampuslms_session` di Application DevTools sebelum login bernilai persis sama dengan setelah login.
* **Kesimpulan:** Celah *Session Fixation* terbuka lebar. Penyerang dapat menyuntikkan session ID yang telah diketahuinya kepada korban dan mengambil alih akun setelah korban login.

---

## 2. Hapus `Gate::authorize()` dari `update`, tapi biarkan `@can` di Blade
* **Yang Dilakukan:** Di `CourseController@update` dan `CourseController@edit`, baris `Gate::authorize('update', $course);` dihapus. Namun di `resources/views/courses/show.blade.php`, tombol edit tetap dibungkus `@can('update', $course)`.
* **Prediksi:** Tombol edit hilang dari pandangan dosen lain di browser, tetapi jika dosen lain mengirim request HTTP secara langsung, data tetap berhasil diubah.
* **Hasil Pengamatan:**
  Saat login sebagai Dosen B (`dosen2@kampuslms.test`) di browser, tombol "Edit Mata Kuliah" untuk Course 1 memang tidak terlihat. Namun, saat Dosen B mengirimkan perintah `curl` langsung ke terminal:
  ```bash
  curl -X PUT http://kampuslms-kelompok-02.test/courses/1 \
    -b "cookie_dosen2.txt" \
    -d "name=Nama Dibajak Dosen B" \
    -d "code=SI201" -d "sks=3" -d "status=active"
  ```
  Permintaan **berhasil dieksekusi (HTTP 302 Redirect, data mata kuliah berubah!)**.
* **Pelajaran Penting:** `@can` di Blade **hanyalah pemanis antarmuka (UX)** agar tombol tidak membingungkan pengguna, **bukan pengaman data**. Pengamanan mutlak wajib berada di sisi backend controller melalui `Gate::authorize()`.

---

## 3. Sebagai Dosen A, kirim PUT ke mata kuliah Dosen B lewat `curl`
* **Yang Dilakukan:** Menguji integritas `CoursePolicy@update` yang sudah diperbaiki. Dosen A (`dosen@kampuslms.test` - pengampu Course 1) mengirim request perubahan nama ke Course 2 (milik Dosen B).
* **Perintah Uji:**
  ```bash
  curl -s -o /dev/null -w "%{http_code}" -b cookie_dosenA.txt -X PUT \
    http://kampuslms-kelompok-02.test/courses/2 \
    -d "name=Percobaan Sabotase&code=SI202&sks=3&status=active"
  ```
* **Hasil Aktual:** Mengembalikan HTTP status **`403 Forbidden`**.
* **Kesimpulan:** `CoursePolicy` bekerja sempurna. Server memverifikasi apakah `auth()->id() === $course->lecturer_id`. Karena ID tidak cocok, eksekusi ditolak sebelum kueri database dijalankan.

---

## 4. Sebagai Mahasiswa, buka submission mahasiswa lain (IDOR Minggu 5)
* **Yang Dilakukan:** Mahasiswa 1 (`mahasiswa@kampuslms.test`, ID 3) mencoba membuka detail pengumpulan tugas milik rekannya di URL `/submissions/2`.
* **Perintah Uji:**
  ```bash
  curl -s -o /dev/null -w "%{http_code}" -b cookie_mhs1.txt \
    http://kampuslms-kelompok-02.test/submissions/2
  ```
* **Hasil Aktual:** Mengembalikan HTTP status **`403 Forbidden`**.
* **Kesimpulan:** Titik rawan IDOR utama telah tertutup total oleh `SubmissionPolicy@view`. Mahasiswa hanya dapat melihat submission yang memiliki `user_id === auth()->id()`.

---

## 5. Ubah `index` menjadi `Course::paginate()` polos, login sebagai mahasiswa
* **Yang Dilakukan:** Mengganti scoping kueri di `CourseController@index` dari `match ($user->role)` menjadi `Course::paginate(15)` tanpa filter relasi.
* **Hasil Pengamatan:** Mahasiswa yang login dapat melihat seluruh 5 mata kuliah di sistem, termasuk kelas yang tidak ia ambil (misalnya mata kuliah prodi lain).
* **Bahaya:** Terjadi **Kebocoran Data Tingkat Daftar (*List-Level Data Leak*)**. Informasi dosen pengampu, kode kelas tertutup, dan jadwal ujian kelas lain terlihat oleh pengguna yang tidak berhak.
* **Solusi Perbaikan:** Mengembalikan kueri scoped:
  ```php
  $query = match ($user->role) {
      'admin' => Course::query(),
      'dosen' => $user->taughtCourses(),
      'mahasiswa' => $user->courses(),
  };
  ```

---

## 6. Kirim `role=admin` pada form edit profil (*Privilege Escalation*)
* **Yang Dilakukan:** Mengirimkan payload request update profil pengguna biasa dengan menyisipkan parameter `role=admin` via cURL.
  ```bash
  curl -X PUT http://kampuslms-kelompok-02.test/users/3 \
    -b cookie_mhs.txt \
    -d "name=Mahasiswa Heker&email=mahasiswa@kampuslms.test&role=admin"
  ```
* **Hasil Aktual:** Data profil (nama) terupdate, namun **kolom `role` di database tetap bernilai `mahasiswa`**.
* **Penyebab:** Pada implementasi `UserController@update` yang saya susun bersama `UserPolicy`:
  1. Kolom `'role'` sengaja **tidak dimasukkan** ke dalam `$fillable` model `User`.
  2. Pengubahan peran dipagari secara ketat:
     ```php
     if (! empty($validated['role']) && Gate::allows('updateRole', $user)) {
         $user->role = $validated['role'];
     }
     ```
  3. `UserPolicy@updateRole` hanya mengembalikan `true` jika `$user->role === 'admin'`. Upaya eskalasi hak akses berhasil digagalkan 100%!

---

## 7. Ganti cast `hashed` menjadi tidak ada, buat user baru, lihat kolom password di database
* **Yang Dilakukan:** Menghapus `'password' => 'hashed'` dari method `casts()` di `User.php`, lalu membuat user baru lewat form register/tinker dengan password `"rahasia123"`.
* **Hasil Pengamatan di MySQL:**
  Kueri `SELECT id, email, password FROM users WHERE email='baru@kampuslms.test';` menampilkan password persis berupa teks polos `"rahasia123"` (bukan string `$2y$12$...`).
* **Bahaya:** Jika database bocor (SQL Injection, backup file bocor, atau dump dicuri), seluruh kata sandi pengguna langsung terbongkar seketika tanpa perlu proses cracking.

---

## 8. Login, salin cookie session, tempel di browser lain (*Session Hijacking*)
* **Yang Dilakukan:** Login sebagai Admin di Google Chrome, menyalin nilai cookie `kampuslms_session`, lalu membukanya di jendela *Incognito* / browser Firefox yang berbeda dengan menyuntikkan cookie tersebut ke DevTools.
* **Hasil Pengamatan:** Browser kedua langsung otomatis login sebagai Admin tanpa diminta memasukkan email dan password!
* **Analisis & Mitigasi:**
  Inilah alasan fundamental mengapa:
  1. Cookie session wajib bertanda **`HttpOnly`** agar tidak bisa dicuri oleh skrip JavaScript asing (serangan XSS).
  2. Cookie wajib bertanda **`Secure`** di server produksi agar hanya ditransmisikan melalui kanal HTTPS terenkripsi, mencegah *man-in-the-middle sniffing*.
  3. Cookie diberi flag **`SameSite=Lax`** untuk mencegah serangan CSRF dari domain lain.

---

# CHECKPOINT MINGGU 7 — Gerbang Interview Tugas 2

Berikut jawaban komprehensif untuk persiapan sesi *interview code-walkthrough* Tugas 2:

### 1. Apa beda autentikasi dan otorisasi? Tunjukkan satu contoh masing-masing di kode Anda.

* **Autentikasi (*Authentication — Who are you?*):**
  Proses membuktikan identitas pengguna yang datang. Apakah pengguna ini benar-benar pemilik akun yang sah?
  * *Contoh di Kode:* Di `app/Http/Controllers/AuthController.php` baris 52:
    ```php
    Auth::attempt($credentials, $remember);
    ```
    Memeriksa kecocokan email dan hash password di database untuk menerbitkan sesi login.

* **Otorisasi (*Authorization — What are you allowed to do?*):**
  Proses memeriksa apakah pengguna yang identitasnya sudah dikenali tersebut memiliki hak istimewa (*permission*) untuk melihat, mengedit, atau menghapus objek tertentu.
  * *Contoh di Kode:* Di `app/Http/Controllers/UserController.php` baris 19:
    ```php
    Gate::authorize('viewAny', User::class);
    ```
    Meskipun pengguna sudah berhasil login (terautentikasi), jika perannya bukan `admin`, sistem langsung melempar exception `403 Forbidden`.

---

### 2. Tunjukkan Policy yang Anda tulis. Jelaskan tiap barisnya!

Saya secara khusus merancang dan menulis **`app/Policies/UserPolicy.php`** untuk mengamankan entitas data pengguna dari akses liar dan eskalasi hak akses:

```php
<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Hanya Admin yang berhak melihat daftar/direktori seluruh pengguna sistem (/users)
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    // Admin boleh melihat profil siapa saja; Pengguna biasa hanya boleh melihat profilnya sendiri
    public function view(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    // Hanya Admin yang berhak membuka form dan mendaftarkan akun baru (/users/create)
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    // Admin boleh mengedit data siapa saja; Pengguna biasa hanya boleh mengedit profil dirinya sendiri
    public function update(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    // Aturan Krusial Anti-Privilege Escalation: Hanya Admin yang berhak mengubah kolom role akun
    public function updateRole(User $user): bool
    {
        return $user->role === 'admin';
    }

    // Hanya Admin yang boleh menghapus pengguna, dan DILARANG menghapus akunnya sendiri ($user->id !== $model->id)
    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }
}
```

---

### 3. Kenapa `@can` di Blade tidak cukup? Peragakan dengan mengakses URL langsung!

* **Alasan:**
  Direktif `@can` di Blade hanya dievaluasi saat merender HTML di server. Fungsinya **hanya menyembunyikan elemen visual (seperti tombol `<button>` atau tautan `<a>`)** agar tidak tampak di mata pengguna biasa.
* **Celah Bahaya:**
  Jika di Controller method `destroy()` atau `update()` tidak dipasang `Gate::authorize()`, siapa pun dapat mengirimkan HTTP Request langsung (menggunakan `curl`, Postman, atau inspect Element console) ke endpoint tersebut. Tanpa penjagaan di Controller, aksi terlarang tersebut tetap dieksekusi oleh server.
* **Peragaan Nyata:**
  1. Hapus baris `Gate::authorize('delete', $user);` pada `UserController@destroy`.
  2. Buka akun sebagai Mahasiswa. Tombol "Hapus Akun" memang tidak ada di tampilan.
  3. Buka terminal dan kirim request:
     `curl -X DELETE http://kampuslms-kelompok-02.test/users/2 -b cookie_mhs.txt`
  4. Server mengembalikan 302 dan User ID 2 terhapus dari database! Ini membuktikan penjagaan di sisi server (*Gate/Policy*) adalah keharusan mutlak.

---

### 4. Buka `docs/keamanan.md`. Pilih satu baris, jelaskan bagaimana ia ditutup, lalu buktikan dengan `curl`!

* **Baris yang Dipilih:** Baris No. 11 — Titik Rawan IDOR Utama:
  `GET /submissions/{submission}` (Pengumpulan Tugas Mahasiswa).
* **Potensi Bahaya IDOR:**
  Mahasiswa A yang login dapat mengganti parameter ID di URL dari `/submissions/1` menjadi `/submissions/2` untuk mengintip berkas jawaban dan nilai rekan kelasnya.
* **Mekanisme Penutup Konkret:**
  Ditutup menggunakan **`SubmissionPolicy@view`** yang dipanggil di `SubmissionController@show`:
  ```php
  public function view(User $user, Submission $submission): bool
  {
      return $user->role === 'admin'
          || $user->id === $submission->assignment->course->lecturer_id
          || $user->id === $submission->user_id;
  }
  ```
  Akses hanya diizinkan bila: (1) Admin, (2) Dosen pengampu mata kuliah tugas tersebut, atau (3) Mahasiswa pemilik sah pengumpulan tersebut.
* **Bukti Pengujian cURL:**
  ```bash
  # Mahasiswa login (ID 3) mencoba membuka submission milik mahasiswa lain (Submission ID 2)
  curl -s -o /dev/null -w "%{http_code}\n" -b cookies/mhs.cookie \
    http://kampuslms-kelompok-02.test/submissions/2
  ```
  **Hasil Respons:** `403` (Akses Ditolak / Forbidden).

---

### 5. Kenapa kata sandi di-hash, bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?

* **Perbedaan Hash vs Enkripsi:**
  * **Enkripsi (Dua Arah / *Two-Way*):** Teks dapat diubah menjadi chipertext dan **dapat dikembalikan (*decrypt*)** menjadi teks asli jika memiliki kunci rahasianya (*secret key*). Jika server atau kunci bocor, semua kata sandi pengguna langsung terbongkar.
  * **Hashing (Satu Arah / *One-Way*):** Fungsi matematika searah (Bcrypt/Argon2) yang mengubah password menjadi string acak tetap beserta salt. Secara kriptografi, nilai hash **mustahil dikembalikan (*irreversible*)** ke teks password aslinya.
* **Konsekuensinya untuk Fitur Lupa Password:**
  Karena server tidak pernah tahu dan tidak bisa mendekripsi kata sandi asli pengguna, aplikasi **tidak boleh dan tidak akan pernah bisa mengirimkan kembali password lama ke email pengguna**. 
  Fitur lupa password wajib menggunakan mekanisme **Password Reset Token**: server menerbitkan token sementara terenkripsi yang dikirim ke email, lalu mengarahkan pengguna ke formulir untuk **membuat kata sandi baru**.

---

### 6. Apa fungsi `session()->regenerate()` saat login?

Fungsi utamanya adalah memusnahkan ID sesi acak lama dan menerbitkan ID sesi baru (*fresh Session ID*) seketika saat proses login berhasil. Tujuannya adalah menangkal serangan **Session Fixation**: mencegah penyerang membajak akun korban menggunakan session ID pra-autentikasi yang sudah disiapkan penyerang sebelum korban login.

---

### 7. Kenapa daftar mata kuliah tidak boleh diambil semua lalu disaring di view?

Contoh kode yang salah:
```php
// SALAH BESAR (Anti-Pattern):
$courses = Course::all();
return view('courses.index', compact('courses'));
// Lalu di Blade: @if(auth()->user()->courses->contains($course))
```

* **Alasan Fatal Mengapa Dilarang:**
  1. **Kebocoran Memori & Skalabilitas:** Jika ada 10.000 mata kuliah di database, server memuat 10.000 objek model ke dalam RAM PHP hanya untuk menampilkan 3 mata kuliah milik mahasiswa.
  2. **Paginasi Hancur (*Broken Pagination*):** Pagination bawaan Laravel (`paginate(15)`) menghitung offset di database. Jika disaring di Blade, halaman 1 mungkin hanya menampilkan 1 data, halaman 2 kosong, dan halaman 3 menampilkan 2 data.
  3. **Bahaya Kebocoran Data (*Security Leak*):** Data seluruh mata kuliah tetap terkirim ke layer presentasi. Jika developer khilaf di satu baris Blade, data privat kelas lain seketika terekspos ke layar pengguna.
* **Solusi Wajib:**
  Penyaringan wajib dilakukan di **level SQL database (*Query Scoping*)**:
  ```php
  $query = match ($user->role) {
      'admin' => Course::query(),
      'dosen' => $user->taughtCourses(),
      'mahasiswa' => $user->courses(),
  };
  ```

---

### 8. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

* **Bagian yang Dikerjakan:**
  Penyelesaian kendala benturan nama rute (*Route Name Collision*) antara rute API dan rute Web di `routes/api.php` dan `bootstrap/app.php`.
* **Saran Awal dari AI:**
  AI sempat menyarankan untuk membuat exception handler kustom atau memodifikasi middleware Laravel untuk mengabaikan method GET pada URL login.
* **Apa yang Saya Ubah & Analisis Kritis Saya (*What I Changed & Why*):**
  Saya menolak solusi tersebut karena tidak menyelesaikan akar masalah (*root cause*). 
  Akar masalah sebenarnya adalah rute API:
  ```php
  Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
  ```
  Menimpa nama rute web `name('login')` (`/login`), sehingga pemanggilan helper `route('login')` di web controller mengarahkan pengguna browser ke rute API `POST`.
  Saya mengubahnya secara definitif dengan memberikan nama unik khusus API:
  ```php
  Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.login');
  ```
  Dan di `ci.yml`, saya menyuntikkan konfigurasi password runner CI secara dinamis (`sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=password/' .env`) agar `.env.example` tetap bersih tanpa membocorkan kata sandi rahasia sesuai aturan dosen. Hal ini membuat pipeline pengujian CI 100% hijau tanpa merusak spesifikasi kontrak API Bagian 5 maupun alur login Web.
