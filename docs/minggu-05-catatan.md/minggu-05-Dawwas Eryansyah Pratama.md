# Minggu 05

**Nama:** Dawwas Eryansyah Pratama

**NIM:** 10241019

---

## 1. READ

### 1. Jalankan php artisan route:list --except-vendor. Salin keluarannya ke catatan.

(base) PS D:\PROWEB\kampuslms-kelompok-02> php artisan route:list --except-vendor

  GET|HEAD        / .......................................................................................... home › routes/web.php:8
  GET|HEAD        courses ..................................................................... courses.index › CourseController@index
  POST            courses ..................................................................... courses.store › CourseController@store
  GET|HEAD        courses/create ............................................................ courses.create › CourseController@create
  GET|HEAD        courses/{course} .............................................................. courses.show › CourseController@show
  PUT|PATCH       courses/{course} .......................................................... courses.update › CourseController@update
  DELETE          courses/{course} ........................................................ courses.destroy › CourseController@destroy
  GET|HEAD        courses/{course}/edit ......................................................... courses.edit › CourseController@edit
  GET|HEAD        tentang .......................................................................... tentang › TentangController@index
  GET|HEAD        users ........................................................................... users.index › UserController@index
  POST            users ........................................................................... users.store › UserController@store
  GET|HEAD        users/create .................................................................. users.create › UserController@create
  GET|HEAD        users/{user} ...................................................................... users.show › UserController@show
  PUT|PATCH       users/{user} .................................................................. users.update › UserController@update
  DELETE          users/{user} ................................................................ users.destroy › UserController@destroy
  GET|HEAD        users/{user}/edit ................................................................. users.edit › UserController@edit

                                                                                                                   Showing [16] routes

### 2. Tandai setiap route yang menerima parameter model ({course}, {assignment}, dst).

**Parameter `{course}`:**

- GET|HEAD    `courses/{course}`
- PUT|PATCH   `courses/{course}`
- DELETE      `courses/{course}`
- GET|HEAD    `courses/{course}/edit`

Keempat route tersebut menerima parameter: `{course}`

Artinya Laravel dapat menggunakan parameter tersebut untuk melakukan Route Model Binding ke model `Course`.

**Parameter `{user}`:**

- GET|HEAD    `users/{user}`
- PUT|PATCH   `users/{user}`
- DELETE      `users/{user}`
- GET|HEAD    `users/{user}/edit`

Keempat route tersebut menerima parameter: `{user}`

yang dapat digunakan untuk Route Model Binding ke model User.

### 3. Untuk setiap route bertanda, jawab: siapa saja yang seharusnya boleh mengaksesnya, dan apa yang saat ini mencegah orang lain? Kemungkinan besar jawabannya "belum ada apa-apa" — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.
A. Route `courses/{course}`

**`GET courses/{course}` → `courses.show`**

- Seharusnya: Admin, Dosen, Mahasiswa
- Risiko: pengguna dapat mengganti nilai `{course}` untuk mencoba melihat mata kuliah lain.
- Yang perlu dicek: apakah `controller/middleware` melakukan pengecekan hak akses.

**`PUT/PATCH courses/{course}` → `courses.update`**
- Seharusnya: Admin
- Risiko: pengguna yang tidak berwenang dapat mencoba mengubah data mata kuliah dengan mengganti `{course}`.
- Yang perlu dicek: middleware atau authorization pada controller.

**`DELETE courses/{course}` → `courses.destroy`**
- Seharusnya: Admin
- Risiko: pengguna dapat mencoba menghapus course lain melalui parameter `{course}`.
- Yang perlu dicek: middleware atau authorization.

**`GET courses/{course}/edit` → `courses.edit`**
- Seharusnya: Admin
- Risiko: pengguna yang tidak berwenang dapat membuka halaman edit course lain.
- Yang perlu dicek: middleware atau authorization.

B. Route `users/{user}`

**`GET users/{user}` → `users.show`**
- Seharusnya: Admin
- Risiko: pengguna dapat mengganti `{user}` untuk mencoba melihat data user lain.

**`PUT/PATCH users/{user}` → `users.update`**
- Seharusnya: Admin
- Risiko: pengguna dapat mencoba mengubah data user lain.

**`DELETE users/{user}` → `users.destroy`**
- Seharusnya: Admin
- Risiko: pengguna dapat mencoba menghapus user lain.

**`GET users/{user}/edit` → `users.edit`**
- Seharusnya: Admin
- Risiko: pengguna dapat mencoba membuka halaman edit user lain

### 4. Buat tabel di docs/minggu-05-<nama>.md berjudul "Daftar Titik Rawan IDOR". Tabel ini akan Anda pakai lagi di minggu 7 dan saat interview.
## 4. Daftar Titik Rawan IDOR

| No. | Route | Nama Route | Pengguna yang Seharusnya | Potensi IDOR | Hal yang Perlu Diperiksa |
|---|---|---|---|---|---|
| 1 | `GET courses/{course}` | `courses.show` | Admin, Dosen, Mahasiswa | Pengguna dapat mengganti `{course}` untuk mencoba melihat course lain. | Authorization terhadap course |
| 2 | `PUT/PATCH courses/{course}` | `courses.update` | Admin | Pengguna dapat mencoba mengubah course lain dengan mengganti `{course}`. | Middleware atau authorization |
| 3 | `DELETE courses/{course}` | `courses.destroy` | Admin | Pengguna dapat mencoba menghapus course lain dengan mengganti `{course}`. | Middleware atau authorization |
| 4 | `GET courses/{course}/edit` | `courses.edit` | Admin | Pengguna dapat mencoba membuka halaman edit course lain. | Middleware atau authorization |
| 5 | `GET users/{user}` | `users.show` | Admin | Pengguna dapat mengganti `{user}` untuk mencoba melihat data user lain. | Authorization terhadap user |
| 6 | `PUT/PATCH users/{user}` | `users.update` | Admin | Pengguna dapat mencoba mengubah data user lain dengan mengganti `{user}`. | Middleware atau authorization |
| 7 | `DELETE users/{user}` | `users.destroy` | Admin | Pengguna dapat mencoba menghapus user lain dengan mengganti `{user}`. | Middleware atau authorization |
| 8 | `GET users/{user}/edit` | `users.edit` | Admin | Pengguna dapat mencoba membuka halaman edit user lain. | Middleware atau authorization |

