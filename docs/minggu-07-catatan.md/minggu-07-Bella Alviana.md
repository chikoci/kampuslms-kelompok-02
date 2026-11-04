# Minggu 7
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ
**1. Berkas mana yang menangani POST login? Method apa?**
**Jawab:**

- **Rute pendefinisi:** 
  Pada `routes/web.php` baris 33:
  ```php
  Route::post('/login', [AuthController::class, 'login'])->name('login.post');
  ```
- **Berkas pengendali (controller):**
  `app/Http/Controllers/AuthController.php`
- **Method:**
  Method `login(Request $request)` (baris 31 s.d. 67).

---

**2. Di baris mana `Auth::attempt()` atau setaranya dipanggil?**
**Jawab:**

`Auth::attempt()` dipanggil di berkas `app/Http/Controllers/AuthController.php` pada **baris 52**:

```php
if (Auth::attempt($credentials, $remember)) {
```

**Penjelasan:**
Method `Auth::attempt()` bertugas memvalidasi kredensial login (email dan password). Laravel mencari pengguna berdasarkan email, lalu mencocokkan password teks biasa yang diinput dengan hash password di database. Jika cocok, Laravel otomatis membuatkan sesi login aktif untuk user dan mengembalikan `true`. Parameter `$remember` digunakan untuk menentukan apakah sesi login perlu diingat lebih lama (*Remember Me*).

---

**3. Temukan `session()->regenerate()`. Kenapa ia ada di situ?**
**Jawab:**

`session()->regenerate()` ditemukan di berkas `app/Http/Controllers/AuthController.php` pada **baris 56**:

```php
// Cegah serangan Session Fixation dengan regenerasi session ID
$request->session()->regenerate();
```

**Kenapa ia ada di situ?**
Kode tersebut diletakkan setelah login berhasil untuk **mencegah serangan *Session Fixation* (pembajakan sesi)**.
Sebelum login, browser sudah memiliki sebuah ID sesi untuk tamu (*guest*). Jika ID sesi tersebut tidak diganti saat login, penyerang yang mengetahui ID sesi sebelum login bisa menggunakannya untuk masuk dan menyamar sebagai pengguna sah. Dengan `session()->regenerate()`, Laravel membuang ID sesi lama dan menerbitkan ID sesi baru yang aman dan acak bagi pengguna yang telah terautentikasi.

---

**4. Di mana kata sandi di-hash? Cari `cast hashed` di model `User`.**
**Jawab:**

Kata sandi di-hash secara otomatis menggunakan fitur casting pada model `User` di berkas `app/Models/User.php` pada method `casts()` **baris 46 s.d. 52**:

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

**Penjelasan:**
Dengan adanya casting `'password' => 'hashed'`, setiap kali atribut `password` diisi atau diperbarui (misalnya lewat `User::create([...])` atau `$user->password = ...`), Eloquent ORM secara otomatis akan mengubah password teks biasa menjadi hash yang aman (menggunakan Bcrypt/Argon2) sebelum disimpan ke database.
Selain itu, hashing kata sandi secara manual juga terdapat pada proses pendaftaran pengguna di `app/Http/Controllers/AuthController.php` baris 110 menggunakan `Hash::make($validated['password'])`.

---

**5. Buka DevTools → Cookies sebelum dan sesudah login. Bandingkan nilai cookie session.**
**Jawab:**

Pengujian dilakukan melalui Developer Tools browser (tekan `F12` -> tab **Application** / **Storage** -> menu **Cookies** -> pilih URL web `http://127.0.0.1:8000`):

- **Sebelum Login (Guest/Tamu):**
  Cookie `laravel_session` berisi string acak terenkripsi yang merepresentasikan sesi tamu (misalnya string diawali `eyJpdi...`).
- **Setelah Login Berhasil:**
  Nilai pada cookie `laravel_session` **berubah total** menjadi string acak baru yang berbeda dari nilai sebelum login.

**Alasan:**
Perubahan nilai cookie ini merupakan efek langsung dari pemanggilan `$request->session()->regenerate()` saat login berhasil, di mana server Laravel membuat ID sesi baru dan mengirimkan instruksi `Set-Cookie` ke browser untuk mengganti nilai sesi lama.

