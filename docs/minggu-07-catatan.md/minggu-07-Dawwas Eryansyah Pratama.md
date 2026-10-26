# Minggu 07

**Nama:** Dawwas Eryansyah Pratama  

**NIM:** 10241019  

---

## 1. READ — Bedah Starter Kit & Mekanisme Keamanan

### 1. Berkas mana yang menangani POST login? Method apa?

Permintaan `POST /login` pada aplikasi KampusLMS diarahkan oleh rute yang terdaftar pada file `routes/web.php` di baris ke-33:

```php
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
```

Dari konfigurasi rute tersebut:
* **Berkas Controller:** `app/Http/Controllers/AuthController.php`
* **Method Penangan:** `public function login(Request $request)`

Ketika pengguna menekan tombol "Masuk" pada form login:
1. Permintaan dikirim dengan metode HTTP `POST` menuju endpoint `/login`.
2. Controller pertama-tama memvalidasi input formulir (memastikan format `email` valid dan `password` terisi).
3. Controller menerapkan *rate limiting* dengan membatasi maksimal 5 kali percobaan login gagal dalam 1 menit (`RateLimiter::tooManyAttempts($throttleKey, 5)`) untuk menangkal serangan *brute force*.

---

### 2. Di baris mana `Auth::attempt()` atau setaranya dipanggil?

Method `Auth::attempt()` dipanggil pada file `app/Http/Controllers/AuthController.php` di **baris ke-52**:

```php
$credentials = $request->only('email', 'password');
$remember = $request->boolean('remember');

if (Auth::attempt($credentials, $remember)) {
    RateLimiter::clear($throttleKey);

    // Cegah serangan Session Fixation dengan regenerasi session ID
    $request->session()->regenerate();

    return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
}
```

**Cara Kerja `Auth::attempt()`:**
1. Menerima array kredensial (berupa `email` dan `password` teks mentah masukan form).
2. Laravel secara otomatis mencari baris data pengguna di tabel `users` berdasarkan nilai `email`.
3. Mengambil nilai hash password yang tersimpan di basis data.
4. Memverifikasi kecocokan antara password masukan form dengan password hash menggunakan fungsi `Hash::check()` (algoritma Bcrypt).
5. Jika cocok, Laravel secara otomatis membuat sesi otentikasi login untuk pengguna tersebut dan mengembalikan nilai `true`. Jika salah, mengembalikan nilai `false` dan mencatat kegagalan di *Rate Limiter*.

---

### 3. Temukan `session()->regenerate()`. Kenapa ia ada di situ?

Perintah `session()->regenerate()` ditemukan pada file `app/Http/Controllers/AuthController.php` di **baris ke-56**, tepat setelah blok kondisi `Auth::attempt()` bernilai sukses.

```php
if (Auth::attempt($credentials, $remember)) {
    RateLimiter::clear($throttleKey);

    // Cegah serangan Session Fixation dengan regenerasi session ID
    $request->session()->regenerate();
```

**Kenapa baris tersebut wajib ada di situ?**
Baris tersebut berfungsi sebagai pertahanan krusial untuk mencegah serangan **Session Fixation (Pembajakan Sesi)**.

* **Skenario Bahaya jika Tanpa `session()->regenerate()`:**
  1. Penyerang membuka website KampusLMS terlebih dahulu untuk memperoleh Session ID pengunjung anonim (misalnya bernilai `SESSION_12345`).
  2. Penyerang menyebarkan link jebakan atau menyuntikkan cookie `SESSION_12345` ke browser korban.
  3. Korban membuka website tersebut dan berhasil login menggunakan akun pribadinya.
  4. Jika sistem **tidak** memperbarui session ID saat login, maka status hak akses korban yang sudah terotentikasi akan menempel pada `SESSION_12345` lama.
  5. Penyerang yang masih memegang `SESSION_12345` tinggal me-refresh browsernya dan otomatis langsung masuk ke dalam akun korban (*Session Hijacking*) tanpa perlu tahu kata sandi korban.

