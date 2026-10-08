# Catatan Praktikum Minggu 6

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: 

Pada tahap ini, saya membandingkan arsitektur aplikasi berbasis web (Blade) yang sudah dibuat pada minggu-minggu sebelumnya dengan arsitektur REST API berbasis Laravel Sanctum yang baru diaktifkan.

---

### 1. Aktivasi API dan Perubahan pada `bootstrap/app.php`

Pada Laravel 12, berkas rute API tidak tersedia secara default. Untuk mengaktifkannya, saya menjalankan perintah:

```bash
php artisan install:api
```

Setelah perintah dijalankan, terjadi beberapa perubahan otomatis pada proyek:
1. Terbentuk berkas baru [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php).
2. Terpasang pustaka `laravel/sanctum` beserta berkas migrasi tabel `personal_access_tokens`.
3. Pada berkas [bootstrap/app.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/bootstrap/app.php), baris konfigurasi routing otomatis ditambahkan:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

Dengan konfigurasi ini, seluruh rute yang ditulis di `routes/api.php` otomatis memiliki awalan URL `/api` dan diproses tanpa session web biasa.

---

### 2. Pembuatan Endpoint `GET /api/v1/courses` Sederhana

Saya membuat rute API untuk menampilkan daftar mata kuliah dengan struktur:

* **Rute di `routes/api.php`:**
```php
Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/courses', [App\Http\Controllers\Api\CourseController::class, 'index']);
    });
});
```

* **Controller di `app/Http/Controllers/Api/CourseController.php`:**
```php
public function index(Request $request): AnonymousResourceCollection
{
    $courses = Course::with('lecturer')
        ->withCount(['materials', 'assignments', 'students'])
        ->latest()
        ->paginate(15);

    return CourseResource::collection($courses);
}
```

* **Hasil Data yang Dikembalikan (JSON):**
Data dibungkus dengan kelas `CourseResource`, sehingga menghasilkan format JSON standar lengkap dengan informasi pagination:

