# Catatan Praktikum Minggu 4
**Nama:** Armansyah  
**NIM:** 10241013

---

# READ — Bandingkan dua jalur  

## 1.Jalankan php artisan install:api. Baca perubahan yang terjadi di bootstrap/app.php.
**Latar belakang:** Di Laravel 12, `routes/api.php` tidak dibuat otomatis
karena aplikasi baru dibuat ramping. Berkas ini harus diaktifkan manual.

**Perintah:**  
php artisan install:api  

**Yang dilakukan perintah ini (dari output terminal):**
- `composer.json` diperbarui dan `laravel/sanctum` v4.3.3 dipasang
- Berkas `routes/api.php` dipublikasikan ("Published API routes file")
- Satu migrasi baru dipublikasikan, lalu dijalankan (tabel
  `personal_access_tokens`, tempat token Sanctum disimpan)

**Perubahan yang saya periksa lewat `git status` / `git diff`:**
| Berkas | Perubahan | Fungsi |
|---|---|---|
| routes/api.php | berkas baru | tempat mendefinisikan route API (prefix /api) |
| bootstrap/app.php |  pada WithRouting ditambahkan  `api:__DIR__.'/../routes/api.php'` | membuat Laravel memuat route API |
| migrasi personal_access_tokens | berkas baru | menyimpan token Bearer |
| composer.json / composer.lock | Sanctum ditambahkan | dependensi autentikasi token |
| app/Models/User.php | [isi: trait HasApiTokens ditambah manual] | memberi method createToken() |

### Kesimpulan READ 1
- Sanctum terpasang, tabel token ada, trait HasApiTokens terpasang di User
- Fondasi API siap untuk membuat endpoint GET /api/v1/courses (READ langkah 2)

## 2.Buat satu endpoint GET /api/v1/courses sederhana.
**Yang dibuat (3 bagian):**
| Bagian | Berkas | Peran |
|---|---|---|
| Route | routes/api.php | GET /v1/courses → CourseController@index (/api otomatis dari bootstrap/app.php, v1 ditulis sendiri) |
| Controller | Api/CourseController.php | mengambil data: Course::paginate(15) |
| Resource | CourseResource | menentukan kolom yang keluar (Daftar Putih) : id, code, name, sks, status |

Alur: Request → Route → Controller → (Model + Resource) → JSON

**Verifikasi route:**  
`php artisan route:list --path=api`
 
`GET|HEAD  api/v1/courses ... Api\CourseController@index`

**Pengujian**:  
`curl http://kampuslms-kelompok-02.test/api/v1/courses \-H "Accept: application/json"`

**Hasil**:  
``` 
{
  "data": [
    {"id":1,"code":"SI201","name":"Pemrograman Web","sks":3,"status":"active"},
    ...
  ],
  "links": {"first":"...?page=1","last":"...?page=1","prev":null,"next":null},
  "meta": {"current_page":1,"per_page":15,"total":6,"last_page":1,...}
}
```

**Pengamatan**:
- Struktur otomatis data / links / meta karena memakai paginate()
- Hanya 5 kolom yang keluar (Resource = daftar putih)
- Total 6 mata kuliah, semua status active
- ID melompat 5 → 10 karena ada data uji yang pernah dihapus
- Domain yang dipakai: kampuslms-kelompok-02.test (bukan kampuslms.test)

## 3.Bandingkan dengan CourseController versi web yang sudah ada. Tulis di catatan: apa yang sama dan apa yang berbeda diantara keduanya?