---

**6. Logout, lalu tekan tombol back. Apa yang terjadi? Kenapa?**
**Jawab:**

**Apa yang terjadi:**
Setelah pengguna logout dan diarahkan ke `/login`, jika tombol **Back** di browser ditekan, halaman sebelumnya (misal Dashboard) **tampak muncul kembali sejenak di layar**. Namun, begitu pengguna mencoba me-refresh halaman (`F5`) atau mengklik tautan/tombol apa pun, halaman tersebut langsung tertutup dan pengguna **otomatis dialihkan kembali ke halaman `/login`**.

**Kenapa hal itu terjadi?**
1. **Cache Browser (*Back-Forward Cache* / bfcache):**
   Saat menekan tombol Back, browser tidak mengirim permintaan baru ke server Laravel, melainkan hanya menampilkan salinan/tangkapan layar halaman yang tersimpan di memori cache lokal browser agar navigasi terasa instan.
2. **Sesi di Server Sudah Hangus:**
   Di sisi server Laravel, ketika tombol logout ditekan, method `logout()` pada `AuthController.php` telah mengeksekusi:
   ```php
   Auth::logout();
   $request->session()->invalidate();
   $request->session()->regenerateToken();
   ```
   Sesi login pengguna sudah dihapus permanen dari server. Saat pengguna berinteraksi (klik link/refresh) yang memicu request baru ke server, middleware `auth` mendeteksi pengguna tidak lagi memiliki sesi aktif, sehingga request ditolak dan diarahkan ke halaman login.

---

## BREAK — Delapan Kerusakan (50 menit)

Berikut analisis dan hasil pengamatan mendalam untuk setiap skenario kerusakan pada mekanisme keamanan aplikasi:

---

### 1. Hapus `session()->regenerate()` dari login
* **Target yang Diamati:** Celah keamanan *Session Fixation* terbuka.
* **Prediksi:** Jika baris pembaruan sesi ini dihilangkan, browser akan tetap mempertahankan ID sesi yang sama sebelum dan sesudah proses login berhasil.
* **Hasil Pengamatan (Sebenarnya):**
  - Saat memeriksa nilai cookie `laravel_session` di DevTools (tab *Application -> Cookies*), nilai token cookie sebelum login (status tamu/guest) dan sesudah berhasil login **sama persis dan tidak berubah sama sekali**.
  - **Dampak Bahaya:** Terjadi celah keamanan fatal *Session Fixation*. Seorang penyerang dapat menyusupkan ID sesi miliknya kepada korban (misalnya melalui tautan jebakan). Begitu korban login dengan akun aslinya, penyerang yang memegang ID sesi yang sama langsung dapat mengakses akun korban tanpa perlu mengetahui email atau kata sandi korban.

---

### 2. Hapus `Gate::authorize()` dari update, tapi biarkan `@can` di Blade
* **Target yang Diamati:** Tombol hilang di antarmuka, tetapi akses URL langsung tetap berjalan.
* **Prediksi:** Pengecekan `@can` di Blade hanya menyembunyikan tombol secara visual di antarmuka pengguna, namun tanpa `Gate::authorize()` di controller, rute `PUT /courses/{id}` tidak memiliki pertahanan di tingkat server.
* **Hasil Pengamatan (Sebenarnya):**
  - **Di Tampilan Layar:** Pengguna yang tidak berhak (misal Mahasiswa atau Dosen lain) memang **tidak melihat tombol "Edit"** karena filter visual `@can('update', $course)` berhasil menyembunyikannya.
  - **Melalui URL Langsung / cURL:** Namun, jika pengguna tersebut langsung mengakses URL `/courses/{id}/edit` melalui bilah browser atau mengirim request HTTP `PUT /courses/{id}` via cURL/Postman, request tersebut **diterima dan berhasil diproses oleh server (HTTP 200 / 302)**.
  - **Pelajaran Keamanan:** Perintah Blade `@can` hanyalah pemanis tampilan (*UI decoration*). Benteng pertahanan keamanan mutlak wajib berada di sisi controller menggunakan `Gate::authorize('update', $course)`.