```json
{
  "data": [
    {
      "id": 1,
      "code": "SI201",
      "name": "Pemrograman Web",
      "sks": 3,
      "status": "active",
      "lecturer": {
        "id": 2,
        "name": "Dr. Dosen Pengampu, M.Kom.",
        "email": "dosen@kampuslms.test",
        "role": "dosen"
      },
      "counts": {
        "materials": 3,
        "assignments": 5,
        "students": 23
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

### 3. Perbandingan `CourseController` Versi Web vs Versi API

Berikut perbandingan langsung antara controller web lama dengan controller API yang baru:

| Aspek | Versi Web (`CourseController.php`) | Versi API (`Api/CourseController.php`) |
|---|---|---|
| **Bentuk Output** | Mengembalikan file tampilan HTML (`return view(...)`). | Mengembalikan data mentah JSON murni via API Resource (`CourseResource::collection(...)`). |
| **Identitas Pengguna** | Stateful: Mengandalkan session cookie di browser. | Stateless: Mengandalkan token Sanctum yang dikirim di header setiap request. |
| **Proteksi CSRF** | Wajib menyertakan token `@csrf` pada setiap request POST/PUT. | Tidak memerlukan CSRF karena tidak memakai session cookie browser. |
| **Penanganan Error** | Redirect balik ke halaman form dengan flash message error di session. | Mengembalikan kode status HTTP langsung (401, 403, 422) beserta pesan error JSON. |
| **Penyajian Data** | Variabel dikirim langsung ke Blade dan dirender server. | Data disaring lewat whitelist resource agar tidak membocorkan kolom rahasia database. |
| **Persamaan** | Keduanya sama-sama memakai kueri Eloquent, pagination, dan eager loading (`with(...)`) untuk mencegah beban kueri berulang (N+1). |

---

### 4. Perbedaan Pemanggilan: Tanpa Header vs Dengan Header `Accept: application/json`

Saya menguji perbedaan perilaku server saat memanggil endpoint privat `GET /api/v1/courses` tanpa membawa token login:

#### A. Tanpa Header `Accept: application/json` (Akses langsung dari URL browser)
* **Permintaan:** Browser mengirim header default `Accept: text/html...`.
* **Perilaku Server:** Laravel mengira permintaan datang dari pengguna web biasa yang sedang browsing. Saat mendeteksi pengguna belum login, Laravel secara otomatis berusaha mengarahkan (redirect) browser ke halaman login web (`route('login')`).
* **Hasil:** Karena fitur form login web belum dipasang, server melempar error `Route [login] not defined` dengan status HTTP 500 (sebelum ditambahkan penanganan khusus di `bootstrap/app.php`).

#### B. Dengan Header `Accept: application/json` (Akses resmi klien API / curl)
* **Permintaan:** Header secara eksplisit meminta respon dalam format JSON.
* **Perilaku Server:** Laravel menyadari bahwa klien adalah aplikasi API (bukan browser web biasa). Server tidak melakukan redirect sama sekali.
* **Hasil:** Server langsung merespon dengan status **HTTP 401 Unauthorized** dan payload JSON yang bersih:
```json
{
  "message": "Unauthenticated."
}
```

**Kesimpulan:** Header `Accept: application/json` sangat wajib disertakan oleh aplikasi frontend/klien API agar server selalu merespon dengan status HTTP dan struktur JSON yang terprediksi, bukan me-redirect halaman.

---

### 5. Pemeriksaan Daftar Rute API (`php artisan route:list --path=api`)

Berikut salinan hasil perintah terminal untuk memeriksa seluruh rute API yang telah didaftarkan pada proyek:

```text
+-----------+----------------------------------------------+------------------------------------+---------------------------------------------+------------+
| Method    | URI                                          | Name                               | Action                                      | Middleware |
+-----------+----------------------------------------------+------------------------------------+---------------------------------------------+------------+
| POST      | api/v1/assignments                           |                                    | Api\AssignmentController@store              | auth:sanctum, throttle:60,1 |
| PUT|PATCH | api/v1/assignments/{assignment}              |                                    | Api\AssignmentController@update             | auth:sanctum, throttle:60,1 |
| DELETE    | api/v1/assignments/{assignment}              |                                    | Api\AssignmentController@destroy            | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/assignments/{assignment}/submissions  |                                    | Api\AssignmentController@submissions        | auth:sanctum, throttle:60,1 |
| POST      | api/v1/assignments/{assignment}/submissions  |                                    | Api\AssignmentController@storeSubmission    | auth:sanctum, throttle:60,1 |
| POST      | api/v1/auth/login                            | login                              | Api\AuthController@login                    | throttle:5,1 |
| POST      | api/v1/auth/logout                           |                                    | Api\AuthController@logout                   | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses                               |                                    | Api\CourseController@index                  | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses/{course}                      |                                    | Api\CourseController@show                   | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses/{course}/assignments          |                                    | Api\CourseController@assignments            | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses/{course}/materials            |                                    | Api\CourseController@materials              | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/me                                    |                                    | Api\AuthController@me                       | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/notifications                         |                                    | Api\NotificationController@index            | auth:sanctum, throttle:60,1 |
| POST      | api/v1/notifications/{id}/read               |                                    | Api\NotificationController@markAsRead       | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/submissions/{submission}              |                                    | Api\SubmissionController@show               | auth:sanctum, throttle:60,1 |
| PUT       | api/v1/submissions/{submission}/grade         |                                    | Api\SubmissionController@grade              | auth:sanctum, throttle:60,1 |
+-----------+----------------------------------------------+------------------------------------+---------------------------------------------+------------+
```

Total terdapat **16 endpoint API aktif**. Seluruh endpoint di atas cocok 100% dengan kontrak Bagian 5 Spesifikasi Proyek KampusLMS, terbagi dalam grup versi `/v1`, dan telah diproteksi dengan rate limiting serta middleware Sanctum.

---
## Bagian BREAK:

1. **Kembalikan `response()->json(User::all())` di satu endpoint uji**
   - **Prediksi:** Seluruh data pengguna termasuk hash kata sandi akan tampil bebas di layar respon JSON.
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka [app/Models/User.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Models/User.php), beri tanda komentar `//` pada properti `$hidden` untuk mensimulasikan kondisi developer lupa memasang penyaring kolom model:
        ```php
        // protected $hidden = [
        //     'password',
        //     'remember_token',
        // ];
        ```
     2. Buka [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php), tambahkan rute uji coba sementara di bawah rute login (di luar middleware `auth:sanctum`):
        ```php
        Route::get('/test-bocor', function () {
            return response()->json(\App\Models\User::all());
        });
        ```
     3. Jalankan perintah uji di terminal:
        ```bash
        curl http://127.0.0.1:8000/api/v1/test-bocor
        ```
     4. Amati struktur data JSON yang dikembalikan server.
     5. Kembalikan kode: aktifkan kembali `$hidden` di `User.php` (hapus tanda `//`), hapus rute uji `/test-bocor`, dan gunakan API Resource `UserResource::collection(User::all())`.
   - **Yang Sebenarnya:**
     * **Kondisi Rusak (Model Mentah tanpa `$hidden`):** Seluruh data sensitif pengguna bocor secara terbuka. Kolom `"password"` yang berisi hash bcrypt (`$2y$12$...`), kolom `remember_token`, serta metadata internal (`email_verified_at`, `created_at`, `updated_at`) terpapar langsung di JSON. Peretas dapat menyedot hash ini untuk dibongkar kata sandinya secara offline.
     * **Kondisi Aman (Menggunakan API Resource):** Respon disaring ketat melalui sistem *whitelist*. Hanya atribut yang sengaja kita daftarkan (`id`, `name`, `email`, `role`) yang keluar ke respon JSON. Kolom password dan data rahasia lainnya tidak pernah diikutsertakan.

