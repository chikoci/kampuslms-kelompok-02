# Catatan Praktikum Minggu 4
**Nama:** Armansyah  
**NIM:** 10241013

## Bagian READ: Peta route Anda sendiri
#

### 1. Jalankan `php artisan route:list --except-vendor`. Salin keluarannya ke catatan.
Hasilnya menampilkan daftar URL, HTTP method, nama route, serta controller yang menangani setiap request. Dari hasil tersebut terlihat bahwa aplikasi memiliki route untuk halaman utama, tentang, serta resource courses dan users yang menyediakan route CRUD secara lengkap.
```
GET|HEAD        / ......................................................................... home › routes/web.php:8
GET|HEAD        courses .................................................... courses.index › CourseController@index
POST            courses .................................................... courses.store › CourseController@store
GET|HEAD        courses/create ........................................... courses.create › CourseController@create
GET|HEAD        courses/{course} ............................................. courses.show › CourseController@show
PUT|PATCH       courses/{course} ......................................... courses.update › CourseController@update
DELETE          courses/{course} ....................................... courses.destroy › CourseController@destroy
GET|HEAD        courses/{course}/edit ........................................ courses.edit › CourseController@edit
GET|HEAD        tentang ......................................................... tentang › TentangController@index
GET|HEAD        users .......................................................... users.index › UserController@index
POST            users .......................................................... users.store › UserController@store
GET|HEAD        users/create ................................................. users.create › UserController@create
GET|HEAD        users/{user} ..................................................... users.show › UserController@show
PUT|PATCH       users/{user} ................................................. users.update › UserController@update
DELETE          users/{user} ............................................... users.destroy › UserController@destroy
GET|HEAD        users/{user}/edit ................................................ users.edit › UserController@edit
```

### 2. Tandai setiap route yang menerima parameter model ({course}, {assignment}, dst).
terdapat 8 route yang menggunakan parameter model, yaitu {course} dan {user}. Route-route tersebut perlu diperhatikan karena parameter tersebut digunakan untuk menentukan data tertentu yang akan diakses, diubah, atau dihapus.
```
GET|HEAD        courses/{course} ............................................. courses.show › CourseController@show
PUT|PATCH       courses/{course} ......................................... courses.update › CourseController@update
DELETE          courses/{course} ....................................... courses.destroy › CourseController@destroy
GET|HEAD        courses/{course}/edit ........................................ courses.edit › CourseController@edit

GET|HEAD        users/{user} ..................................................... users.show › UserController@show
PUT|PATCH       users/{user} ................................................. users.update › UserController@update
DELETE          users/{user} ............................................... users.destroy › UserController@destroy
GET|HEAD        users/{user}/edit ................................................ users.edit › UserController@edit
```

### 3. Untuk setiap route bertanda, jawab: siapa saja yang seharusnya boleh mengaksesnya, dan apa yang saat ini mencegah orang lain? Kemungkinan besar jawabannya "belum ada apa-apa" — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.
Berdasarkan route yang ada saat ini, sebagian besar route seperti courses/{course} dan users/{user} belum memiliki middleware atau policy yang membatasi hak akses secara khusus. Artinya, model binding sudah dapat digunakan untuk menemukan data berdasarkan ID, tetapi belum ada mekanisme authorization yang memastikan bahwa pengguna tersebut memang memiliki hak untuk mengakses, mengubah, atau menghapus data tersebut.

| Route                        | Siapa yang Seharusnya Boleh              | Perlindungan Saat Ini              |
| ---------------------------- | ---------------------------------------- | ---------------------------------- |
| `GET courses/{course}`       | Admin, dosen, mahasiswa sesuai hak akses | Belum ada middleware/policy khusus |
| `GET courses/{course}/edit`  | Admin atau pihak yang berwenang          | Belum ada middleware/policy khusus |
| `PUT/PATCH courses/{course}` | Admin atau pihak yang berwenang          | Belum ada middleware/policy khusus |
| `DELETE courses/{course}`    | Admin atau pihak yang berwenang          | Belum ada middleware/policy khusus |
| `GET users/{user}`           | Admin atau sesuai hak akses              | Belum ada middleware/policy khusus |
| `GET users/{user}/edit`      | Admin atau pihak yang berwenang          | Belum ada middleware/policy khusus |
| `PUT/PATCH users/{user}`     | Admin atau pihak yang berwenang          | Belum ada middleware/policy khusus |
| `DELETE users/{user}`        | Admin atau pihak yang berwenang          | Belum ada middleware/policy khusus |

route yang memiliki parameter model saat ini belum menunjukkan adanya pembatasan authorization pada daftar route. Kondisi ini menjadi titik yang perlu diperhatikan terhadap IDOR dan akan menjadi pekerjaan pada materi berikutnya.

### 4.Buat tabel di docs/minggu-05-<nama>.md berjudul "Daftar Titik Rawan IDOR". Tabel ini akan Anda pakai lagi di minggu 7 dan saat interview.
Tabel ini memetakan titik rawan pada aplikasi KampusLMS saat ini:

| No | Route | Parameter | Hak Akses yang Seharusnya | Kondisi Pengamanan Saat Ini | Potensi Bahaya IDOR |
|---|---|---|---|---|---|
| 1 | `courses/{course}` | `{course}` | Admin, Dosen pengampu, & Mahasiswa kelas tersebut | Terbuka bebas untuk siapa saja | Siapa saja bisa melihat silabus/detail mata kuliah internal tanpa terdaftar |
| 2 | `courses/{course}/edit` | `{course}` | Hanya Admin & Dosen pemilik mata kuliah | Terbuka bebas tanpa login | Pengguna mana pun bisa membuka form edit mata kuliah milik dosen lain |
| 3 | `courses/{course}` (PUT/PATCH) | `{course}` | Hanya Admin & Dosen pemilik mata kuliah | Tidak ada validasi kepemilikan di controller | Orang luar bisa mengganti nama, SKS, status, atau dosen pengampu mata kuliah |
| 4 | `courses/{course}` (DELETE) | `{course}` | Hanya Admin | Tidak ada validasi peran | Siapapun bisa mengirim request DELETE untuk menghapus mata kuliah dari database |
| 5 | `users/{user}` | `{user}` | Admin & Pengguna pemilik akun sendiri | Terbuka bebas untuk publik | Orang luar bisa mengintip informasi pribadi pengguna lain dengan mengganti ID |
| 6 | `users/{user}/edit` | `{user}` | Admin & Pengguna pemilik akun sendiri | Terbuka bebas tanpa autentikasi | Siapa saja bisa membuka form edit data akun orang lain |
| 7 | `users/{user}` (PUT/PATCH) | `{user}` | Admin & Pengguna pemilik akun sendiri | Tidak ada pengecekan kecocokan `user_id` | Siapa saja bisa mengganti data pengguna lain, termasuk mengubah email dan role |
| 8 | `users/{user}` (DELETE) | `{user}` | Hanya Admin | Tidak ada proteksi middleware role | Pengguna umum bisa menghapus akun pengguna lain dari sistem |
