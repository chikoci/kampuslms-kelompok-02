# Minggu 06

**Nama:** Dawwas Eryansyah Pratama

**NIM:** 10241019

---

## 1. READ

### 1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.

`php artisan install:api`

Perintah tersebut berhasil memasang Laravel Sanctum, membuat `routes/api.php`, serta membuat migration untuk tabel `personal_access_tokens`.

di `bootstrap/app.php`, konfigurasi routing menjadi:
```
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

Penambahan:
```
api: __DIR__.'/../routes/api.php',
```

membuat Laravel dapat membaca route yang berada pada `routes/api.php`. Route API kemudian menggunakan awalan `/api`.

### 2. Buat satu endpoint `GET /api/v1/courses` sederhana.

Untuk memahami jalur API, buat endpoint sederhana pada `routes/api.php`:
```
Route::get('/v1/courses', function () {
    return response()->json([
        'message' => 'Daftar mata kuliah',
    ]);
});
```

Setelah dijalankan:
```
php artisan route:list --path=api
```

route tersebut berhasil terdaftar sebagai:
```
GET|HEAD        api/v1/courses .......................................................................... Api\CourseController@index
```

Endpoint tersebut belum mengambil data mata kuliah dari database. Pada tahap READ ini endpoint hanya digunakan untuk memahami cara kerja dasar route API dan response JSON.

### 3. Bandingkan dengan `CourseController` versi web yang sudah ada. Tulis di catatan: apa yang sama dan apa yang berbeda di antara keduanya?

Pada jalur web, project sudah memiliki `CourseController` yang digunakan untuk mengelola data mata kuliah.

Contohnya pada method `index()`:
```php
$courses = Course::query()
    ->with('lecturer')
    ...
    ->paginate(15)
    ->withQueryString();

return view('courses.index', compact('courses'));
```

Sedangkan endpoint API yang dibuat pada tahap READ masih berupa route sederhana:

```php
Route::get('/v1/courses', function () {
    return response()->json([
        'message' => 'Daftar mata kuliah',
    ]);
});
```

Perbandingannya:

| Aspek | Web | API pada tahap READ |
|---|---|---|
| Route | `/mata-kuliah` | `/api/v1/courses` |
| File route | `routes/web.php` | `routes/api.php` |
| Pengolahan | `CourseController` | Closure sederhana |
| Response | Blade/HTML | JSON |
| Database | Mengambil data `Course` | Belum mengambil database |
| Pagination | Sudah digunakan | Belum digunakan |

- Persamaannya, keduanya sama-sama merupakan jalur backend Laravel dan sama-sama dapat digunakan untuk menyediakan data mata kuliah.
- Perbedaannya, jalur web ditujukan untuk menghasilkan halaman HTML melalui Blade, sedangkan jalur API ditujukan untuk menyediakan response dalam bentuk JSON.

`CourseController` versi web tidak diubah pada tahap READ, karena digunakan sebagai bahan perbandingan dengan jalur API.

### 4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.

lakukan dua pengujian terhadap: `/api/v1/courses`

- A. Tanpa Accept: `application/json`

Endpoint dipanggil melalui browser: `http://127.0.0.1:8000/api/v1/courses-

Hasil:
```json
{
    "message": "Daftar mata kuliah"
}
```

- B. Dengan `Accept: application/json`

Endpoint dipanggil menggunakan:
```PowerShell
curl.exe -H "Accept: application/json" http://127.0.0.1:8000/api/v1/courses
```

Hasil:
```json
{
    "message": "Daftar mata kuliah"
}
```

**Kesimpulan pengujian**
Kedua pengujian menghasilkan response JSON yang sama. Hal ini karena endpoint yang dibuat memang secara langsung menggunakan:

`return response()->json(...);`

Jadi pada endpoint sederhana yang dibuat dalam READ ini, penambahan header `Accept: application/json` tidak mengubah isi response.

Header tersebut tetap penting untuk komunikasi dengan API karena client dapat menyatakan format response yang diharapkannya.

### 5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.

`php artisan route:list --path=api`

Hasilnya:
```
GET|HEAD  api/user
GET|HEAD  api/v1/courses
```

Route: `api/user`
merupakan route bawaan yang dibuat ketika API scaffolding diaktifkan dan menggunakan middleware: `auth:sanctum`

Sedangkan route yang saya buat adalah: `api/v1/courses`

Route tersebut merupakan endpoint sederhana yang dibuat untuk memenuhi tahap READ.

Dengan demikian, pada tahap READ sudah berhasil mengaktifkan jalur API Laravel 12, membuat endpoint `/api/v1/courses`, membandingkannya dengan jalur web, serta melakukan pengujian response API.

## 2. Break

### 

## 3. CheckPoin