---

### 3. Sebagai Dosen A, kirim PUT ke mata kuliah Dosen B lewat cURL
* **Target yang Diamati:** Pengujian *Policy* secara sungguhan (*Cross-User Authorization*).
* **Prediksi:** Sistem akan menolak permintaan pembaruan dan mengembalikan respon error otorisasi karena Dosen A bukan pemilik sah dari mata kuliah Dosen B.
* **Hasil Pengamatan (Sebenarnya):**
  - Ketika Dosen A mengirim request HTTP `PUT` ke rute mata kuliah milik Dosen B:
    ```bash
    curl -i -X PUT http://127.0.0.1:8000/courses/{id_dosen_b} \
      -b cookies_dosen_a.txt \
      -H "X-CSRF-TOKEN: <token_dosen_a>" \
      -d "name=Mata+Kuliah+Dibajak"
    ```
  - Server langsung mengembalikan respon **`HTTP 403 Forbidden`** (*This action is unauthorized*).
  - **Alasan:** Pada `CoursePolicy@update`, terdapat validasi ketat:
    `$user->role === 'admin' || $course->lecturer_id === $user->id`.
    Karena `$course->lecturer_id` adalah ID Dosen B, maka Dosen A yang bukan pemilik mata kuliah dan bukan admin langsung dicegat oleh Policy.

---

### 4. Sebagai mahasiswa, buka submission mahasiswa lain (IDOR minggu 5)
* **Target yang Diamati:** Rute sekarang harus mengembalikan status **403 Forbidden**.
* **Prediksi:** Mahasiswa yang mencoba mengganti parameter ID pengumpulan tugas pada URL `/submissions/{id}` milik mahasiswa lain akan ditolak oleh otorisasi Policy.
* **Hasil Pengamatan (Sebenarnya):**
  - Saat Mahasiswa A (ID = 3) mencoba membuka URL `/submissions/2` (yang merupakan tugas milik Mahasiswa B), layar browser menampilkan pesan **`403 | Akses Ditolak`** (*This action is unauthorized*).
  - **Alasan:** Method `SubmissionController@show` kini dipagari dengan `Gate::authorize('view', $submission)`. Pada `SubmissionPolicy@view`, akses hanya diberikan jika:
    1. Pengguna adalah Administrator (`$user->role === 'admin'`),
    2. Pengguna adalah Dosen pengampu mata kuliah terkait, atau
    3. Pengguna adalah pemilik asli dari submission tersebut (`$user->id === $submission->user_id`).
  - Karena Mahasiswa A tidak memenuhi ketiga syarat di atas, celah IDOR yang pada minggu-minggu sebelumnya sempat terbuka kini telah tertutup sepenuhnya.

---

### 5. Ubah index menjadi `Course::paginate()` polos, login sebagai mahasiswa
* **Target yang Diamati:** Terjadi kebocoran data tingkat daftar (*List-Level Data Leak*).
* **Prediksi:** Tanpa penyaringan (*query scoping*) berbasis pengguna yang sedang login, seluruh data dari tabel mata kuliah akan ditarik dari database dan ditampilkan ke siapa saja.
* **Hasil Pengamatan (Sebenarnya):**
  - Ketika mahasiswa login dan membuka halaman `/courses`, mahasiswa tersebut dapat melihat **seluruh daftar mata kuliah di seluruh kampus**, termasuk mata kuliah semester atas, jurusan lain, atau kelas yang tidak diikutinya (*enrolled*).
  - **Pelajaran Keamanan:** Untuk mencegah kebocoran daftar (*data leak*), controller harus menerapkan *Query Scoping* di tingkat database:
    ```php
    $courses = match ($user->role) {
        'admin'     => Course::query(),
        'dosen'     => $user->taughtCourses(),
        'mahasiswa' => $user->courses(),
    }->paginate(10);
    ```
    Menampilkan data polos `Course::paginate()` tanpa filter peran adalah pelanggaran prinsip hak akses terkecil (*Principle of Least Privilege*).

---