* **Solusi dengan `session()->regenerate()`:**
  Begitu kredensial terbukti valid, Laravel seketika menghancurkan Session ID lama dan menerbitkan Session ID baru yang benar-benar acak. Akibatnya, Session ID lama yang dipegang penyerang seketika kedaluwarsa dan serangannya gagal total.

---

### 4. Di mana kata sandi di-hash? Cari cast `hashed` di model `User`.

Di Laravel 12, proses hashing kata sandi dilakukan secara otomatis pada model `App\Models\User` yang terletak di berkas `app/Models/User.php` pada **baris ke-46 sampai 52**:

```php
/**
 * Casting atribut menggunakan method standar Laravel 12.
 *
 * @return array<string, string>
 */
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

**Penjelasan Cara Kerjanya:**
* Pada method `casts()`, dideklarasikan aturan `'password' => 'hashed'`.
* Fitur ini secara otomatis mengaktifkan mekanisme *mutator hashing* bawaan Laravel. Setiap kali atribut `$user->password` diberi nilai string teks polos (baik saat `User::create()`, `$user->password = ...`, maupun melalui factory/seeder), Laravel akan langsung membungkus dan meng-enkripsi kata sandi tersebut menggunakan algoritma hashing satu arah (Bcrypt dengan cost factor 12) sebelum perintah SQL `INSERT` atau `UPDATE` dikirimkan ke database.
* Dengan mekanisme ini, pengembang tidak perlu lagi memanggil `Hash::make()` secara manual berulang-ulang, dan risiko kata sandi tersimpan dalam bentuk teks polos (*plain text*) di basis data dapat dicegah sepenuhnya.

---

### 5. Buka DevTools → Cookies sebelum dan sesudah login. Bandingkan nilai cookie session.

Pengujian dilakukan menggunakan browser dengan membuka menu **Developer Tools (F12)** &rarr; tab **Application** (atau **Storage**) &rarr; bagian **Cookies** pada URL `http://127.0.0.1:8000`:

| Kondisi | Nama Cookie | Nilai Cookie (Contoh Pengamatan) | Atribut Keamanan | Keterangan |
|---|---|---|---|---|
| **Sebelum Login (Tamu / Guest)** | `laravel_session` | `eyJpdiI6InhGSD...` (ID Sesi Tamu) | `HttpOnly`, `SameSite=Lax`, `Path=/` | Sesi berumur sementara untuk menyimpan state pengunjung anonim |
| **Setelah Login (Akun Mahasiswa / Dosen)** | `laravel_session` | `eyJpdiI6IlpWQT...` (**Berubah Total**) | `HttpOnly`, `SameSite=Lax`, `Path=/` | Sesi resmi pengguna yang sudah terotentikasi dan terikat ke data user |

**Hasil Analisis:**
1. **Perubahan String Cookie:** Nilai pada cookie sesi berubah 100% secara total saat berpindah dari sebelum login menjadi sesudah login. Ini merupakan bukti konkret di sisi browser bahwa pemanggilan method `session()->regenerate()` di controller bekerja dengan sempurna.
2. **Flag `HttpOnly` Aktif:** Cookie ini memiliki flag `HttpOnly`, yang artinya nilai sesi tersebut dikunci oleh browser sehingga tidak dapat dibaca atau dicuri oleh skrip jahat JavaScript (melindungi pengguna dari serangan *Cross-Site Scripting* / XSS).

---

### 6. Logout, lalu tekan tombol *back*. Apa yang terjadi? Kenapa?

**Langkah Pengujian yang Dilakukan:**
1. Login menggunakan akun `mahasiswa@kampuslms.test`.
2. Masuk ke halaman `/dashboard`.
3. Menekan tombol "Keluar" (Logout), yang mengirimkan request `POST /logout`.
4. Sistem mengarahkan kembali ke halaman `/login` disertai pesan notifikasi: *"Anda telah berhasil keluar."*
5. Menekan tombol **Back (Kembali)** pada navigasi peramban browser.