### 1. Kenapa mengembalikan model mentah berbahaya? peragakan kebocorannya

mengembalikan model mentah berbahaya karena sistem dapat menampilkan atau mengembalikan seluruh data dari model dalam response API.

percobaan => 
1. routers/api.php `Route::get('/v1/test-bocor', fn () => response()->json(\App\Models\User::all()));`
2. jalankan di browser `http://127.0.0.1:8000/api/v1/test-bocor`
3. atau jalankan di terminal `curl http://127.0.0.1:8000/api/v1/test-bocor`
4. data `user` dan `password` tidak terlihat karena ada perintah di model `$hidden`

### 2. Kenapa mengembalikan model mentah berbahaya? peragakan kebocorannya

- `401 Unauthorized` → server belum mengenali/menolak identitas karena belum login atau token tidak valid. Uji dengan `curl.exe -i -H "Accept: application/json" http://127.0.0.1:8000/api/v1/courses`

- `403 Forbidden` → user sudah terautentikasi, tapi tidak punya izin, Contoh: mahasiswa mencoba membuat assignment. Uji dengan `curl.exe -i -X POST "http://127.0.0.1:8000/api/v1/assignments" -H "Authorization: Bearer 2|8LgJrQSi1tFQ3BQffhDrEtkL4Svy4jL6HYy7Jpx46c940803" -H "Accept: application/json" -H "Content-Type: application/json" -d "{}"` (sesuaikan token)

### 3. Kenapa routes/api.php tidak ada di Laravel 12?

Karena Laravel 12 dirancang dengan struktur awal yang sangat minimalis (lean skeleton). Banyak aplikasi
web yang hanya menggunakan tampilan biasa (Blade) dan sama sekali tidak membutuhkan API. Dengan tidak
menyertakan rute API sejak awal, aplikasi menjadi lebih ringan dan cepat karena tidak perlu memuat sistem
API yang tidak dipakai.

Untuk mengaktifkannya jalankan `php artisan install:api`

Perintah ini membuat routes/api.php, memasang Sanctum, dan mendaftarkan API route di bootstrap/app.php.

### 4. Fungsi whenLoaded()?

`whenLoaded()` digunakan agar relasi hanya dimasukkan ke JSON jika sudah di-load.

Contoh:
```
'lecturer' => new UserResource($this->whenLoaded('lecturer'))
```

Tanpa `whenLoaded()`, relasi bisa memicu query database berulang dan menyebabkan N+1 Query.

Intinya: whenLoaded() membantu mengontrol relasi dan mencegah query database yang tidak perlu.

### 5. Kenapa pesan gagal login tidak boleh membedakan email/password?

Jika server memberikan pesan yang berbeda (contoh: "Email tidak terdaftar" vs "Password salah"),
penyerang bisa memanfaatkan informasi tersebut untuk menebak akun kampus mana saja yang aktif (User
Enumeration).

Bahayanya:
1. Penyerang menggunakan program bot untuk memasukkan ribuan daftar email secara otomatis.
2. Saat server menjawab "Password salah", penyerang tahu pasti bahwa akun email dosen atau mahasiswa
tersebut benar-benar terdaftar di sistem KampusLMS.
3. Penyerang kemudian bisa memusatkan serangan tebak kata sandi (brute force) atau penipuan (phishing)
khusus ke akun tersebut.
4. Karena itu, pesan gagal login wajib diseragamkan dengan satu kalimat netral:
"Kredensial yang diberikan tidak cocok dengan data kami."
Dengan kalimat ini, penyerang tidak bisa mengetahui apakah emailnya yang salah atau passwordnya
yang salah

### 6. Kenapa Endpoint Login Wajib di-Throttle? Berapa Nilai yang Anda Pakai dan Mengapa?

Endpoint login adalah pintu utama masuknya pengguna. Tanpa batasan frekuensi (throttle),
peretas dapat menjalankan bot otomatis untuk mencoba puluhan ribu kombinasi kata sandi per detik
(serangan brute force) sampai akun berhasil dibobol.

throttle:5,1 (maksimal 5 kali percobaan dalam 1 menit).

Alasannya:
1. Aman untuk manusia: Kesempatan 5 kali dalam 1 menit sangat cukup bagi pengguna asli yang
sesekali salah mengetik kata sandi.
2. Mematikan bot peretas: Bagi bot penyerang, batasan 5 kali per menit membuat upaya membobol
password menjadi sia-sia karena butuh waktu bertahun-tahun untuk mencoba ribuan kata sandi.
3. Begitu gagal 5 kali berturut-turut, server langsung menolak percobaan ke-6 dengan respon HTTP 429
Too Many Requests dan mewajibkan menunggu 60 detik sebelum boleh mencoba lagi.