| Aspek                      | Web (`CourseController.php`)                                                                                                 | API (`Api/CourseController.php`)                                                                                                                           |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Namespace**              | `App\Http\Controllers`                                                                                                       | `App\Http\Controllers\Api`                                                                                                                                 |
| **Tujuan**                 | Menangani permintaan dari pengguna melalui **halaman web** dan menampilkan tampilan HTML.                                    | Menyediakan **data melalui API** agar dapat digunakan oleh aplikasi lain, seperti frontend atau mobile app.                                                |
| **Bentuk Output**          | HTML yang dirender menggunakan **Blade**. Contoh: `return view(...)`.                                                        | JSON yang dikirim melalui **API Resource**. Contoh: `CourseResource::collection(...)`.                                                                     |
| **Identitas Pengguna**     | **Stateful**, menggunakan session dan cookie browser untuk mengenali pengguna yang sedang login.                             | **Stateless**, menggunakan token **Bearer/Sanctum** yang dikirim pada setiap request.                                                                      |
| **Proteksi CSRF**          | Menggunakan CSRF karena request berasal dari browser dan menggunakan session cookie. Form biasanya menggunakan `@csrf`.      | Tidak menggunakan CSRF untuk autentikasi token API karena autentikasi tidak bergantung pada session cookie browser.                                        |
| **Query `index()`**        | Menggunakan `with('lecturer')`, pencarian `q`, filter `status`, `latest()`, dan `paginate(15)->withQueryString()`.           | Menggunakan `Course::paginate(15)`. Jika dikembangkan lebih lanjut, dapat ditambahkan eager loading dan filter seperti versi web.                          |
| **Eager Loading**          | Sudah menggunakan `with('lecturer')` untuk mengambil data dosen sekaligus dan menghindari query berulang atau **N+1 Query**. | Awalnya belum menggunakan eager loading, tetapi dapat ditambahkan `with('lecturer')` agar pengambilan data relasi lebih efisien.                           |
| **Pencarian & Filter**     | Sudah tersedia, misalnya pencarian berdasarkan nama/kode dan filter berdasarkan status.                                      | Belum tersedia pada versi yang dibandingkan.                                                                                                               |
| **Pagination**             | Menggunakan `paginate(15)` dan `withQueryString()` agar parameter pencarian/filter tetap terbawa saat berpindah halaman.     | Menggunakan `paginate(15)` untuk membatasi jumlah data yang dikirim setiap halaman.                                                                        |
| **Method yang Tersedia**   | Lengkap untuk CRUD: `index`, `create`, `store`, `show`, `edit`, `update`, dan `destroy`.                                     | Pada versi yang dibandingkan hanya memiliki `index`.                                                                                                       |
| **Method Form**            | Memiliki `create` dan `edit` karena perlu menampilkan halaman form untuk menambah dan mengubah data.                         | Tidak memiliki `create` dan `edit` karena API hanya mengirim/menerima data, bukan menampilkan halaman form.                                                |
| **Penyimpanan Data**       | `store()` menyimpan data lalu mengarahkan pengguna kembali ke halaman web.                                                   | Jika tersedia, `store()` akan menerima request API dan mengembalikan data serta status HTTP dalam format JSON.                                             |
| **Update Data**            | `update()` mengubah data kemudian melakukan redirect dan memberikan flash message.                                           | `update()` mengubah data dan mengembalikan response JSON beserta status HTTP.                                                                              |
| **Hapus Data**             | `destroy()` menghapus data lalu melakukan redirect dan memberikan flash message.                                             | `destroy()` menghapus data dan biasanya mengembalikan status `204 No Content` jika berhasil.                                                               |
| **Response Berhasil**      | Biasanya berupa **redirect** ke halaman lain disertai flash message, misalnya `"Course berhasil ditambahkan"`.               | Menggunakan response HTTP dan JSON, misalnya `201 Created` untuk data baru, `200 OK` untuk permintaan berhasil, atau `204 No Content` setelah penghapusan. |
| **Validasi Berhasil**      | Data divalidasi menggunakan `StoreCourseRequest` atau `UpdateCourseRequest` sebelum disimpan.                                | Data divalidasi dan jika tidak valid, API mengembalikan error dalam format JSON.                                                                           |
| **Validasi Gagal**         | Pengguna diarahkan kembali ke form dan error disimpan di session sehingga dapat ditampilkan menggunakan `@error`.            | Mengembalikan **HTTP 422 Unprocessable Entity** beserta informasi error dalam JSON.                                                                        |
| **Penanganan Error**       | Lebih berorientasi pada tampilan web, yaitu redirect kembali ke halaman sebelumnya dan menampilkan pesan error.              | Lebih berorientasi pada aplikasi, yaitu mengirimkan status HTTP dan pesan error dalam format JSON.                                                         |
| **Otorisasi**              | Menggunakan pengecekan role atau kepemilikan data, misalnya `abort_unless(..., 403)` pada `edit`, `update`, dan `destroy`.   | Pada versi yang dibandingkan **belum memiliki otorisasi** dan perlu ditambahkan jika endpoint API dibatasi berdasarkan role atau kepemilikan data.         |
| **Keamanan Data**          | Data dikirim ke Blade sesuai kebutuhan halaman.                                                                              | Data sebaiknya melewati **`CourseResource`** sehingga hanya field yang memang diperbolehkan yang dikirim ke client dan field sensitif tidak ikut terbuka.  |
| **Cara Mengakses**         | Diakses melalui URL halaman web dan digunakan oleh browser.                                                                  | Diakses melalui endpoint API, misalnya `/api/courses`, dan dapat digunakan oleh frontend, mobile app, atau client lainnya.                                 |
| **Cara Kerja Request**     | Browser mengirim request, server memprosesnya, kemudian server mengembalikan halaman HTML atau redirect.                     | Client mengirim request, server memprosesnya, kemudian server mengembalikan data/status dalam JSON.                                                        |
| **Kesamaan**               | Sama-sama menggunakan **Eloquent** untuk mengambil dan mengelola data dari database.                                         | Sama-sama menggunakan **Eloquent** untuk mengambil dan mengelola data dari database.                                                                       |
| **Kesamaan Pagination**    | Sama-sama dapat menggunakan pagination untuk membatasi jumlah data yang ditampilkan/dikirim.                                 | Sama-sama dapat menggunakan pagination untuk membatasi jumlah data yang ditampilkan/dikirim.                                                               |
| **Kesamaan Eager Loading** | Sama-sama dapat menggunakan `with(...)` untuk mengambil relasi secara lebih efisien.                                         | Sama-sama dapat menggunakan `with(...)` untuk mengambil relasi secara lebih efisien.                                                                       |