**Apa yang Terjadi:**
* Tampilan visual halaman dashboard sebelumnya mungkin sempat muncul sekilas karena peramban membaca memori singgahan lokal (*Back-Forward Cache / bfcache*).
* Namun, ketika pengguna mencoba mengklik tautan apa saja, membuka menu mata kuliah, atau melakukan refresh halaman, sistem **langsung menolak dan mengarahkan kembali secara paksa ke halaman `/login`**.

**Kenapa Hal Tersebut Terjadi?**
Hal ini terjadi karena pada proses logout di `AuthController::logout()` baris ke-73 sampai 80:

```php
public function logout(Request $request)
{
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
}
```

1. `Auth::logout()` mencabut status login pengguna dari memori server.
2. `$request->session()->invalidate()` menghapus seluruh data sesi yang tersimpan di server dan memusnahkan session ID yang bersangkutan.
3. `$request->session()->regenerateToken()` mereset token CSRF baru.

Ketika ada permintaan baru dikirimkan ke server, middleware `auth` memeriksa tiket sesi ke database/session store server. Karena tiket sesi tersebut sudah dimusnahkan secara permanen, server menolak akses dan me-redirect pengguna ke rute login. Tampilan sesaat sebelum refresh hanyalah arsip visual di browser pengguna, bukan berarti pengguna masih memiliki sesi aktif di server.

---

## 2. BREAK — Delapan Kerusakan & Eksperimen Keamanan (50 menit)

Berikut dokumentasi dan pengujian 8 skenario perusakan yang saya telusuri dan buktikan pada aplikasi KampusLMS:

### 1. Hapus `session()->regenerate()` dari login
* **Yang Dilakukan:** Memberi komentar pada baris `$request->session()->regenerate();` di `AuthController.php`.
* **Prediksi:** Nilai Session ID browser tidak akan berganti sebelum dan sesudah login.
* **Hasil Pengamatan:** Nilai cookie `laravel_session` di Application DevTools sebelum login bernilai sama persis dengan setelah login.
* **Kesimpulan:** Celah *Session Fixation* terbuka lebar. Penyerang dapat menyuntikkan ID sesi awal ke korban dan langsung membajak akun korban setelah korban login tanpa perlu mengetahui password.

---

### 2. Hapus `Gate::authorize()` dari `update`, tapi biarkan `@can` di Blade
* **Yang Dilakukan:** Pada `CourseController@update` dan `edit`, baris `Gate::authorize('update', $course);` dinonaktifkan. Namun di file `resources/views/courses/show.blade.php`, tombol edit tetap dibungkus `@can('update', $course)`.
* **Prediksi:** Tombol edit hilang dari pandangan pengguna biasa di browser, namun jika pengguna menembak URL secara langsung, sistem tetap mengeksekusinya.
* **Hasil Pengamatan:** Saat login sebagai Mahasiswa di browser, tombol "Edit Mata Kuliah" memang tidak ada di tampilan kelas. Namun, saat mahasiswa mengetik langsung alamat URL `http://localhost:8000/courses/1/edit` di peramban, form edit **berhasil terbuka secara bebas**.
* **Pelajaran Terpenting:** Direktif `@can` di Blade **hanyalah pemanis antarmuka (UX)** untuk kenyamanan visual, bukan benteng keamanan. Keamanan hakiki mutlak wajib dipasang di sisi backend controller melalui `Gate::authorize()`.

---

### 3. Sebagai Dosen A, kirim PUT ke mata kuliah Dosen B lewat `curl`
* **Yang Dilakukan:** Menguji integritas otorisasi pada Controller dan Policy. Dosen A (`dosen@kampuslms.test` - pengampu Course 1) mengirimkan permintaan modifikasi data ke Course 2 (milik Dosen B).
* **Perintah Uji:**
  ```bash
  curl -s -o /dev/null -w "%{http_code}" -b cookie_dosenA.txt -X PUT \
    http://127.0.0.1:8000/courses/2 \
    -d "name=Sabotase Nama Kelas&code=SI202&sks=3&status=active"
  ```
