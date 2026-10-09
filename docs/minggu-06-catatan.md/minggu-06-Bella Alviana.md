# Minggu 6
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ

**1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.**
Jawab: jalankan `php artisan install:api` Laravel membuat `routes/api.php` dan memasang Sanctum untuk kebutuhan autentikasi API. Selain itu, konfigurasi pada `bootstrap/app`.php juga diperbarui agar Laravel mengenali file `api.php` sebagai tempat untuk mendefinisikan route API.

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```
Dengan begitu, route yang dibuat di api.php dapat diakses melalui awalan /api.

**2. Buat satu endpoint `GET /api/v1/courses` sederhana.**
Jawab: 
Endpoint tersebut menggunakan method `GET` dan berada pada prefix versi `API` v1.

Rute pada `routes/api.php`:
```php
Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/courses', [App\Http\Controllers\Api\CourseController::class, 'index']);
    });
});
```

Rute tersebut mengarahkan request `GET /api/v1/courses` ke method `index()` pada `CourseController`.

Proses pada `CourseController`:
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
Pada method tersebut, data course diambil menggunakan Eloquent. Relasi lecturer dimuat terlebih dahulu dan jumlah materials, assignments, serta students ikut dihitung. Data kemudian menggunakan pagination sebanyak 15 data per halaman.

Response API:

Hasilnya tidak langsung mengembalikan model Course, tetapi menggunakan CourseResource. Dengan demikian, data yang dikirim ke client mengikuti format yang sudah ditentukan oleh Resource.

Contoh sederhananya:
```php
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
Kesimpulan: endpoint ini digunakan untuk mengambil daftar mata kuliah dalam bentuk JSON. Penggunaan CourseResource membuat format data yang dikirim lebih terkontrol, sedangkan with() dan withCount() digunakan untuk mengambil data relasi dan jumlah data terkait dengan lebih efisien. Ini sesuai dengan materi READ yang meminta pembuatan endpoint GET /api/v1/courses sebelum membandingkannya dengan controller versi web.

**3. Bandingkan dengan `CourseController` versi web yang sudah ada. Tulis di catatan: apa yang sama dan apa yang berbeda di antara keduanya?**
Jawab: 
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

**4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.**
Jawab: 
memanggil endpoint privat `GET /api/v1/courses` tanpa membawa token login:

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

**5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.**
Jawab:
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

## Break

**1. Kembalikan `response()->json(User::all())` di satu endpoint uji**
Jawab:

**Langkah Percobaan** 
- Buka file `routes/api.php`
- Tambahkan sementara 
  ```php
    use App\Models\User;

    Route::get('/test-users', function () {
    return response()->json(User::all());
    });
    ```
- Panggil Endpoint `http://127.0.0.1:8000/api/test-users`
- jika `$hidden` di file `app\Models\User.php` bekerja maka akan menampilkan 
  ```text
  {"id":1,"name":"Administrator KampusLMS","email":"admin@kampuslms.test","role":"admin","nim_nip":null,"email_verified_at":"2026-09-30T11:21:16.000000Z","created_at":"2026-09-30T11:21:16.000000Z","updated_at":"2026-09-30T11:21:16.000000Z","deleted_at":null}
  ```
- `$hidden` dimatikan otomatis hash password akan ikut ditampilkan 
  ```text
  {"id":1,"name":"Administrator KampusLMS","email":"admin@kampuslms.test","password":"$2y$12$7Asu7hrDbZ.Yf1ei0\/AlAOODFBbLXBe8l1AKO9U8kexXGhNjuJi8.","role":"admin","nim_nip":null,"email_verified_at":"2026-09-30T11:21:16.000000Z","remember_token":null,"created_at":"2026-09-30T11:21:16.000000Z","updated_at":"2026-09-30T11:21:16.000000Z","deleted_at":null}
  ```

