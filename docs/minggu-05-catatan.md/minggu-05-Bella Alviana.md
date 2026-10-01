# Minggu 5
**NAMA:** Bella Alviana
**NIM:** 10241015

## READ

**1. Jalankan `php artisan route:list --except-vendor`. Salin keluarannya ke catatan.**
Jawab: Keluarannya dari menjalankan `php artisan route:list --except-vendor` terdapat 16 rute yang aktif di sistem.
```+-----------+-------------------------+-----------------+------------------------------------------+------------+
| Method    | URI                     | Name            | Action                                   | Middleware |
+-----------+-------------------------+-----------------+------------------------------------------+------------+
| GET|HEAD  | /                       | home            | Closure (routes/web.php:8)               | web        |
| GET|HEAD  | courses                 | courses.index   | App\Http\Controllers\CourseController    | web        |
| POST      | courses                 | courses.store   | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/create          | courses.create  | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/{course}        | courses.show    | App\Http\Controllers\CourseController    | web        |
| PUT|PATCH | courses/{course}        | courses.update  | App\Http\Controllers\CourseController    | web        |
| DELETE    | courses/{course}        | courses.destroy | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/{course}/edit   | courses.edit    | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | tentang                 | tentang         | App\Http\Controllers\TentangController   | web        |
| GET|HEAD  | users                   | users.index     | App\Http\Controllers\UserController      | web        |
| POST      | users                   | users.store     | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/create            | users.create    | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/{user}            | users.show      | App\Http\Controllers\UserController      | web        |
| PUT|PATCH | users/{user}            | users.update    | App\Http\Controllers\UserController      | web        |
| DELETE    | users/{user}            | users.destroy   | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/{user}/edit       | users.edit      | App\Http\Controllers\UserController      | web        |
+-----------+-------------------------+-----------------+------------------------------------------+------------+
```

**2. Tandai setiap route yang menerima parameter model `({course}, {assignment}, dst)`.**
Jawab: terdapat 8 route yang menerima parameter model, yaitu 4 route untuk `course` dan 4 route untuk `user`.
```
+----+-------------------------+-------------+----------------------------+
| No | Route                   | Parameter   | Perlu Diperiksa            |
+----+-------------------------+-------------+----------------------------+
| 1  | courses/{course}        | {course}    | Ya                         |
| 2  | courses/{course}/edit   | {course}    | Ya                         |
| 3  | courses/{course}        | {course}    | Ya - update                |
| 4  | courses/{course}        | {course}    | Ya - delete                |
| 5  | users/{user}            | {user}      | Ya                         |
| 6  | users/{user}/edit       | {user}      | Ya                         |
| 7  | users/{user}            | {user}      | Ya - update                |
| 8  | users/{user}            | {user}      | Ya - delete                |
+----+-------------------------+-------------+----------------------------+
```

**3. Untuk setiap route bertanda, `jawab: siapa saja yang seharusnya boleh mengaksesnya`, dan apa yang saat ini mencegah orang lain? Kemungkinan besar jawabannya `"belum ada apa-apa"` — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.**
Jawab: 
```
| No. | Route                         | Siapa yang Seharusnya Boleh Mengakses?                                                           | Proteksi Saat Ini                                                  |
| --: | ----------------------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------ |
|   1 | `GET /courses/{course}`       | Admin, Dosen pengampu, Mahasiswa yang terdaftar                                                  | Belum ada `auth`, `role`, atau pengecekan kepemilikan pada routing |
|   2 | `GET /courses/{course}/edit`  | Admin dan Dosen pengampu course                                                                  | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
|   3 | `PUT/PATCH /courses/{course}` | Admin dan Dosen pengampu course                                                                  | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
|   4 | `DELETE /courses/{course}`    | Admin dan Dosen yang berwenang terhadap course                                                   | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
|   5 | `GET /users/{user}`           | Admin; pengguna biasa hanya boleh mengakses data dirinya sesuai aturan aplikasi                  | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
|   6 | `GET /users/{user}/edit`      | Admin; pengguna biasa hanya boleh mengedit profil dirinya sendiri jika fitur tersebut disediakan | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
|   7 | `PUT/PATCH /users/{user}`     | Admin; pengguna biasa hanya boleh memperbarui data dirinya sendiri jika diizinkan                | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
|   8 | `DELETE /users/{user}`        | Admin                                                                                            | Belum ada `auth`, `role`, atau pengecekan kepemilikan              |
```
Berdasarkan hasil route:list, seluruh route yang memiliki parameter model masih menggunakan middleware bawaan web. Belum terlihat adanya middleware auth, middleware role, maupun Policy pada routing. Oleh karena itu, route-route tersebut menjadi titik yang perlu diperiksa terhadap risiko IDOR.