---

2. **Hapus `auth:sanctum` dari grup route, panggil tanpa token**
   - **Prediksi:** Endpoint data privat (daftar mata kuliah) dapat diakses bebas oleh siapa saja tanpa autentikasi.
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php), hapus sementara `'auth:sanctum'` dari grup middleware rute privat:
        ```php
        Route::middleware(['throttle:60,1'])->group(function () {
        ```
     2. Jalankan perintah curl tanpa menyertakan token:
        ```bash
        curl -i -H "Accept: application/json" http://127.0.0.1:8000/api/v1/courses
        ```
     3. Amati status HTTP dan data yang diterima.
     4. Kembalikan kode: pasang kembali `'auth:sanctum'` ke dalam array middleware:
        ```php
        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        ```
     5. Jalankan ulang perintah curl yang sama untuk memverifikasi proteksinya kembali aktif.
   - **Yang Sebenarnya:**
     * **Kondisi Rusak (Tanpa Middleware auth:sanctum):** Server merespon dengan status **HTTP 200 OK** dan seluruh daftar mata kuliah kampus beserta data dosen pengampunya terbuka lebar untuk publik tanpa login.
     * **Kondisi Aman (Dengan Middleware auth:sanctum):** Pintu API langsung terkunci rapat. Server merespon dengan status **HTTP 401 Unauthorized** dan pesan JSON `{"message": "Unauthenticated."}` karena tidak menemukan Bearer token valid pada header request.

---