**Penjelasan** Secara default, Laravel melalui **Eloquent ORM akan mengambil seluruh kolom** dari tabel database termasuk data sensitif seperti *hash* kata sandi dan mengubahnya menjadi properti objek PHP. Dalam kondisi normal, data sensitif ini tidak bocor karena dilindungi oleh properti `$hidden` **pada model User yang berfungsi sebagai filter** otomatis sebelum data diubah menjadi teks JSON untuk response API. Namun, ketika properti `$hidden` tersebut dihapus atau dimatikan, pengaman otomatis ini **hilang**. Akibatnya, Laravel akan langsung mengubah seluruh data mentah dari database tanpa penyaringan, sehingga kata sandi ikut terekspos secara terang-terangan ke publik.

**2. Hapus `auth:sanctum` dari grup route, panggil tanpa token**
Jawab: 

**Langkah Percobaan**
- Jalankan url `http://127.0.0.1:8000/api/v1/courses` web akan menampilkan `{"message":"Unauthenticated."}` Ini membuktikan bahwa `auth:sanctum` bekerja.
- Hapus `auth::sanctum` di file `routes/api.php` dan kode akan menjadi `Route::middleware(['throttle:60,1'])->group(function () {` 
- Jalankan curl `curl -i http://127.0.0.1:8000/api/v1/courses \ -H "Accept: application/json"` dan request akan menghasilkan `HTTP/1.1 500 Internal Server Error` dengan pesan `Attempt to read property "role" on null` yang berasa dari erorr `CourseController.php`

**Penjelasan**
Ketika middleware `auth:sanctum` dihapus dari protected routes, request tanpa Bearer Token tidak lagi dihentikan pada tahap autentikasi dan dapat diteruskan ke controller. Pada pengujian ini, request kemudian menghasilkan `500 Internal Server Error` karena controller mencoba membaca `role` dari user yang bernilai `null`. Kondisi tersebut menunjukkan bahwa perlindungan autentikasi pada level route diperlukan agar request tanpa token ditolak sejak awal dengan `401 Unauthorized`.

**3. Panggil endpoint terlindungi dengan token yang sudah dihapus**
Jawab:
**Langkah Percobaan**
- Jalankan `ls database/seeders` dan `grep -R -n -i "mahasiswa\|password" database/seeders` yang berfungsi untuk mencari data mahasiswa dan password.
- Login untuk mendapatkan token
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"mahasiswa@kampuslms.test","password":"password"}'
    ```
- Salin token lalu tes
    ```bash
    curl -i http://127.0.0.1:8000/api/v1/courses \
    -H "Accept: application/json" \
    -H "Authorization: Bearer TOKEN_KAMU"
    ```
  Token masih valid `HTTP/1.1 200 OK`
- Setelah berhasil, lanjut logout menggunakan token yang sama.
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/logout \
    -H "Accept: application/json" \
    -H "Authorization: Bearer TOKEN_KAMU"
    ``` 
  akan menampilkan pesan `HTTP/1.1 200 OK` `{"message":"Berhasil keluar."}`
- Gunakan lagi token yang lama 
  ```bash
  curl -i http://127.0.0.1:8000/api/v1/courses \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_LAMA"
  ```
  token lama digunakan kembali dan akan menampilkan pesan `HTTP/1.1 401 Unauthorized`
 `{"message":"Unauthenticated."}`

**Penjelasan**
Pengujian dilakukan dengan menggunakan token Sanctum yang masih valid untuk mengakses endpoint `/api/v1/courses`. Request pertama berhasil dengan status `200 OK`. Selanjutnya dilakukan logout menggunakan token tersebut sehingga token dicabut. Ketika token lama digunakan kembali untuk mengakses endpoint yang sama, server memberikan respons `401 Unauthorized` dengan pesan `Unauthenticated`. Hasil ini menunjukkan bahwa mekanisme pencabutan token berjalan dengan baik dan token yang sudah tidak valid tidak dapat digunakan untuk mengakses endpoint yang dilindungi.

**4. Login sebagai mahasiswa, panggil POST `/api/v1/assignments`**
Jawab: 