**4. Buat tabel di `docs/minggu-05-<nama>.md berjudul "Daftar Titik Rawan IDOR"`. Tabel ini akan Anda pakai lagi di minggu 7 dan saat interview.**
Jawab: 

### Daftar Titik Rawan IDOR

Tabel ini memetakan titik rawan pada aplikasi KampusLMS berdasarkan hasil pemeriksaan route saat ini:

```
| **No** | **Route**                      | **Parameter** | **Hak Akses yang Seharusnya**                                       | **Kondisi Pengamanan Saat Ini**                                  | **Potensi Bahaya IDOR**                                                                               |
| :----: | :----------------------------- | :-----------: | :------------------------------------------------------------------ | :--------------------------------------------------------------- | :---------------------------------------------------------------------------------------------------- |
|    1   | `courses/{course}`             |   `{course}`  | Admin, Dosen pengampu, & Mahasiswa yang terdaftar                   | Belum terlihat middleware `auth`, `role`, atau Policy pada route | Pengguna dapat mencoba mengganti ID untuk mengakses detail course yang seharusnya tidak dapat diakses |
|    2   | `courses/{course}/edit`        |   `{course}`  | Admin & Dosen pengampu                                              | Belum terlihat pembatasan role atau kepemilikan pada route       | Dosen dapat mencoba membuka form edit course milik dosen lain                                         |
|    3   | `courses/{course}` (PUT/PATCH) |   `{course}`  | Admin & Dosen pengampu                                              | Belum terlihat validasi kepemilikan pada route                   | Pengguna yang tidak berwenang dapat mencoba mengubah course dengan mengganti ID                       |
|    4   | `courses/{course}` (DELETE)    |   `{course}`  | Admin & pihak yang memiliki hak menghapus                           | Belum terlihat proteksi role pada route                          | Pengguna yang tidak berwenang dapat mencoba menghapus course dengan mengganti ID                      |
|    5   | `users/{user}`                 |    `{user}`   | Admin & pengguna pemilik akun sendiri sesuai aturan aplikasi        | Belum terlihat middleware `auth`, `role`, atau Policy pada route | Pengguna dapat mencoba mengganti ID untuk mengakses data user lain                                    |
|    6   | `users/{user}/edit`            |    `{user}`   | Admin & pengguna pemilik akun sendiri jika fitur tersebut diizinkan | Belum terlihat pembatasan role atau kepemilikan pada route       | Pengguna dapat mencoba membuka form edit akun pengguna lain                                           |
|    7   | `users/{user}` (PUT/PATCH)     |    `{user}`   | Admin & pengguna pemilik akun sendiri sesuai aturan aplikasi        | Belum terlihat validasi kepemilikan pada route                   | Pengguna dapat mencoba mengubah data user lain dengan mengganti ID                                    |
|    8   | `users/{user}` (DELETE)        |    `{user}`   | Admin                                                               | Belum terlihat proteksi middleware `role` pada route             | Pengguna yang tidak berwenang dapat mencoba menghapus user lain                                       |
```

## BREAK

**1. Login sebagai mahasiswa A. Buka submission milik mahasiswa B dengan mengubah angka di URL.**

Jawab: 

**Prediksi** Mahasiswa A tetap bisa membuka Submission B hanya dengan mengganti ID pada URL.