**Yang sama:** sama-sama bertumpu pada model Course dan paginate(15);
inti pekerjaannya (mengambil daftar mata kuliah) tidak berubah.

**Yang beda:** hanya pembungkus keluarannya (view Blade vs Resource JSON),
cara menangani sukses/gagal (redirect + flash vs status code + JSON), dan
cara identitas dikenali (session vs token). Controller API saya juga masih
jauh lebih sederhana daripada versi web (belum ada filter, urutan, eager
loading, dan otorisasi).

**Temuan:**
- Pengecekan peran di controller web dibungkus `if (auth()->check())`,
  sehingga pengguna belum login tidak terkena pengecekan jika route tidak
  dilindungi middleware auth. [isi: hasil cek route:list, ada middleware
  auth atau tidak]

**Kesimpulan:** Logika query sebaiknya ditulis sekali (mis. scope di model)
dan dipakai web maupun API, sedangkan controller hanya menentukan format
keluarannya. Dengan begitu perubahan aturan bisnis cukup dilakukan di satu
tempat.

## 4.Panggil endpoint API tanpa header Accept: application/json. Lalu dengan header itu. Catat bedanya.
Setup: route /api/v1/courses dilindungi `auth:sanctum` (sementara),
dipanggil tanpa token.

Perintah:  
curl -s -o /dev/null -w "%{http_code} | %{content_type}\n" \ http://kampuslms-kelompok-02.test/api/v1/courses

curl -s -o /dev/null -w "%{http_code} | %{content_type}\n" \ http://kampuslms-kelompok-02.test/api/v1/courses \ -H "Accept: application/json"

| | Tanpa Accept | Dengan Accept: application/json |
|---|---|---|
| Status | 500 | 401 |
| Content-Type | text/html; charset=utf-8 | application/json |
| Isi respons | halaman error HTML (debug page Laravel) | JSON Unauthenticated |
| Penyebab | [isi: Route [login] not defined / belum diselidiki] | token tidak ada |

**Kenapa berbeda:** saat autentikasi gagal, Laravel memeriksa apakah
klien mengharapkan JSON (expectsJson(), terutama dari header Accept).
Tanpa header, request dianggap browser, sehingga Laravel mencoba
mengarahkan ke halaman login. Dengan header, dianggap klien API, sehingga
dijawab 401 JSON.

