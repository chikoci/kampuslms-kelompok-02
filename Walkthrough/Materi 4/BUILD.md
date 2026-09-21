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