3. **Panggil endpoint terlindungi dengan token yang sudah dihapus**
   - **Prediksi:** Permintaan ditolak dengan status HTTP 401 Unauthorized karena token yang dipakai sudah dihapus dari database saat logout.
   - **Cara Uji (Mencoba Kondisi Normal):**
     1. Login menggunakan akun dosen untuk mendapatkan Bearer token aktif:
        ```bash
        curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
          -H "Content-Type: application/json" \
          -H "Accept: application/json" \
          -d '{"email":"dosen@kampuslms.test","password":"password"}'
        ```
        Salin string token aktif yang didapat di respon (contoh: `16|hsRc...`).
     2. Uji panggil endpoint mata kuliah membawa token tersebut untuk membuktikan token aktif (server merespon **HTTP 200 OK**):
        ```bash
        curl -i -H "Authorization: Bearer <TOKEN_DOSEN>" \
          -H "Accept: application/json" \
          http://127.0.0.1:8000/api/v1/courses
        ```
     3. Lakukan logout resmi membawa token tersebut untuk mencabut token dari database:
        ```bash
        curl -i -X POST http://127.0.0.1:8000/api/v1/auth/logout \
          -H "Authorization: Bearer <TOKEN_DOSEN>" \
          -H "Accept: application/json"
        ```
        Server merespon `{"message": "Berhasil keluar."}` dan mengeksekusi penghapusan token via `$request->user()->currentAccessToken()->delete()`.
     4. Coba panggil kembali endpoint courses menggunakan token lama yang sudah di-logout tadi:
        ```bash
        curl -i -H "Authorization: Bearer <TOKEN_DOSEN>" \
          -H "Accept: application/json" \
          http://127.0.0.1:8000/api/v1/courses
        ```
   - **Yang Sebenarnya:**
     * Server langsung menolak request dengan status **HTTP 401 Unauthorized** dan pesan JSON `{"message": "Unauthenticated."}`.
     * Ini membuktikan bahwa proses pencabutan token pada tabel `personal_access_tokens` berjalan efektif dan token lama yang sudah dicabut tidak dapat dipakai ulang (*mencegah serangan token replay*).

---

4. **Login sebagai mahasiswa, panggil `POST /api/v1/assignments`**
   - **Prediksi:** Permintaan harus ditolak dengan status HTTP 403 Forbidden (bukan 401) karena mahasiswa sudah terautentikasi resmi, namun peran mahasiswa tidak memiliki hak akses untuk membuat tugas.
   - **Cara Uji (Mencoba Skenario Otorisasi Peran):**
     1. Login menggunakan akun mahasiswa untuk mendapatkan Bearer token mahasiswa:
        ```bash
        curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
          -H "Content-Type: application/json" \
          -H "Accept: application/json" \
          -d "{\"email\":\"mahasiswa@kampuslms.test\",\"password\":\"password\"}"
        ```
        Salin string token mahasiswa yang didapat (contoh: `18|28Lp...`).
     2. Kirim request pembuatan tugas baru membawa token mahasiswa tersebut:
        ```bash
        curl -i -X POST http://127.0.0.1:8000/api/v1/assignments \
          -H "Authorization: Bearer <TOKEN_MAHASISWA>" \
          -H "Accept: application/json" \
          -H "Content-Type: application/json" \
          -d "{\"course_id\":1,\"title\":\"Tugas_Uji_Mahasiswa\",\"due_date\":\"2026-10-30\"}"
        ```
     3. Amati status HTTP dan respon JSON yang dikembalikan server.
   - **Yang Sebenarnya:**
     * Server langsung merespons dengan status **HTTP 403 Forbidden** dan pesan:
       ```json
       {
         "message": "Anda tidak memiliki akses ke sumber daya ini."
       }
       ```
     * Ini membuktikan bahwa otorisasi peran bekerja efektif di controller API: identitas mahasiswa dikenali (bukan 401 Unauthorized), tetapi wewenang perannya dibatasi dengan tegas (403 Forbidden) karena pembuatan tugas hanya boleh dilakukan oleh dosen pengampu atau admin.

---