* **Hasil Aktual:** Mengembalikan HTTP Status **`403 Forbidden`**.
* **Kesimpulan:** `CoursePolicy@update` berhasil menahan serangan. Server memvalidasi `$user->id === $course->lecturer_id`. Karena ID dosen tidak cocok, eksekusi pembaruan ditolak.

---

### 4. Sebagai Mahasiswa, buka submission mahasiswa lain (IDOR Minggu 5)
* **Yang Dilakukan:** Mahasiswa 1 (`mahasiswa@kampuslms.test`) mencoba membuka URL detail pengumpulan tugas milik rekan kelasnya di `/submissions/2`.
* **Perintah Uji:**
  ```bash
  curl -s -o /dev/null -w "%{http_code}" -b cookie_mhs.txt \
    http://127.0.0.1:8000/submissions/2
  ```
* **Hasil Aktual:** Mengembalikan HTTP Status **`403 Forbidden`**.
* **Kesimpulan:** Celah IDOR utama tertutup rapat oleh `SubmissionPolicy@view`. Mahasiswa hanya dapat membuka berkas pengumpulan yang memiliki `user_id === auth()->id()`.

---

### 5. Ubah `index` menjadi `Course::paginate()` polos, login sebagai mahasiswa
* **Yang Dilakukan:** Mengubah kueri penyaringan di `CourseController@index` dari `match ($user->role)` menjadi `Course::paginate(15)` tanpa filter relasi.
* **Hasil Pengamatan:** Mahasiswa yang login dapat melihat seluruh mata kuliah di sistem, termasuk mata kuliah dari program studi atau kelas lain yang tidak ia ambil.
* **Bahaya:** Terjadi **Kebocoran Data Tingkat Daftar (*List-Level Data Leak*)**. Informasi dosen pengampu, kode kelas tertutup, dan data privat kelas lain bocor ke pengguna yang tidak berhak.
* **Solusi:** Kueri wajib disaring langsung di tingkat basis data:
  ```php
  $query = match ($user->role) {
      'admin'     => Course::query(),
      'dosen'     => $user->taughtCourses(),
      'mahasiswa' => $user->courses(),
  };
  ```

---

### 6. Kirim `role=admin` pada form edit profil (*Privilege Escalation*)
* **Yang Dilakukan:** Mengirimkan payload request pembaruan profil pengguna biasa dengan menyisipkan parameter terlarang `role=admin` via HTTP request.
* **Hasil Aktual:** Data profil (nama/email) terupdate, namun **kolom `role` di database tetap bernilai `mahasiswa`**.
* **Penyebab:** Kolom `'role'` sengaja dikeluarkan dari properti `$fillable` model `User`, dan di `UserController@update` perubahan role dipagari secara ketat oleh `Gate::allows('updateRole', $user)` yang hanya mengizinkan Admin.

---

### 7. Ganti cast `hashed` menjadi tidak ada, buat user baru, lihat kolom password di database
* **Yang Dilakukan:** Menghapus `'password' => 'hashed'` dari method `casts()` di `User.php`, lalu membuat pengguna baru dengan password `"password123"`.
* **Hasil Pengamatan di Database:** Kueri `SELECT email, password FROM users;` menampilkan password persis berupa teks mentah `"password123"` (bukan string hash acak `$2y$12$...`).
* **Bahaya:** Jika basis data bocor atau dicuri, seluruh kata sandi civitas kampus langsung terbongkar seketika tanpa perlu proses peretasan (*cracking*).

---

