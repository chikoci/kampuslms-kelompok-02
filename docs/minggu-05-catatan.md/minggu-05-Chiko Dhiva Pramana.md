# Catatan Praktikum Minggu 5

**Nama:** Chiko Dhiva Pramana  
**NIM:** 10241017  

---

## Bagian READ:

Pada tahap ini, saya memeriksa peta rute yang saat ini sudah terdaftar di aplikasi KampusLMS kelompok kami dengan menjalankan perintah `php artisan route:list --except-vendor`. 

Tujuannya adalah mengidentifikasi rute-rute yang menerima parameter model (seperti `{course}` dan `{user}`) serta menganalisis siapa yang berhak mengaksesnya dan bagaimana proteksi keamanannya saat ini.

---

### 1. Daftar Rute Aplikasi Saat Ini

Berikut salinan keluaran dari terminal:

```text
+-----------+-----------------------+-----------------+------------------------------------------+------------+
| Method    | URI                   | Name            | Action                                   | Middleware |
+-----------+-----------------------+-----------------+------------------------------------------+------------+
| GET|HEAD  | /                     | home            | Closure (routes/web.php:8)               | web        |
| GET|HEAD  | tentang               | tentang         | App\Http\Controllers\TentangController   | web        |
| GET|HEAD  | courses               | courses.index   | App\Http\Controllers\CourseController    | web        |
| POST      | courses               | courses.store   | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/create        | courses.create  | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/{course}      | courses.show    | App\Http\Controllers\CourseController    | web        |
| PUT|PATCH | courses/{course}      | courses.update  | App\Http\Controllers\CourseController    | web        |
| DELETE    | courses/{course}      | courses.destroy | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | courses/{course}/edit | courses.edit    | App\Http\Controllers\CourseController    | web        |
| GET|HEAD  | users                 | users.index     | App\Http\Controllers\UserController      | web        |
| POST      | users                 | users.store     | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/create          | users.create    | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/{user}          | users.show      | App\Http\Controllers\UserController      | web        |
| PUT|PATCH | users/{user}          | users.update    | App\Http\Controllers\UserController      | web        |
| DELETE    | users/{user}          | users.destroy   | App\Http\Controllers\UserController      | web        |
| GET|HEAD  | users/{user}/edit     | users.edit      | App\Http\Controllers\UserController      | web        |
+-----------+-----------------------+-----------------+------------------------------------------+------------+
```

Total ada 16 rute aktif di sistem.

---

### 2. Penandaan Rute Berparameter Model

Dari daftar di atas, terdapat 8 rute yang menerima parameter model:

* **Parameter `{course}` (Modul Mata Kuliah):**
  1. `GET courses/{course}` (`courses.show`)
  2. `GET courses/{course}/edit` (`courses.edit`)
  3. `PUT|PATCH courses/{course}` (`courses.update`)
  4. `DELETE courses/{course}` (`courses.destroy`)

* **Parameter `{user}` (Modul Pengguna):**
  1. `GET users/{user}` (`users.show`)
  2. `GET users/{user}/edit` (`users.edit`)
  3. `PUT|PATCH users/{user}` (`users.update`)
  4. `DELETE users/{user}` (`users.destroy`)

---

### 3. Analisis Hak Akses & Proteksi Saat Ini

Saya menganalisis setiap rute bertanda di atas berdasarkan dua pertanyaan modul:

1. **Siapa saja yang seharusnya boleh mengaksesnya?**
   * **Rute `{user}` (edit/update/destroy):** Seharusnya **hanya Admin** yang boleh mengelola data pengguna lain. Pengguna biasa maksimal hanya boleh mengedit profil dirinya sendiri.
   * **Rute `{course}` (edit/update/destroy):** Seharusnya **hanya Admin** atau **Dosen pengampu mata kuliah bersangkutan** yang boleh mengubah atau menghapus data tersebut. Dosen lain maupun mahasiswa tidak boleh menyentuhnya.
   * **Rute `{course}` (show):** Boleh dilihat oleh Admin, Dosen pengampu, dan Mahasiswa yang terdaftar (enrolled) di kelas tersebut.

2. **Apa yang saat ini mencegah orang lain mengaksesnya?**
   * **Jawabannya: Belum ada apa-apa sama sekali!**
   * Seluruh rute di atas saat ini masih bersifat terbuka bebas (hanya memakai middleware bawaan `web`).
   * Belum ada middleware `auth` untuk mewajibkan login, belum ada middleware `role` untuk membatasi peran, dan di dalam controller belum ada validasi kepemilikan (`abort_unless` / Policy).
   * Siapapun yang mengetahui atau menebak URL (misal membuka `/courses/2/edit` atau `/users/1/edit`), bisa langsung melihat, mengubah, bahkan menghapus data milik orang lain. Inilah celah **IDOR (Insecure Direct Object Reference)** yang nyata di aplikasi kami.

---

### 4. Tabel: Daftar Titik Rawan IDOR

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

---

### Kesimpulan Tahap READ:
Dari penelusuran ini, saya memahami bahwa **Route Model Binding otomatis mengambil data dari database berdasarkan ID, namun tidak otomatis memeriksa hak akses**. 

Tanpa adanya middleware grup peran dan pengecekan kepemilikan di controller, aplikasi sangat rentan terhadap serangan IDOR karena pengguna hanya perlu mengganti angka ID pada URL untuk memanipulasi data orang lain. 

Temuan ini menjadi dasar kebutuhan perbaikan yang akan kami kerjakan di tahap BUILD (memasang middleware dan route group) serta tahap BREAK (menguji pembuktian kerusakannya).

## Bagian BREAK:

1. Prediksi: 