**Kesimpulan:** klien API wajib mengirim Accept: application/json.
Tanpanya, error datang sebagai HTML (bahkan 500 yang menyesatkan, padahal
masalah sebenarnya hanya "belum login") dan tidak bisa diproses klien API.

## 5.Jalankan php artisan route:list --path=api. Cocokkan dengan kontrak di spesifikasi.
Berikut salinan hasil perintah terminal untuk memeriksa seluruh rute API yang telah didaftarkan pada proyek:

```text
+-----------+----------------------------------------------+------------------------------------+---------------------------------------------+-----------------------------+
| Method    | URI                                          | Name                               | Action                                      | Middleware                  |
+-----------+----------------------------------------------+------------------------------------+---------------------------------------------+-----------------------------+ 
| POST      | api/v1/assignments                           |                                    | Api\AssignmentController@store              | auth:sanctum, throttle:60,1 |
| PUT|PATCH | api/v1/assignments/{assignment}              |                                    | Api\AssignmentController@update             | auth:sanctum, throttle:60,1 |
| DELETE    | api/v1/assignments/{assignment}              |                                    | Api\AssignmentController@destroy            | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/assignments/{assignment}/submissions  |                                    | Api\AssignmentController@submissions        | auth:sanctum, throttle:60,1 |
| POST      | api/v1/assignments/{assignment}/submissions  |                                    | Api\AssignmentController@storeSubmission    | auth:sanctum, throttle:60,1 |
| POST      | api/v1/auth/login                            | login                              | Api\AuthController@login                    | throttle:5,1                |
| POST      | api/v1/auth/logout                           |                                    | Api\AuthController@logout                   | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses                               |                                    | Api\CourseController@index                  | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses/{course}                      |                                    | Api\CourseController@show                   | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses/{course}/assignments          |                                    | Api\CourseController@assignments            | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/courses/{course}/materials            |                                    | Api\CourseController@materials              | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/me                                    |                                    | Api\AuthController@me                       | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/notifications                         |                                    | Api\NotificationController@index            | auth:sanctum, throttle:60,1 |
| POST      | api/v1/notifications/{id}/read               |                                    | Api\NotificationController@markAsRead       | auth:sanctum, throttle:60,1 |
| GET|HEAD  | api/v1/submissions/{submission}              |                                    | Api\SubmissionController@show               | auth:sanctum, throttle:60,1 |
| PUT       | api/v1/submissions/{submission}/grade        |                                    | Api\SubmissionController@grade              | auth:sanctum, throttle:60,1 |
+-----------+----------------------------------------------+------------------------------------+---------------------------------------------+-----------------------------+
```

Total terdapat **16 endpoint API aktif**. Seluruh endpoint di atas cocok 100% dengan kontrak Bagian 5 Spesifikasi Proyek KampusLMS, terbagi dalam grup versi `/v1`, dan telah diproteksi dengan rate limiting serta middleware Sanctum.

---
# BREAK — Tujuh kerusakan

## 1. Kembalikan response()->json(User::all()) di satu endpoint uji
**Tujuan:** melihat sendiri data apa saja yang bocor kalau model
dikembalikan langsung tanpa API Resource.

#### 1. Kode yang ditambahkan  
Di `routes/api.php` (di dalam grup `v1`, di luar `auth:sanctum`):

```php
Route::get('/uji-bocor', fn () => response()->json(\App\Models\User::all()));
```

Verifikasi route terdaftar:

```bash
php artisan route:list --path=uji-bocor
# GET|HEAD  api/v1/uji-bocor ... routes/api.php:24
```

Catatan: URL-nya `api/v1/uji-bocor` (bukan `api/uji-bocor`) karena route
berada di dalam grup `prefix('v1')`. Percobaan pertama ke
`/api/uji-bocor` menghasilkan 404 untuk alasan ini.

#### 2. Pengujian

```bash
curl -s http://kampuslms-kelompok-02.test/api/v1/uji-bocor \
  -H "Accept: application/json" | head -c 1200
```