### 8. Login, salin cookie session, tempel di browser lain (*Session Hijacking*)
* **Yang Dilakukan:** Login sebagai Admin di Google Chrome, menyalin nilai cookie `laravel_session`, lalu menempelkannya ke browser lain (atau jendela Incognito).
* **Hasil Pengamatan:** Browser kedua langsung otomatis masuk sebagai Admin tanpa diminta memasukkan email dan password.
* **Analisis & Mitigasi:**
  Inilah alasan fundamental mengapa:
  1. Cookie sesi wajib berstatus **`HttpOnly`** agar tidak dapat dicuri oleh script jahat JavaScript melalui serangan XSS (*Cross-Site Scripting*).
  2. Cookie sesi wajib berstatus **`Secure`** di server produksi agar hanya dikirimkan lewat protokol HTTPS terenkripsi.
  3. Menggunakan atribut **`SameSite=Lax`** untuk mencegah pencurian sesi lewat serangan CSRF domain luar.

---

## 3. CHECKPOINT MINGGU 7 — Gerbang Interview Tugas 2

Berikut adalah instrumen pengujian dan jawaban komprehensif untuk persiapan sesi wawancara tugas 2:

### 1. Apa beda autentikasi dan otorisasi? Tunjukkan satu contoh masing-masing di kode Anda.