5. **Hapus eager loading pada endpoint daftar mata kuliah (Masalah N+1 Query)**
   - **Prediksi:** Menghapus eager loading (`with('lecturer')`) akan menyebabkan masalah N+1 Query (database dipaksa menjalankan kueri berulang kali untuk setiap baris data yang diambil), atau relasi dosen tidak muncul di JSON karena diproteksi oleh fungsi `whenLoaded()`.
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka [app/Http/Controllers/Api/CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/CourseController.php), pada method `index()` ubah kueri sementara menjadi tanpa eager loading:
        ```php
        $query = Course::query();
        ```
     2. Jalankan perintah curl untuk memanggil endpoint courses dengan Bearer token aktif:
        ```bash
        curl -i -H "Authorization: Bearer <TOKEN_ANDA>" \
          -H "Accept: application/json" \
          http://127.0.0.1:8000/api/v1/courses
        ```
     3. Amati struktur respon JSON: objek `"lecturer"` tidak dimuat (kosong) dan nilai `"counts"` tidak muncul karena Resource diproteksi oleh `$this->whenLoaded('lecturer')`.
     4. Kembalikan kode: aktifkan kembali eager loading pada [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/CourseController.php):
        ```php
        $query = Course::with('lecturer')->withCount(['materials', 'assignments', 'students']);
        ```
     5. Panggil ulang curl: seluruh objek `"lecturer"` dan `"counts"` langsung tampil lengkap.
   - **Yang Sebenarnya:**
     * **Kondisi Tanpa Eager Loading:** Jika Resource tidak diproteksi `whenLoaded()`, Laravel akan mengeksekusi 1 kueri utama untuk mengambil daftar mata kuliah, lalu memicu 1 kueri terpisah untuk setiap dosen mata kuliah (total 16 kueri pada 15 data). Masalah N+1 Query ini membebani server database dan memperlambat respon API.
     * **Kondisi Aman (Eager Loading + `whenLoaded()`):** Database hanya mengeksekusi 2 kueri efisien (satu kueri utama dan satu kueri relasi dosen via `WHERE id IN (...)`). Kolom relasi hanya disertakan jika memang sudah dimuat di controller.

---

6. **Hapus `throttle` dari login, jalankan percobaan berturut-turut**
   - **Prediksi:** Jika middleware `throttle` dihapus dari rute login, server akan memproses percobaan login tanpa batas (celah serangan brute force terbuka). Sebaliknya, jika `throttle:5,1` aktif, server akan membatasi maksimal 5 percobaan per menit dan menolak percobaan berikutnya dengan status HTTP 429 Too Many Requests.
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php), hapus sementara middleware throttle pada rute login:
        ```php
        Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
        ```
     2. Jalankan pengujian loop request login gagal berturut-turut di terminal:
        ```bash
        for i in {1..10}; do
          curl -s -o /dev/null -w "%{http_code}\n" -X POST http://127.0.0.1:8000/api/v1/auth/login \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d '{"email":"admin@kampuslms.test","password":"passwordsalah"}'
        done
        ```
        Hasilnya: Seluruh 10 request terus-menerus mengembalikan kode status `422`. Tidak ada pemblokiran sama sekali.
     3. Kembalikan kode: pasang kembali middleware `throttle:5,1` pada [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php):
        ```php
        Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
        ```
     4. Jalankan kembali perintah loop yang sama:
        ```bash
        for i in {1..10}; do
          curl -s -o /dev/null -w "%{http_code}\n" -X POST http://127.0.0.1:8000/api/v1/auth/login \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d '{"email":"admin@kampuslms.test","password":"passwordsalah"}'
        done
        ```
        Hasilnya: 5 request pertama mengembalikan status `422`, dan tepat pada request ke-6 hingga ke-10, server langsung memblokir dengan status `429`.
     5. Cek detail header dan respon HTTP 429 dengan curl lengkap:
        ```bash
        curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
          -H "Content-Type: application/json" \
          -H "Accept: application/json" \
          -d '{"email":"admin@kampuslms.test","password":"passwordsalah"}'
        ```
   - **Yang Sebenarnya:**
     * **Kondisi Rusak (Tanpa Throttle):** Server melayani serangan tanpa batas. Header pembatas rate limiting (`X-RateLimit-*`) tidak ada, sehingga penyerang bebas menjalankan skrip bot brute force atau credential stuffing untuk menebak kata sandi tanpa hambatan.
     * **Kondisi Aman (Dengan `throttle:5,1`):** Laravel memproteksi endpoint login secara ketat. Pengguna hanya diizinkan mencoba 5 kali per menit. Pada percobaan ke-6, Laravel langsung merespons dengan status **HTTP 429 Too Many Requests**, menyertakan header `Retry-After: 60`, dan mengembalikan pesan JSON `{"message": "Too Many Attempts."}`.

---