**Sebenarnya** 
Di `routes/web.php` kami sudah memiliki route khusus pengujian yaitu:
```php
Route::get('/dev/login-mahasiswa', function () {
    Auth::loginUsingId(3);
    return redirect('/courses')->with(
        'success',
        'Sekarang login sebagai: Mahasiswa'
    );
});
```
Langkah melakukan tes:
 - Buka browser dan login sebagai mahasiswa dengan menambahkan `dev/login-mahasiswa` pada URL http://127.0.0.1:8000/dev/login-mahasiswa
 - Route akan: 
```
 /dev/login-mahasiswa
        ↓
 Auth::loginUsingId(3)
        ↓
 Laravel membuat session login
        ↓
 User yang aktif = User 3
        ↓
 redirect ke /courses
```
 - Buka: `http://127.0.0.1:8000/submissions/1` dan akan memunculkan tampilan pengumpulan tugas milik mahasiswa A
 - Ubah URL di atas dengan submission milik mahasiswa b menjadi `http://127.0.0.1:8000/submissions/2` dan hasilnya adalah `403 - akses ditolak` Akses ditolak: anda tidak memiliki izin untuk melihat pengumpulan tugas ini.

Berdasarkan hasil pengujian, ketika mahasiswa A mencoba mengakses submission milik mahasiswa B dengan mengubah ID pada URL, sistem memberikan respons **403 Forbidden**. Hal ini menunjukkan bahwa meskipun `Submission` dengan ID tersebut ditemukan melalui route model binding, pengguna yang sedang login tidak memiliki izin untuk mengakses data tersebut. Sistem telah melakukan pengecekan hak akses berdasarkan pengguna yang sedang login sehingga mahasiswa A tidak dapat melihat submission milik mahasiswa B. Dengan demikian, pada kondisi pengujian ini **IDOR tidak berhasil dieksploitasi**, karena akses terhadap objek milik pengguna lain telah dibatasi.


**2. Buka `/courses/1/assignments/99` di mana tugas 99 milik mata kuliah lain.**

Jawab: 

**Prediksi** sistem akan menampilkan eror 

**Langkah percobaan**
 - Mengubah sementara method `show()` pada `AssignmentController.php`.
  - Kode awal
  ```php
  public function show(Assignment $assignment)
  {
    $assignment->load(['course', 'submissions.student']);

    return view('assignments.show', compact('assignment'));
  }
 
 ```
Diubah menjadi

 ```php
  public function show($assignment)
    {
        $assignment = Assignment::findOrFail($assignment);

        $assignment->load(['course', 'submissions.student']);

        return view('assignments.show', compact('assignment'));
    }
```
 - Memperbaiki tampilan halaman `error 404` pada `layout.blade.php`.
  pada baris 53 dari `@if ($errors->any())` menjadi `@if (isset($errors) && $errors->any())`
  Perubahan ini diperlukan agar halaman 404 tidak menghasilkan error Undefined variable $errors ketika variabel $errors tidak tersedia.
 - Nonaktifkan `Route::scopeBindings` pada file `Routes/web.php`
 - Menghilangkan `shallow()` dari route assignment `Routes/web.php`.
 Kode:
 ```php
 Route::resource('courses.assignments', AssignmentController::class)->shallow();
 ```
 ```php
 Route::resource('courses.assignments', AssignmentController::class);
 ```
  
**Hasilnya** Setelah mekanisme `scopeBindings()` dinonaktifkan, dilakukan pengujian dengan mengakses URL `/courses/1/assignments/99`. Hasil pengujian menunjukkan bahwa halaman Assignment 99 tetap berhasil ditampilkan dan sistem tidak memberikan respons `404 Not Found`. Pada halaman terlihat bahwa pengguna berada pada mata kuliah Pemrograman Web, tetapi tugas dengan ID 99 tetap dapat diakses melalui URL tersebut meskipun berdasarkan relasi data tugas tersebut bukan merupakan tugas dari mata kuliah yang digunakan pada URL. Hal ini menunjukkan bahwa tanpa scoped binding, Laravel hanya mencari data Assignment berdasarkan ID-nya dan tidak memastikan bahwa Assignment tersebut benar-benar memiliki hubungan dengan Course pada URL. Kondisi ini membuktikan adanya celah pada nested route.