**Langkah Percobaan**
- Patikan `auth::sanctum` di file `routes/api.php` dan jalankan di terminal untuk memunculkan token.
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"mahasiswa@kampuslms.test","password":"password"}'
    ```
- Salin token dan jalankan
    ```bash
    curl -i http://127.0.0.1:8000/api/v1/me \
    -H "Accept: application/json" \
    -H "Authorization: Bearer TOKEN_MAHASISWA"
    ```
    hasilnya akan menunjukkan `HTTP/1.1 200 OK` dan akan menampilkan data mahasiswa tersebut.
- Gunakan token mahasiswa untuk mengirim request membuat assignment `POST /api/v1/assignments`
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/assignments \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer TOKEN_MAHASISWA" \
    -d '{
    "course_id": 1,
    "title": "Pengujian Authorization",
    "description": "Assignment untuk pengujian role mahasiswa",
    "deadline": "2026-12-01 23:59:00"
    }'
    ```
    Akan menampilkan `HTTP/1.1 403 Forbidden` dan pesan `{"message":"Anda tidak memiliki akses ke sumber daya ini."}`

**Penjelasan**
Pengujian dilakukan dengan menggunakan akun mahasiswa untuk mengakses endpoint `POST /api/v1/assignments` yang memerlukan hak akses tertentu. Request dikirim menggunakan Bearer Token mahasiswa yang valid. Hasil pengujian menunjukkan respons `403 Forbidden` dengan pesan “Anda tidak memiliki akses ke sumber daya ini.” Hal ini menunjukkan bahwa proses autentikasi berhasil, tetapi sistem berhasil menolak akses karena pengguna dengan role mahasiswa tidak memiliki izin untuk membuat assignment. Dengan demikian, mekanisme authorization berdasarkan hak akses pengguna berjalan dengan baik.

**5. Hapus eager loading, panggil daftar mata kuliah, lihat Telescope/Debugbar**
Jawab: 

**Langkah Percobaan**
- Endpoint yang digunakan `GET /api/v1/courses`
- Jalankan curl ini untuk membuat token baru
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"mahasiswa@kampuslms.test","password":"password"}'
    ```
- Patikan endpoint normal yang berjalan
    ```bash
    curl -i http://127.0.0.1:8000/api/v1/courses \
    -H "Accept: application/json" \
    -H "Authorization: Bearer TOKEN_BARU"
    ```
    akan menampilkan `HTTP/1.1 200 OK`
- Buka file `app/Http/Controllers/Api/CourseController.php` pada bagian
    ```php
    $query = Course::with('lecturer')->withCount([
    'materials',
    'assignments',
    'students'
    ]);
    ```
    hapus bagian `with('lecturer')`
- Karena belum memiliki `Telescope` maupun `Debugbar` maka kita akan menggunakan `DB::listen()`
- Buka `app\Http\Controllers\Api\CourseController.php` tambahkan `use Illuminate\Support\Facades\DB;` di bagian import
- Tambahkan 
    ```php
    DB::listen(function ($query) {
    logger()->info('SQL: ' . $query->sql);
    });
    ```
    didalam method `index()` 
- Buka file `app\Http\Resources\CourseResource.php` dan sementara ubah 
    ```php
    'lecturer' => new UserResource($this->whenLoaded('lecturer')),
    ```
    menjadi 
    ```php
    'lecturer' => new UserResource($this->lecturer),
    ```
- Bersihkan log lama `> storage/logs/laravel.log` agar hasil percobaan tidak tercampur dengan log sebelumnya.
- Panggil kembali endpoint `/courses`
    ```bash
    curl -i http://127.0.0.1:8000/api/v1/courses \
    -H "Accept: application/json" \
    -H "Authorization: Bearer TOKEN_BARU"
    ```
    Patikan hasilnya `HTTP/1.1 200 OK`
- Jalankan query `tail -n 100 storage/logs/laravel.log` dan log akan menunjukkan query utama untuk mengambil courses, kemudian query lecture yang berulang.
    SQL: select count(*) as aggregate from `courses` ...

    SQL: select `courses`.*, ... from `courses` ...

    SQL: select * from `users`
    where `users`.`id` = ?
    and `users`.`deleted_at` is null
    limit 1

    SQL: select * from `users`
    where `users`.`id` = ?
    and `users`.`deleted_at` is null
    limit 1

    SQL: select * from `users`
    where `users`.`id` = ?
    and `users`.`deleted_at` is null
    limit 1

    SQL: select * from `users`
    where `users`.`id` = ?
    and `users`.`deleted_at` is null
    limit 1

    SQL: select * from `users`
    where `users`.`id` = ?
    and `users`.`deleted_at` is null
    limit 1
- Karena terdapat 5 mata kuliah, setiap `course` membutuhkan data `lecture`
   **Tanpa eager loading:**

    Query utama `courses`  
    ↓  
    Course 1 → query `lecturer`  
    Course 2 → query `lecturer`  
    Course 3 → query `lecturer`  
    Course 4 → query `lecturer`  
    Course 5 → query `lecturer`

    **Pola query:**  
    1 query utama + 5 query `lecturer` = **6 query**
    
    Hasil percobaan menunjukkan adanya `N+1 Query`. Setelah eager loading` with('lecturer')` dihapus, Laravel melakukan query lecturer secara terpisah untuk setiap mata kuliah. Hal ini menyebabkan jumlah query bertambah seiring jumlah data yang ditampilkan dan dapat menurunkan performa aplikasi.
- Dan kembalikan kode setelah percobaan.
  
**6. Hapus `throttle` dari login, jalankan 50 percobaan berturut-turut.**
Jawab:

**Langkah Percobaan**
- Endpoint yang digunakan `POST /api/v1/auth/login` 
- Buka file `routes/api.php` dan ubah sementara
    ```php
    Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');
    ```
    menjadi 

    ```php
    Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('login');
    ```
- Jalankan request yang sama berulang kali 
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"mahasiswa@kampuslms.test","password":"salah"}'
    ```