7. **Buat pesan login berbeda untuk email salah vs password salah (*User enumeration*)**
   - **Prediksi:** Membedakan pesan kesalahan login (misalnya "Email tidak terdaftar" vs "Kata sandi salah") akan membuka celah keamanan *user enumeration*, di mana pihak luar dapat memetakan dan mengetahui alamat email siapa saja yang terdaftar di dalam sistem database KampusLMS.
   - **Cara Uji (Mencoba Kondisi Rusak & Normal):**
     1. Buka [app/Http/Controllers/Api/AuthController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/AuthController.php), pada method `login()` ubah validasi kredensial sementara menjadi dua blok terpisah:
        ```php
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak terdaftar di sistem kami.'],
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Kata sandi yang Anda masukkan salah.'],
            ]);
        }
        ```
     2. Uji kondisi rusak dengan cURL di terminal:
        * **Uji email palsu (tidak terdaftar):**
          ```bash
          curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d '{"email":"tidakada@kampuslms.test","password":"sembarang"}'
          ```
          Server mengembalikan: `{"message":"Email tidak terdaftar di sistem kami.","errors":{"email":["Email tidak terdaftar di sistem kami."]}}`.
        * **Uji email valid (ada di database), tetapi password salah:**
          ```bash
          curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d '{"email":"admin@kampuslms.test","password":"sembarang"}'
          ```
          Server mengembalikan: `{"message":"Kata sandi yang Anda masukkan salah.","errors":{"password":["Kata sandi yang Anda masukkan salah."]}}`.
     3. Kembalikan kode: pulihkan pesan kredensial seragam pada [AuthController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/AuthController.php):
        ```php
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }
        ```
     4. Jalankan kembali kedua perintah cURL di atas.
   - **Yang Sebenarnya:**
     * **Kondisi Rusak (Pesan Error Dibedakan):** Terjadi kebocoran status akun (*user enumeration*). Penyerang cukup menjalankan skrip otomatis dengan kamus ribuan alamat email. Setiap kali muncul respon "Kata sandi salah", penyerang mencatat email tersebut sebagai akun valid yang aktif. Informasi ini menjadi modal berharga bagi penyerang untuk melanjutkan serangan *brute force* terarah atau *phishing*.
     * **Kondisi Aman (Pesan Error Seragam):** Server merespons kedua percobaan gagal dengan respon JSON yang identik: `{"message":"Email atau kata sandi salah.","errors":{"email":["Email atau kata sandi salah."]}}`. Penyerang sama sekali tidak mendapatkan petunjuk apakah emailnya yang tidak terdaftar atau kata sandinya yang keliru, sehingga privasi dan keberadaan akun pengguna terlindungi penuh.

---

## 6.4 Checkpoint Minggu 6

Berikut jawaban evaluasi materi Minggu 6 beserta langkah-langkah peragaannya:

### 1. Kenapa Mengembalikan Model Mentah Berbahaya? Peragakan Kebocorannya.

**Penjelasan:**
Mengembalikan model database langsung via `response()->json($user)` sangat berbahaya karena Laravel akan mengubah seluruh kolom tabel menjadi JSON tanpa disaring. Akibatnya, data rahasia seperti hash kata sandi (`$2y$12$...`) dan `remember_token` ikut terkirim ke layar pengguna luar. Jika data ini dicuri, peretas bisa menebak kata sandi aslinya di komputer lain secara offline.

**Langkah Peragaan:**
1. Tambahkan rute uji coba sementara di `routes/api.php`:
   ```php
   Route::get('/v1/test-bocor', fn () => response()->json(\App\Models\User::first()));
   ```
2. Jalankan perintah curl di terminal:
   ```bash
   curl http://127.0.0.1:8000/api/v1/test-bocor
   ```
3. **Hasil yang tampak:** Kolom `"password": "$2y$12$..."` terlihat jelas di respon JSON.
4. **Solusinya di materi:** Selalu gunakan API Resource (`UserResource`) sebagai penyaring (*whitelist*). Hanya kolom yang kita daftarkan (`id`, `name`, `email`, `role`) yang akan dikirim, sedangkan kolom kata sandi tidak pernah diikutsertakan.

---

### 2. Apa Beda 401 dan 403? Tunjukkan di API Anda Satu Contoh Masing-Masing.

