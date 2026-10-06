# Catatan Praktikum Minggu 6

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ: Bandingkan Dua Jalur

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