- **Hasil dari pengujian** percobaan login dengan password yang salah tetap diproses dan menghasilkan respons `422 Unprocessable Content`. Meskipun request dilakukan berkali-kali, server tidak memberikan respons `429 Too Many Requests`.
- Dan jika kita mengembalikan kode yang kita ubah tadi `throttle:5,1` fungsi ini akan membatasi percobaan login hanya 5 kali. Dengan demikian, jumlah percobaan login dapat dibatasi dan server dapat memberikan respons `429 Too Many Requests` setelah batas percobaan tercapai.

**7. Buat pesan login berbeda untuk email salah vs password salah.**
Jawab: 

**Langkah Percobaan**
- Gunakan email yang tidak terdaftar di database
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"tidakterdaftar@kampuslms.test","password":"salah"}'
    ```
- Uji email yang terdaftar dengan password yang salah
    ```bash
    curl -i -X POST http://127.0.0.1:8000/api/v1/auth/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"mahasiswa@kampuslms.test","password":"salah"}'
    ```
- Email yang tidak terdaftar menghasilkan:

    ```text
    HTTP/1.1 422 Unprocessable Content
    ```

    dengan pesan:

    ```json
    {
        "message": "Email atau kata sandi salah.",
        "errors": {
            "email": [
                "Email atau kata sandi salah."
            ]
        }
    }
    ```
- Email yang terdaftar tetapi menggunakan password yang salah juga menghasilkan respons yang sama:

    ```text
    HTTP/1.1 422 Unprocessable Content
    ```

    dengan pesan:

    ```json
    {
        "message": "Email atau kata sandi salah.",
        "errors": {
            "email": [
                "Email atau kata sandi salah."
            ]
        }
    }
    ```

**Kesimpulan:**
Pengujian menunjukkan bahwa endpoint login menggunakan pesan error yang sama untuk email yang tidak terdaftar maupun password yang salah. Dengan demikian, aplikasi **tidak membocorkan informasi mengenai keberadaan akun melalui pesan login** dan risiko **user enumeration pada endpoint tersebut tidak ditemukan**.

Selain itu, header `X-RateLimit-Limit: 5` dan `X-RateLimit-Remaining` menunjukkan bahwa mekanisme rate limiting yang sebelumnya dinonaktifkan untuk pengujian No. 6 telah aktif kembali.