**Penjelasan:**
* **401 Unauthorized:** Masalah identitas / autentikasi ("Kamu belum login atau tokenmu tidak valid").
* **403 Forbidden:** Masalah hak akses / otorisasi ("Identitasmu sah dan kamu sudah login, tetapi peranmu dilarang melakukan aksi ini").

**Langkah Peragaan di API KampusLMS:**
* **Contoh 401 (Tanpa Token):**  
  Panggil endpoint data mata kuliah tanpa membawa token login:
  ```bash
  curl -i -H "Accept: application/json" http://127.0.0.1:8000/api/v1/courses
  ```
  *Respon server:* Status **401 Unauthorized** dengan pesan `Unauthenticated.`.
* **Contoh 403 (Mahasiswa Akses Fitur Dosen):**  
  1. Login sebagai mahasiswa untuk mendapatkan token mahasiswa:
     ```bash
     curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
       -H "Content-Type: application/json" \
       -H "Accept: application/json" \
       -d '{"email":"mahasiswa@kampuslms.test","password":"password"}'
     ```
     Salin string token yang dihasilkan pada field `"token"`.
  2. Coba buat tugas baru membawa token mahasiswa tersebut:
     ```bash
     curl -i -X POST http://127.0.0.1:8000/api/v1/assignments \
       -H "Authorization: Bearer <TOKEN_MAHASISWA>" \
       -H "Accept: application/json" \
       -H "Content-Type: application/json" \
       -d '{"course_id":1,"title":"Tugas Palsu","due_date":"2026-10-30"}'
     ```
  *Respon server:* Status **403 Forbidden** dengan pesan `Anda tidak memiliki akses ke sumber daya ini.`. Server tahu Anda adalah mahasiswa, tetapi menolak karena hanya dosen pengampu atau admin yang berhak membuat tugas.

---

### 3. Kenapa `routes/api.php` Tidak Ada Secara Default di Laravel 12? Bagaimana Mengaktifkannya?

**Penjelasan:**
Laravel 12 dirancang dengan struktur awal yang sangat minimalis (*lean skeleton*). Banyak aplikasi web yang hanya menggunakan tampilan biasa (Blade) dan sama sekali tidak membutuhkan API. Dengan tidak menyertakan rute API sejak awal, aplikasi menjadi lebih ringan dan cepat karena tidak perlu memuat sistem API yang tidak dipakai.

**Cara Mengaktifkannya:**
Cukup jalankan satu perintah di terminal:
```bash
php artisan install:api
```
Perintah ini otomatis:
1. Memasang paket autentikasi `laravel/sanctum` dan tabel token database.
2. Membuat berkas baru [routes/api.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/api.php).
3. Mendaftarkan rute API ke konfigurasi [bootstrap/app.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/bootstrap/app.php).

---

### 4. Apa Fungsi `whenLoaded()`? Apa yang Terjadi Tanpanya?

**Fungsi `whenLoaded()`:**
Method `whenLoaded('namaRelasi')` pada API Resource berfungsi untuk menyertakan data relasi ke dalam respons JSON hanya jika relasi tersebut sudah dimuat (*eager loaded*) oleh controller. Jika relasi belum dimuat, atribut tersebut diabaikan dan tidak dimasukkan ke dalam JSON.

**Apa yang Terjadi Tanpanya:**
Jika data relasi dipanggil langsung tanpa `whenLoaded()` (misalnya `'lecturer' => new UserResource($this->lecturer)`):
1. **Memicu Masalah N+1 Query:** Jika controller tidak melakukan eager loading (`with('lecturer')`), Laravel akan otomatis mengeksekusi satu kueri database tambahan untuk setiap baris data yang diambil (*lazy loading*). Pada 15 data mata kuliah, terjadi 1 kueri utama + 15 kueri relasi (total 16 kueri), yang membebani server database dan memperlambat respons API.
2. **Perlindungan `whenLoaded()`:** Mencegah terjadinya lazy loading yang tidak disengaja. Database hanya menjalankan 2 kueri efisien jika eager loading dipanggil, atau 1 kueri saja jika relasi memang tidak dibutuhkan.

