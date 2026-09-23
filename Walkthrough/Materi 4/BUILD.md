# Hasil Implementasi Tahap BUILD (Minggu 4)

## Ringkasan Pencapaian BUILD Minggu 4
Sesuai ketentuan modul Minggu 4 sub-bab 4.3 (Tahap BUILD) dan spesifikasi proyek KampusLMS, seluruh 6 butir persyaratan fitur telah selesai diimplementasikan:

1. **Form Request Mata Kuliah:**
   - Kelas `StoreCourseRequest` dan `UpdateCourseRequest` telah dibuat dan aktif digunakan.
   - Aturan validasi mencakup pengecekan ketat tipe data, rentang SKS (1-6), keabsahan ID dosen (`exists:users,id`), dan enum status (`in:draft,active,archived`).
   - Aturan `unique` pada proses update menggunakan `Rule::unique('courses', 'code')->ignore($this->course)` agar tidak menolak kodenya sendiri saat disimpan ulang.
   - Pesan error kustom (`messages()`) dalam bahasa Indonesia ramah pengguna.

2. **Form Blade Mata Kuliah:**
   - Form [create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/create.blade.php) dan [edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/edit.blade.php) telah dilengkapi token proteksi `@csrf`.
   - Menggunakan helper `old('field', ...)` agar input pengguna tidak terhapus saat validasi gagal.
   - Menampilkan pesan error per-input menggunakan direktif `@error('field')` dengan styling `.form-error` dan penanda `.is-invalid`.

3. **Flash Message Global di Layout:**
   - Berkas [layout.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/components/layout.blade.php) telah dilengkapi penanganan `session('success')` untuk notifikasi sukses dan `session('error')` untuk notifikasi kegagalan, sehingga berlaku otomatis di seluruh halaman aplikasi.

4. **Daftar Mata Kuliah Lengkap dengan Filter & Pagination:**
   - [CourseController@index](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) menggunakan rantai query `when()` untuk menangani pencarian (nama/kode) dan filter status.
   - Menggunakan `paginate(15)->withQueryString()` agar kata kunci pencarian dan status filter tetap terbawa saat berpindah ke halaman 2, 3, dan seterusnya.
   - Eager loading `with('lecturer')` dipasang untuk mencegah masalah performa N+1 query.

5. **Penerapan Penuh pada Modul Pengguna (Users):**
   - Dibuat `StoreUserRequest` dan `UpdateUserRequest` lengkap dengan pesan bahasa Indonesia dan `Rule::unique()->ignore($this->user)` untuk email dan NIM/NIP.
   - [UserController](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php) menerapkan `$request->validated()`, dengan penetapan `role` tetap dilakukan secara eksplisit demi mematuhi aturan keamanan anti-mass assignment.
   - Form [users/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/create.blade.php) dan [users/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/edit.blade.php) dilengkapi `@error` dan `old()`.
   - Halaman [users/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/index.blade.php) dilengkapi form pencarian (nama/email/NIM), filter role, dan pagination `withQueryString()`.

6. **Konfirmasi Penghapusan & Method DELETE:**
   - Seluruh aksi penghapusan (Courses dan Users) menggunakan form dengan direktif `@method('DELETE')` dan dialog konfirmasi JavaScript `onsubmit="return confirm(...)"` untuk mencegah penghapusan yang tidak disengaja.

---

## Berkas yang Dibuat & Dimodifikasi