* **Autentikasi (*Authentication — "Siapa Anda?"*):**  
  Proses pembuktian identitas pengguna yang datang.  
  * *Contoh di Kode:* Di [app/Http/Controllers/AuthController.php:52](file:///d:/PROWEB/kampuslms-kelompok-02/app/Http/Controllers/AuthController.php#L52):
    ```php
    Auth::attempt($credentials, $remember);
    ```
    Memverifikasi apakah email dan password yang dimasukkan cocok dengan data di database.

* **Otorisasi (*Authorization — "Apa yang boleh Anda lakukan?"*):**  
  Proses pemeriksaan hak izin (*permission*) setelah pengguna login untuk menentukan apakah ia berhak mengakses atau mengubah data tertentu.  
  * *Contoh di Kode:* Di [app/Http/Controllers/AssignmentController.php:39](file:///d:/PROWEB/kampuslms-kelompok-02/app/Http/Controllers/AssignmentController.php#L39):
    ```php
    Gate::authorize('create', [Assignment::class, $course]);
    ```
    Meskipun pengguna sudah login (terautentikasi), jika perannya adalah mahasiswa atau dosen luar yang bukan pengampu kelas tersebut, sistem langsung melempar respon **403 Forbidden**.

---

### 2. Tunjukkan Policy yang Anda tulis. Jelaskan tiap barisnya!

Sebagai penanggung jawab modul Materi dan Penugasan, saya menulis **[app/Policies/MaterialPolicy.php](file:///d:/PROWEB/kampuslms-kelompok-02/app/Policies/MaterialPolicy.php)** dan **[app/Policies/AssignmentPolicy.php](file:///d:/PROWEB/kampuslms-kelompok-02/app/Policies/AssignmentPolicy.php)**.

Berikut penjelasan baris per baris pada `MaterialPolicy`:

```php
// 1. viewAny: Siapa saja yang login boleh melihat halaman daftar, isi data disaring controller
public function viewAny(User $user, ?Course $course = null): bool
{
    if (! $course) return true;
    if ($user->role === 'admin') return true;
    if ($user->role === 'dosen') return $user->id === $course->lecturer_id;
    if ($user->role === 'mahasiswa') return $course->students()->where('user_id', $user->id)->exists();
    return false;
}

// 2. view: Memeriksa hak membuka detail materi perkuliahan
public function view(User $user, Material $material): bool
{
    if ($user->role === 'admin') return true; // Admin punya hak akses penuh
    $course = $material->course;
    if ($user->role === 'dosen') return $user->id === $course->lecturer_id; // Dosen pengampu kelas ini
    if ($user->role === 'mahasiswa') return $course->students()->where('user_id', $user->id)->exists(); // Mahasiswa terdaftar
    return false; // Mahasiswa luar ditolak (403)
}

// 3. create: Memeriksa hak membuat materi baru
public function create(User $user, ?Course $course = null): bool
{
    if ($user->role === 'admin') return true;
    if ($user->role === 'dosen') return $course && $user->id === $course->lecturer_id; // Hanya dosen pengampu
    return false; // Mahasiswa dilarang membuat materi
}

// 4. download: Memeriksa hak unduh berkas materi
public function download(User $user, Material $material): bool
{
    return $this->view($user, $material); // Meniru hak view agar mahasiswa luar kelas tidak bisa mengunduh file
}
```

---

### 3. Kenapa `@can` di Blade tidak cukup? Peragakan dengan mengakses URL langsung.

* **Alasan:**  
  Direktif `@can` di Blade hanya bekerja di layer presentasi (HTML/Frontend) untuk menyembunyikan elemen visual seperti tombol. Menyembunyikan tombol bukanlah keamanan. Pengguna yang mengerti web dapat langsung mengetikkan alamat URL endpoint (misalnya `/courses/1/edit` atau `/courses/1/assignments/create`) di peramban atau menembaknya via Postman/curl. Tanpa `Gate::authorize()` di controller, data tetap dapat dimanipulasi.
* **Langkah Peragaan Langsung:**  
  1. Login sebagai Mahasiswa (`mahasiswa@kampuslms.test`). Buka kelas `http://localhost:8000/courses/1`. Tombol "Edit Mata Kuliah" tidak terlihat karena disembunyikan `@can`.
  2. Buka address bar browser, ketik langsung URL editnya: `http://localhost:8000/courses/1/edit` lalu tekan Enter.
  3. Sistem langsung menolak dan menampilkan halaman **403 - Akses Ditolak** karena di backend method `CourseController@edit` dipagari oleh `Gate::authorize('update', $course);`.

---

### 4. Buka `docs/keamanan.md`. Pilih satu baris, jelaskan bagaimana ia ditutup, lalu buktikan dengan `curl`.

* **Baris yang Dipilih:** Baris No. 8 pada tabel IDOR `docs/keamanan.md`:  
  Endpoint `POST /courses/{course}/assignments/create` (Pembuatan Tugas Baru).
* **Potensi Bahaya:**  
  Mahasiswa nakal atau dosen yang tidak mengampu kelas tersebut mencoba membuat tugas palsu pada mata kuliah orang lain dengan mengirim HTTP POST langsung.
* **Mekanisme Penutup Konkret:**  
  Ditutup menggunakan **`AssignmentPolicy@create`** yang dipanggil di `AssignmentController@store`:
  ```php
  Gate::authorize('create', [Assignment::class, $course]);
  ```
  Di dalam `AssignmentPolicy`, sistem memeriksa:
  ```php
  public function create(User $user, ?Course $course = null): bool
  {
      if ($user->role === 'admin') return true;
      if ($user->role === 'dosen') return $course && $user->id === $course->lecturer_id;
      return false; // Mahasiswa dilarang membuat tugas
  }
  ```
* **Bukti Pengujian cURL:**  
  Ketika mahasiswa mencoba mengirimkan request pembuatan tugas via cURL:
  ```bash
  curl -s -o /dev/null -w "%{http_code}" -b cookie_mhs.txt -X POST \
    http://127.0.0.1:8000/courses/1/assignments \
    -d "title=Tugas+Palsu&due_at=2026-10-30&max_score=100&status=published"
  ```
  **Hasil Respons:** Mengembalikan kode status HTTP **`403 Forbidden`** *(Sesuai skrip uji otorisasi test-authz.sh)*.

---

### 5. Kenapa kata sandi di-hash, bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?

* **Alasan Di-hash (Bukan Di-enkripsi):**  
  * **Enkripsi bersifat dua arah (*Two-Way*):** Teks cipher dapat didekripsi kembali menjadi teks asli jika memiliki kunci rahasianya (*secret key*). Jika server atau kunci tersebut dicuri, seluruh kata sandi pengguna langsung bocor.
  * **Hashing bersifat satu arah (*One-Way*):** Fungsi matematika searah (Bcrypt) yang secara kriptografis mustahil dikembalikan (*irreversible*) ke bentuk kata sandi aslinya.
* **Konsekuensinya untuk Fitur Lupa Password:**  
  Karena server tidak pernah menyimpan dan tidak bisa melihat teks kata sandi asli pengguna, aplikasi **tidak dapat mengirimkan kata sandi lama ke email pengguna**.  
  Sistem wajib menggunakan mekanisme **Password Reset Token**: server mengirimkan tautan dengan token acak unik berbatas waktu ke email, yang mengarahkan pengguna ke halaman pembuatan **kata sandi baru**.

---

### 6. Apa fungsi `session()->regenerate()` saat login?

Fungsi utamanya adalah memusnahkan ID sesi acak lama dan menerbitkan ID sesi baru (*fresh Session ID*) seketika saat proses login berhasil.  
Tujuannya adalah menangkal serangan **Session Fixation**: mencegah penyerang membajak akun korban menggunakan session ID pra-autentikasi yang sudah disiapkan penyerang sebelum korban login.

---

### 7. Kenapa daftar mata kuliah tidak boleh diambil semua lalu disaring di view?

Contoh kode yang salah (Anti-Pattern):
```php
// SALAH:
$courses = Course::all();
return view('courses.index', compact('courses'));
// Lalu di Blade disaring: @if(auth()->user()->courses->contains($course))
```

* **Alasan Fatal Mengapa Dilarang:**
  1. **Pemborosan Memori & Performa Buruk:** Jika ada 10.000 data mata kuliah di basis data, server memuat 10.000 objek model ke dalam RAM PHP hanya untuk menampilkan 3 mata kuliah yang diambil mahasiswa.
  2. **Paginasi Rusak (*Broken Pagination*):** Paginasi Laravel (`paginate(15)`) menghitung data di database. Jika disaring di Blade, halaman 1 mungkin hanya menampilkan 1 data, halaman 2 kosong, dan halaman 3 menampilkan 2 data.
  3. **Bahaya Kebocoran Data (*Data Leak*):** Seluruh baris data mata kuliah tetap terkirim ke browser. Jika terjadi celah di satu baris Blade, data privat kelas lain seketika terekspos.
* **Solusi Wajib:**  
  Penyaringan wajib dilakukan di **level SQL database (*Query Scoping*)**:
  ```php
  $query = match ($user->role) {
      'admin'     => Course::query(),
      'dosen'     => $user->taughtCourses(),
      'mahasiswa' => $user->courses(),
  };
  ```

---

### 8. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?

* **Bagian yang Dikerjakan:**  
  Pembuatan fungsi penyimpanan penugasan baru di `AssignmentController@store` ([app/Http/Controllers/AssignmentController.php:37-55](file:///d:/PROWEB/kampuslms-kelompok-02/app/Http/Controllers/AssignmentController.php#L37-L55)).
* **Saran Awal dari AI:**  
  AI awalnya menyarankan validasi inline biasa dan langsung menyimpan seluruh data dari formulir menggunakan `$request->all()`:
  ```php
  // Saran awal AI:
  $request->validate([...]);
  Assignment::create($request->all()); // Rentan mass assignment
  ```
* **Apa yang Saya Ubah & Kenapa (*What I Changed & Why*):**  
  Saya menolak saran tersebut dan mengubahnya dengan dua keputusan penting:
  1. **Mencegah Mass Assignment:** Saya tidak menggunakan `$request->all()`. Saya menampung data yang lolos validasi ke `$validated` dan mengisi kolom pembuat tugas langsung dari server melalui `$validated['created_by'] = auth()->id();`. Parameter luar yang tidak terdaftar otomatis dibuang.
  2. **Proteksi Otorisasi Server-Side:** AI awalnya hanya menyarankan menyembunyikan tombol buat tugas di Blade menggunakan `@can`. Saya secara mandiri menambahkan `Gate::authorize('create', [Assignment::class, $course]);` di baris pertama controller agar dosen dari kelas lain atau mahasiswa tidak dapat membuat tugas ilegal lewat tembak URL langsung.