### 6. Kirim `role=admin` pada form edit profil
* **Target yang Diamati:** Pengujian celah *Mass Assignment* dan eskalasi hak akses (*Privilege Escalation*).
* **Prediksi:** Input manipulatif `role=admin` yang diselipkan dari luar tidak boleh mengubah peran pengguna di database.
* **Hasil Pengamatan (Sebenarnya):**
  - Saat pengguna biasa memodifikasi form edit profil (misalnya menambahkan `<input name="role" value="admin">` melalui Inspect Element) lalu menyimpan data:
  - Nilai kolom `role` milik pengguna tersebut di database **tetap bernilai `mahasiswa` atau `dosen` dan tidak berubah menjadi `admin`**.
  - **Alasan:** Pada model `User.php`, atribut `role` **tidak didaftarkan di dalam properti `$fillable`**:
    ```php
    protected $fillable = [
        'name',
        'email',
        'password',
        'nim_nip',
    ];
    ```
    Eloquent ORM secara otomatis mengabaikan field yang tidak ada di `$fillable` saat proses Mass Assignment (`$user->update($request->all())`), sehingga penyerang gagal menaikkan hak akses dirinya sendiri.

---

### 7. Ganti cast `hashed` menjadi tidak ada, buat user baru, lihat kolom password di database
* **Target yang Diamati:** Kata sandi tersimpan dalam bentuk teks polos (*plain text*) di database.
* **Prediksi:** Jika deklarasi `'password' => 'hashed'` di model `User.php` dihapus dan pembuatan user menggunakan `$user = User::create(['password' => 'rahasia123'])` tanpa `Hash::make()`, kata sandi tidak akan diacak.
* **Hasil Pengamatan (Sebenarnya):**
  - Ketika data pada tabel `users` diperiksa melalui database viewer (phpMyAdmin / Tinker), kolom `password` untuk user baru tersebut berisi string teks biasa: `"rahasia123"`, bukan string acak panjang berawalan `$2y$12$...` (hash Bcrypt).
  - **Dampak Bahaya:** Sangat berbahaya bagi privasi dan keamanan pengguna. Jika database mengalami kebocoran (*data breach* / dump), penyerang dapat langsung membaca kata sandi asli pengguna tanpa memerlukan proses *brute-force* atau *cracking*. Cast `'password' => 'hashed'` melindungi aplikasi dari kelalaian developer yang lupa mengenkripsi password.

---

### 8. Login, salin cookie session, tempel di browser lain
* **Target yang Diamati:** Pembajakan Sesi (*Session Hijacking*) dan pentingnya atribut `HttpOnly` serta `Secure`.
* **Prediksi:** Karena server Laravel hanya mengenali sesi berdasarkan nilai token pada cookie `laravel_session`, browser lain yang menyalin nilai cookie tersebut akan langsung dikenali sebagai pengguna yang sama tanpa login.
* **Hasil Pengamatan (Sebenarnya):**
  - Setelah menyalin nilai `laravel_session` dari Browser A dan menempelkannya ke dalam cookie Browser B (misal Firefox atau jendela Incognito), saat halaman `/dashboard` di-refresh di Browser B, **halaman dashboard langsung terbuka dan pengguna terotentikasi penuh tanpa memasukkan email maupun password**.
  - **Pelajaran Keamanan (Kenapa cookie wajib `HttpOnly` & `Secure`):**
    1. **`HttpOnly`:** Mencegah cookie dibaca atau dicuri oleh skrip JavaScript pihak ketiga (misalnya akibat celah Cross-Site Scripting / XSS). Dengan `HttpOnly`, cookie tidak dapat diakses melalui `document.cookie`.
    2. **`Secure`:** Memastikan cookie hanya dapat dikirimkan melalui protokol jaringan terenkripsi HTTPS, sehingga tidak bisa disadap (*sniffing*) saat pengguna terhubung ke jaringan internet publik/WiFi terbuka.
    3. **`SameSite=Lax/Strict`:** Membatasi cookie agar tidak dikirim otomatis pada request lintas situs (*cross-site*), melindungi pengguna dari serangan CSRF.