**Langkah Pengujian di Terminal:**

1. **Dapatkan Token Login Dosen:**
   Jalankan login untuk mendapatkan Bearer token dosen:
   ```bash
   curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"email":"dosen@kampuslms.test","password":"password"}'
   ```
   Salin string token yang dihasilkan pada field `"token"`.

2. **Uji 1: Memanggil API saat eager loading tidak dimuat di controller:**
   * Buka [app/Http/Controllers/Api/CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/CourseController.php), ubah kueri sementara:
     ```php
     $query = Course::query(); // Tanpa memanggil with('lecturer')
     ```
   * Panggil API di terminal membawa token login dosen tadi:
     ```bash
     curl -s -H "Authorization: Bearer <TOKEN_DOSEN>" \
       -H "Accept: application/json" \
       http://127.0.0.1:8000/api/v1/courses
     ```
   * **Hasil Pengamatan:** Kolom `"lecturer"` otomatis tidak disertakan di respon JSON, dan database aman dari eksekusi kueri berulang.

3. **Uji 2: Memanggil API saat eager loading diaktifkan:**
   * Kembalikan kode di [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/Api/CourseController.php):
     ```php
     $query = Course::with('lecturer')->withCount(['materials', 'assignments', 'students']);
     ```
   * Panggil kembali perintah cURL yang sama dengan token dosen:
     ```bash
     curl -s -H "Authorization: Bearer <TOKEN_DOSEN>" \
       -H "Accept: application/json" \
       http://127.0.0.1:8000/api/v1/courses
     ```
   * **Hasil Pengamatan:** Objek `"lecturer"` langsung tampil lengkap di dalam respon JSON karena datanya telah dimuat sebelumnya di controller.

---

### 5. Kenapa Pesan Gagal Login Tidak Boleh Membedakan Email Salah dan Password Salah?

**Penjelasan:**
Jika server memberikan pesan yang berbeda (contoh: "Email tidak terdaftar" vs "Password salah"), penyerang bisa memanfaatkan informasi tersebut untuk menebak akun kampus mana saja yang aktif (*User Enumeration*).

**Bahayanya:**
1. Penyerang menggunakan program bot untuk memasukkan ribuan daftar email secara otomatis.
2. Saat server menjawab "Password salah", penyerang tahu pasti bahwa akun email dosen atau mahasiswa tersebut benar-benar terdaftar di sistem KampusLMS.
3. Penyerang kemudian bisa memusatkan serangan tebak kata sandi (*brute force*) atau penipuan (*phishing*) khusus ke akun tersebut.
4. Karena itu, pesan gagal login wajib diseragamkan dengan satu kalimat netral:  
   `"Kredensial yang diberikan tidak cocok dengan data kami."`  
   Dengan kalimat ini, penyerang tidak bisa mengetahui apakah emailnya yang salah atau passwordnya yang salah.

---

### 6. Kenapa Endpoint Login Wajib di-Throttle? Berapa Nilai yang Anda Pakai dan Mengapa?

**Penjelasan:**
Endpoint login adalah pintu utama masuknya pengguna. Tanpa batasan frekuensi (*throttle*), peretas dapat menjalankan bot otomatis untuk mencoba puluhan ribu kombinasi kata sandi per detik (*serangan brute force*) sampai akun berhasil dibobol.

**Nilai yang Digunakan:**
`throttle:5,1` (maksimal 5 kali percobaan dalam 1 menit).

**Alasannya:**
1. **Aman untuk manusia:** Kesempatan 5 kali dalam 1 menit sangat cukup bagi pengguna asli yang sesekali salah mengetik kata sandi.
2. **Mematikan bot peretas:** Bagi bot penyerang, batasan 5 kali per menit membuat upaya membobol password menjadi sia-sia karena butuh waktu bertahun-tahun untuk mencoba ribuan kata sandi.
3. Begitu gagal 5 kali berturut-turut, server langsung menolak percobaan ke-6 dengan respon **HTTP 429 Too Many Requests** dan mewajibkan menunggu 60 detik sebelum boleh mencoba lagi.