| No | Berkas | Status | Deskripsi |
|---|---|---|---|
| 1 | [StoreCourseRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/StoreCourseRequest.php) | Baru | Validasi penambahan mata kuliah baru |
| 2 | [UpdateCourseRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/UpdateCourseRequest.php) | Baru | Validasi update mata kuliah dengan unique ignore |
| 3 | [StoreUserRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/StoreUserRequest.php) | Baru | Validasi penambahan user baru |
| 4 | [UpdateUserRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/UpdateUserRequest.php) | Baru | Validasi update user dengan unique ignore |
| 5 | [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) | Dimodifikasi | Menggunakan FormRequest, `$request->validated()`, filter query & pagination |
| 6 | [UserController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php) | Dimodifikasi | Menggunakan FormRequest, `$request->validated()`, filter role & pagination |
| 7 | [layout.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/components/layout.blade.php) | Dimodifikasi | Menambahkan alert `session('error')` |
| 8 | [courses/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/create.blade.php) | Dimodifikasi | Menambahkan `@error` dan `is-invalid` |
| 9 | [courses/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/edit.blade.php) | Dimodifikasi | Menambahkan `@error` dan `is-invalid` |
| 10 | [courses/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/index.blade.php) | Dimodifikasi | Menambahkan search bar, filter status, dan pagination |
| 11 | [users/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/create.blade.php) | Dimodifikasi | Menambahkan `@error` dan `is-invalid` |
| 12 | [users/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/edit.blade.php) | Dimodifikasi | Menambahkan `@error` dan `is-invalid` |
| 13 | [users/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/index.blade.php) | Dimodifikasi | Menambahkan search bar, filter role, dan pagination |
| 14 | [style.css](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/public/css/style.css) | Dimodifikasi | Menambahkan styling `.form-error`, `.is-invalid`, `.filter-card`, `.pagination-wrapper` |

---

## Verifikasi Teknis

### 1. Pengecekan Sintaks (Linting)
Seluruh berkas PHP request dan controller lolos linting tanpa error:
```bash
php -l app/Http/Requests/StoreCourseRequest.php
php -l app/Http/Requests/UpdateCourseRequest.php
php -l app/Http/Requests/StoreUserRequest.php
php -l app/Http/Requests/UpdateUserRequest.php
php -l app/Http/Controllers/CourseController.php
php -l app/Http/Controllers/UserController.php
# Hasil: No syntax errors detected
```

### 2. Pengecekan Routing
Rute `courses` dan `users` terdaftar rapi pada kernel routing:
```bash
php artisan route:list --path=courses
php artisan route:list --path=users
# Hasil: 7 route courses dan 7 route users aktif dan terhubung ke controller
```

---

## Checklist Evaluasi & Rubrik Penilaian (Sub-CPMK 7 & Checkpoint 4)

| Kriteria Evaluasi | Status | Bukti Implementasi |
|---|---|---|
| **Validasi Server-Side Ketat** | Terpenuhi | Menggunakan Form Request terpisah, bukan validasi frontend |
| **Pencegahan Mass Assignment** | Terpenuhi | Controller hanya memanggil `$request->validated()` |
| **Penanganan Update Unique** | Terpenuhi | Menggunakan `Rule::unique()->ignore(...)` pada `code`, `email`, dan `nim_nip` |
| **Pola PRG (Post-Redirect-Get)** | Terpenuhi | Seluruh method store/update mengembalikan `redirect()->with('success', ...)` |
| **State Management yang Benar** | Terpenuhi | Filter pencarian ditaruh di query string (URL) dan dipertahankan lewat `withQueryString()` |
| **Proteksi CSRF** | Terpenuhi | Seluruh form menyertakan direktif `@csrf` |
| **User Experience Form** | Terpenuhi | Menggunakan helper `old()` dan pesan kesalahan kustom `@error` berbahasa Indonesia |
| **Pencegahan N+1 Query** | Terpenuhi | Menggunakan eager loading `with('lecturer')` pada query course |

---

## Alur Kerja dan Lokasi Kode Tiap Poin (Singkat & Ringkas)

Berikut adalah rangkuman alur kerja dan lokasi berkas kode untuk setiap poin implementasi tahap BUILD:

### 1. Form Request Mata Kuliah (Store & Update)
* **Alur:** Request form masuk -> dicegat Form Request sebelum masuk controller -> diperiksa aturan validasi (tipe data, rentang SKS 1-6, keabsahan dosen `exists`, enum status `in`, dan kode unik) -> jika gagal otomatis redirect balik + kirim pesan error bahasa Indonesia -> jika valid diserahkan ke controller via `$request->validated()`.
* **Lokasi Kode:**
  - [StoreCourseRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/StoreCourseRequest.php) : Aturan validasi tambah mata kuliah dan kustomisasi pesan error (`messages()`).
  - [UpdateCourseRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/UpdateCourseRequest.php) : Aturan update dengan `Rule::unique('courses', 'code')->ignore($this->course)`.