Request dikirim **tanpa token**.

#### 3. Hasil pengamatan

Seluruh pengguna tampil. Contoh dua objek pertama:

```json
{"id":1,"name":"Administrator KampusLMS","email":"admin@kampuslms.test",
 "role":"admin","nim_nip":null,"email_verified_at":"2026-09-14T12:43:52.000000Z",
 "created_at":"...","updated_at":"...","deleted_at":null}

{"id":2,"name":"Dr. Dosen Pengampu, M.Kom.","email":"dosen@kampuslms.test",
 "role":"dosen","nim_nip":"198501012010121001","email_verified_at":"...",
 "created_at":"...","updated_at":"...","deleted_at":null}
```

| Yang bocor | Contoh |
|---|---|
| Email semua pengguna | admin@kampuslms.test, dosen@kampuslms.test, mahasiswa@kampuslms.test |
| Role | admin, dosen, mahasiswa |
| NIM/NIP | 198501012010121001 (dosen), 2201010001 (mahasiswa) |
| Nama lengkap | Dr. Dosen Pengampu, M.Kom. |
| Metadata | email_verified_at, created_at, updated_at, deleted_at |

Temuan penting:
- Hash `password` dan `remember_token` **tidak tampil**.
- Data tetap keluar **tanpa token** karena route berada di luar
  `auth:sanctum`.

#### 4. Penjelasan

1. **Kenapa hash password tidak tampil di percobaan utama:** model `User`
   punya `$hidden = ['password', 'remember_token']`, sehingga kedua kolom
   itu disembunyikan saat model diubah menjadi JSON. Itu pengaman bawaan
   Laravel, **bukan** bukti bahwa mengembalikan model mentah itu aman.
2. **Kenapa tetap berbahaya:** `$hidden` hanya melindungi kolom yang
   didaftarkan. Kolom custom seperti `role`, `nim_nip`, dan `deleted_at`
   tidak ada di `$hidden`, sehingga ikut keluar. Setiap kolom baru yang
   sensitif dan lupa didaftarkan akan otomatis bocor.
3. **Dampaknya:** penyerang langsung tahu email admin dan dosen beserta
   perannya, target paling bernilai untuk brute force dan phishing. Daftar
   email yang valid juga mempermudah user enumeration (lihat BREAK 7).
4. **Kebocoran berlapis:** route ini bisa diakses tanpa token, jadi
   masalahnya dua sekaligus: model mentah (BREAK 1) dan tanpa autentikasi
   (BREAK 2).

#### 6. Perbaikan

- Gunakan API Resource sebagai daftar putih:

```php
  class UserResource extends JsonResource
  {
      public function toArray(Request $request): array
      {
          return [
              'id'   => $this->id,
              'name' => $this->name,
              'role' => $this->role,
          ];
      }
  }
```

- Lindungi route dengan `auth:sanctum`.
- Jangan pernah mengembalikan `User::all()` ke klien; kalau memang perlu
  daftar pengguna, batasi per peran dan per kebutuhan.

**Pelajaran:** Resource = daftar putih (hanya yang disebut yang keluar),
sedangkan `$hidden` = daftar hitam (semua keluar kecuali yang disebut).
Daftar putih lebih aman karena kolom baru tidak bocor secara default.

## 2. Hapus auth:sanctum dari grup route, panggil tanpa token
#### 1. Kode yang diubah (routes/api.php)

```php
// sebelum
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
// sesudah
Route::middleware(['throttle:60,1'])->group(function () {
```

Kendala: percobaan pertama memakai `[/*'auth:sanctum'*/, 'throttle:60,1']`
dan menghasilkan FatalError "Cannot use empty array elements in arrays",
karena koma tertinggal di luar komentar. Error sintaks di routes/api.php
membuat seluruh aplikasi (termasuk artisan) gagal memuat route.

#### 2. Pengujian (tanpa token)

```bash
curl -i http://kampuslms-kelompok-02.test/api/v1/courses/1 -H "Accept: application/json"
curl -i http://kampuslms-kelompok-02.test/api/v1/me -H "Accept: application/json"
```