**3. Aktifkan `Route::scopeBindings()`, ulangi nomor 2.**

Jawab:

**Prediksi** menurut saya sistem akan menampilkan erorr

**Sebenarnya** Setelah `Route::scopeBindings()` diaktifkan kembali, pengujian diulangi menggunakan URL `/courses/1/assignments/99`. Berbeda dengan pengujian sebelumnya, sistem tidak lagi menampilkan data Assignment 99 dan memberikan respons `404 Not Found`. Hal ini terjadi karena scoped binding memastikan bahwa Assignment yang diminta harus memiliki hubungan dengan Course yang terdapat pada URL. Karena Assignment 99 bukan merupakan tugas dari Course 1, Laravel tidak menemukan resource yang sesuai dan menolak akses tersebut. Dengan demikian, `scopeBindings()` berhasil mencegah akses terhadap resource anak melalui resource induk yang berbeda.

**4. Daftarkan middleware di `app/Http/Kernel.php` seperti tutorial lama**

Jawab: 

**Prediksi** tidak akan bisa karena sistem ini sudah memakai `laravel 12` yang dimana `kernel.php` digunakan pada tipe `laravel 10` kebawah.

**Hasilnya** Ketika menjalankan perintah `cat app/Http/Kernel.php` di terminal, sistem mengembalikan kesalahan `No such file or directory`. Hal ini mengonfirmasi bahwa arsitektur Laravel 12 telah menghapus berkas `Kernel.php`. Mengikuti tutorial lama dengan membuat berkas ini secara manual tidak akan berdampak apa pun dan memicu `error Target class [role] does not exist`, karena pendaftaran alias middleware di Laravel 12 sepenuhnya dikelola di `bootstrap/app.php` melalui metode `->withMiddleware()`.

**5. Pasang `role:admin` pada grup, lalu akses sebagai dosen.**

Jawab:  

**Prediksi** Saat saya mencoba membuka halaman kelola pengguna (/admin/users) menggunakan akun Dosen, aplikasinya mungkin bakal menampilkan halaman kosong.

**Sebenarnya** Setelah middleware `role:admin` dipasang pada grup route Admin, Setelah berhasil login dengan mengakses URL `dev/login-dosen`, setelahnya pengguna mencoba mengakses `/admin/dashboard`. Sistem menolak permintaan tersebut dan memberikan respons `403 Forbidden`. Hal ini terjadi karena middleware `EnsureUserHasRole` melakukan pemeriksaan terhadap role pengguna sebelum request diteruskan ke `route` maupun `controller`. Karena role pengguna adalah dosen, sedangkan route membutuhkan role admin, akses langsung ditolak pada lapisan awal. Dengan demikian, `middleware role` berhasil membatasi akses berdasarkan hak dan tingkatan pengguna secara tepat.


**6. Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos `role:dosen`)**

Jawab:

**Prediksi** Dosen A tidak bisa mengedit mata kuliah milik dosen B karena sistem melakukan pemeriksaan bahwa mereka dua orang yang berbeda.

**Hasilnya** Sistem menampilkan `403 - Akses ditolak` Hal ini terjadi karena meskipun Dosen A lolos dari middleware `role:dosen`, sistem menerapkan lapisan keamanan kedua pada level *controlle*r atau *policy* berupa pemeriksaan kepemilikan data (ownership check). Karena ID Dosen A tidak sesuai dengan `lecturer_id` pada data mata kuliah milik Dosen B, sistem menolak akses dan memberikan respons `403 Forbidden`. Pengujian ini menunjukkan bahwa pembatasan akses berbasis peran (role) saja belum cukup. Sistem juga perlu menerapkan pembatasan berdasarkan kepemilikan data untuk mencegah pengguna mengakses atau memanipulasi resource milik pengguna lain. Mekanisme tersebut merupakan salah satu bentuk perlindungan terhadap risiko `IDOR`.