### 2. Form Blade Mata Kuliah (Create & Edit)
* **Alur:** Browser menampilkan form -> form diproteksi token `@csrf` -> input diisi helper `old('field', $default)` agar ketikan tidak hilang saat validasi gagal -> jika terdapat error validasi, direktif `@error('field')` menampilkan pesan error di bawah input dan menambahkan kelas CSS `.is-invalid` (border merah).
* **Lokasi Kode:**
  - [courses/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/create.blade.php) : Form tambah mata kuliah lengkap dengan `@csrf`, `old()`, dan `@error`.
  - [courses/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/edit.blade.php) : Form edit mata kuliah lengkap dengan `@csrf`, `@method('PUT')`, `old('field', $course->field)`, dan `@error`.

### 3. Flash Message Global di Layout
* **Alur:** Setelah controller berhasil/gagal memproses data (store/update/destroy) -> controller menyisipkan flash session (`with('success', ...)` atau `with('error', ...)`) -> layout utama mendeteksi `session('success')` atau `session('error')` -> banner alert notifikasi otomatis muncul di bagian atas halaman mana saja.
* **Lokasi Kode:**
  - [layout.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/components/layout.blade.php) : Pengecekan `@if(session('success'))` dan `@if(session('error'))` untuk merender kotak alert global.
  - [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php) & [UserController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php) : Pengiriman pesan flash saat redirect.

### 4. Daftar Mata Kuliah (Filter & Pagination)
* **Alur:** Pengguna mengetik pencarian atau memilih status di form GET -> controller menerima parameter `q` dan `status` -> menyaring data database secara fleksibel menggunakan query builder `when()` -> memuat relasi dosen `with('lecturer')` agar hemat query -> membatasi data per halaman `paginate(15)` dan menempelkan parameter URL `withQueryString()` -> view merender tabel dan link pagination `$courses->links()`.
* **Lokasi Kode:**
  - [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php#L16-L32) : Logika method `index()` (`when()`, `with('lecturer')`, `paginate(15)->withQueryString()`).
  - [courses/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/index.blade.php) : Form input pencarian GET, dropdown status, tombol reset, tabel data, dan blok `$courses->links()`.

### 5. Penerapan Penuh pada Modul Pengguna (Users)
* **Alur:** Menerapkan seluruh standar arsitektur di atas ke entitas Pengguna -> validasi request ditangani kelas khusus dengan proteksi email & NIM unik -> controller hanya mengolah data tervalidasi `$request->validated()` (role ditetapkan terpisah demi keamanan) -> form create/edit dilengkapi proteksi CSRF, `old()`, dan `@error` -> halaman index menyediakan pencarian (nama/email/NIM), filter role, dan pagination terjaga.
* **Lokasi Kode:**
  - [StoreUserRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/StoreUserRequest.php) & [UpdateUserRequest.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Requests/UpdateUserRequest.php) : Validasi data user baru dan update user dengan `ignore`.
  - [UserController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php) : Controller CRUD pengguna dengan validasi dan pagination.
  - [users/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/index.blade.php), [users/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/create.blade.php), [users/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/edit.blade.php) : Tampilan form dan index pengguna.

### 6. Konfirmasi Penghapusan & Method DELETE
* **Alur:** Pengguna menekan tombol Hapus -> event JavaScript `onsubmit="return confirm(...)"` memunculkan dialog konfirmasi pencegahan klik tidak sengaja -> jika dikonfirmasi "OK", form dikirim via method POST dengan menyertakan `@csrf` dan spoofing method `@method('DELETE')` -> rute `destroy` memanggil model `delete()` dan me-redirect dengan notifikasi sukses.
* **Lokasi Kode:**
  - [courses/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/index.blade.php#L76-L82) : Form tombol hapus mata kuliah (`@method('DELETE')` + `confirm`).
  - [users/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/index.blade.php#L77-L83) : Form tombol hapus pengguna (`@method('DELETE')` + `confirm`).
  - [CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php#L84-L89) & [UserController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php#L98-L103) : Method `destroy()` yang mengeksekusi penghapusan.