#### 3. Hasil pengamatan

| Endpoint | Sebelum (auth:sanctum) | Sesudah (dihapus) |
|---|---|---|
| GET /courses/1 | 401 | 500: Attempt to read property "role" on null (CourseController.php:89) |
| GET /me | 401 | 500: Attempt to read property "id" on null (UserResource.php:18) |

Header X-RateLimit-Limit: 60 tetap ada (throttle masih jalan).

#### 4. Penjelasan

- Tanpa `auth:sanctum`, request tidak ditolak di depan, tetapi masuk ke
  `controller` dengan `$request->user()` bernilai null.
- Dua endpoint di atas gagal (500) karena kode memakai ->role / ->id pada
  user null. Itu perlindungan kebetulan, bukan desain: endpoint yang tidak
  memakai user() akan membuka data ke publik.
- Klien menerima 500 (salah server) padahal masalahnya belum login
  (seharusnya 401).
- APP_DEBUG=true membocorkan path file, nama class, dan baris kode.
  Di produksi harus false.
- Throttle hanya membatasi laju, bukan siapa yang boleh masuk.

#### 5. Perbaikan

Pasang kembali auth:sanctum di grup route. Jangan mengandalkan
pengecekan di dalam controller sebagai pengganti autentikasi.

## 3. Panggil endpoint terlindungi dengan token yang sudah dihapus
**Yang dicoba:** login → pakai token → logout → pakai token yang sama lagi.

```bash
# 1. token valid
curl -i http://kampuslms-kelompok-02.test/api/v1/me -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"

# 2. logout (cabut token)
curl -i -X POST http://kampuslms-kelompok-02.test/api/v1/auth/logout -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"

# 3. pakai token yang sama lagi
curl -i http://kampuslms-kelompok-02.test/api/v1/me -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```

**Hasil:**

| Langkah | Status | Isi |
|---|---|---|
| GET /me (token valid) | 200 | profil dosen |
| POST /auth/logout | 200 | `{"message":"Berhasil keluar."}` |
| GET /me (token yang sama) | 401 | `{"message":"Unauthenticated."}` |

**Kesimpulan:** token yang sama memberi hasil berbeda sebelum dan sesudah logout. Artinya logout mencabut token di server (barisnya dihapus dari tabel `personal_access_tokens`), bukan hanya menghapusnya di sisi klien. Jadi token yang bocor atau disalin tidak bisa dipakai lagi setelah logout.

**Kendala:** token berformat `id|string`, jadi header harus diberi tanda kutip (`-H "Authorization: Bearer ..."`). Tanpa kutip, bash membaca `|` sebagai pipe dan muncul error `command not found`.

## 4. Login sebagai mahasiswa, panggil POST /api/v1/assignments
**Yang dicoba:** login sebagai mahasiswa, lalu POST ke endpoint khusus dosen.

```bash
curl -i -X POST http://kampuslms-kelompok-02.test/api/v1/assignments -H "Accept: application/json" -H "Authorization: Bearer <TOKEN_MAHASISWA>" -d "course_id=1" -d "title=Uji" -d "deadline=2026-12-31"
```

**Hasil:** `403 Forbidden` (JSON). Pesan: [isi dari curl -s di atas].

**Kesimpulan:** 403, bukan 401, karena token valid (identitas dikenali) tetapi
mahasiswa tidak berhak membuat tugas. Bukan 422, jadi pengecekan peran dilakukan
sebelum validasi. Otorisasi dijaga di sisi API, bukan hanya di frontend.

## 5. Hapus eager loading, panggil daftar mata kuliah, lihat Telescope/Debugbar
## 6. Hapus throttle dari login, jalankan 50 percobaan berturut-turut
## 7. Buat pesan login berbeda untuk email salah vs password salah
---
# CHECKPOINT MINGGU 6
### 1. Kenapa mengembalikan model mentah berbahaya? Peragakan kebocorannya.

**Jawaban:** Mengembalikan model mentah berbahaya **karena semua kolom ikut terkirim ke pengguna**, termasuk yang seharusnya tidak boleh dilihat. Kalau nanti ada kolom baru di database, kolom itu juga otomatis bocor tanpa kita sadari.

Solusinya adalah **API Resource**. Resource itu seperti daftar putih: hanya kolom yang saya tulis di `toArray()` yang keluar."

**Peragaan:**  
1) Di `routes/api.php`, buat route uji: `Route::get('/uji-bocor', fn () => response()->json(\App\Models\User::all()));`
2) Panggil: `curl http://kampuslms-kelompok-02.test/api/v1/uji-bocor -H "Accept: application/json"`
3) Tunjukkan bahwa password (hash) dan email semua user tampil.

Kalau ditanya kenapa hash password tidak tampil:

"Itu karena `$hidden` bawaan model User. Tapi `$hidden` hanya melindungi kolom yang didaftarkan, sedangkan kolom lain seperti role dan nim_nip tetap bocor."

### 2. Apa beda 401 dan 403? Tunjukkan di API Anda satu contoh masing-masing.
**Jawaban:**   
401 = identitas tidak dikenali (belum login atau token tidak valid).  
403 = identitas dikenali, tetapi tidak berhak.

**Peragaan:**  
- **401: panggil tanpa token.**  
`curl -i http://kampuslms-kelompok-02.test/api/v1/courses -H "Accept: application/json"`

- **403: login sebagai mahasiswa, ambil tokennya, lalu panggil endpoint khusus dosen.**   
cara untuk menampilkan token -> `curl -s -X POST http://kampuslms-kelompok-02.test/api/v1/auth/login -H "Accept: application/json" -d "email=mahasiswa@kampuslms.test" -d "password=password" `

  403 -> `curl -i -X POST http://kampuslms-kelompok-02.test/api/v1/assignments -H "Accept: application/json" -H "Authorization: Bearer <token_mahasiswa>"`

### 3. Kenapa routes/api.php tidak ada secara default di Laravel 12? Bagaimana mengaktifkannya?
**Jawaban:**  
Laravel 12 dibuat ramping, sehingga berkas API dan Sanctum tidak dipasang sampai dibutuhkan. Aktifkan dengan `php artisan install:api`, yang membuat `routes/api.php`, memasang Sanctum, dan menambah migrasi tabel token. Setelah itu `bootstrap/app.php` memuat route API dengan prefix /api.

**Peragaan:**  
tunjukkan routes/api.php, baris api: ... di bootstrap/app.php (git diff), dan php artisan route:list --path=api.

### 4. Apa fungsi whenLoaded()? Apa yang terjadi tanpanya?
**Jawaban:** `whenLoaded()` menyertakan relasi di Resource hanya jika relasi itu sudah di-eager load oleh controller. Tanpanya, mengakses $this->lecturer memicu query baru untuk setiap baris (masalah N+1): 15 data menjadi 1 + 15 query.

### 5. Kenapa pesan gagal login tidak boleh membedakan email salah dan password salah?
**Jawaban:** Karena pembedaan itu memungkinkan user enumeration: penyerang bisa mencoba banyak email dan mencatat mana yang terdaftar, lalu memakainya untuk brute force atau phishing yang lebih terarah. Gunakan satu pesan untuk semua kegagalan, misalnya "Email atau kata sandi salah."

Kalau pesannya dibedakan, penyerang bisa tahu email mana yang terdaftar, tinggal menebak passwordnya. Itu namanya user enumeration. Karena itu semua kegagalan login harus memakai satu pesan yang sama, yaitu 'Email atau kata sandi salah'.
### 6. Kenapa endpoint login wajib di-throttle? Berapa nilai yang Anda pakai dan mengapa?
**Jawaban:**  
Tanpa throttle, siapa pun bisa mencoba ribuan kombinasi password per menit (brute force). Nilai yang dipakai: throttle:5,1 untuk login (5 percobaan per menit), karena pengguna sah jarang salah lebih dari beberapa kali sedangkan tebakan massal jadi tidak praktis. Endpoint umum throttle:60,1. Setelah melewati batas, server membalas 429.

**Peragaan:**   
panggil login 6 kali berturut-turut dengan password salah, lalu tunjukkan percobaan ke-6 menghasilkan 429.