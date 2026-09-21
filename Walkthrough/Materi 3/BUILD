# Hasil Implementasi Tahap BUILD (Milestone M1 : Tugas 1)

Dokumen ini merangkum seluruh hasil implementasi tahap **BUILD** untuk menyelesaikan **Milestone M1 (Tugas 1)** sesuai dengan spesifikasi proyek KampusLMS dan arahan modul Minggu 3.

---

## 1. Ruang Lingkup dan Batasan Sesuai Modul

Berdasarkan ketentuan modul:
> "Belum diperlukan minggu ini: login, validasi mendalam, pembatasan hak akses. Itu minggu 4-7. Fokus sekarang di struktur data yang benar."

Tahap BUILD ini fokus menyelesaikan 2 deliverable inti:
1. **Deliverable 4:** CRUD Mata Kuliah berfungsi penuh (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) terhubung langsung ke database MySQL melalui model Eloquent `Course`.
2. **Deliverable 5:** CRUD Pengguna berfungsi penuh (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) dengan penegakan keamanan: kolom `role` **tidak ada di `$fillable`**, melainkan diatur secara eksplisit di controller.

---

## 2. Berkas yang Dibuat & Diperbarui

### A. Routing ([routes/web.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/routes/web.php))
- Mendaftarkan resource route penuh untuk Mata Kuliah:
  `Route::resource('courses', CourseController::class);`
- Mendaftarkan resource route penuh untuk Pengguna:
  `Route::resource('users', UserController::class);`

### B. Controllers

#### 1. [app/Http/Controllers/CourseController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/CourseController.php)
- Menggantikan array dummy statis minggu ke-2 menjadi kueri database Eloquent nyata.
- **`index()`**: Mengambil daftar mata kuliah beserta relasi dosen pengampu (`Course::with('lecturer')->latest()->get()`).
- **`create()`**: Mengambil data dosen (`User::where('role', 'dosen')->get()`) untuk pilihan dropdown pengampu.
- **`store()`**: Memvalidasi input (`code`, `name`, `sks`, `lecturer_id`, `status`) dan menyimpan ke database lewat `Course::create($validated)`.
- **`show()`**: Eager loading relasi `lecturer`, `materials`, dan `assignments` untuk ditampilkan di halaman detail.
- **`edit()`**: Menyiapkan data awal mata kuliah dan opsi dosen pengampu.
- **`update()`**: Memperbarui data mata kuliah dengan validasi keunikan kode (`unique:courses,code,id`).
- **`destroy()`**: Menghapus mata kuliah (didukung `cascadeOnDelete` database untuk membersihkan materi dan tugas terkait).

#### 2. [app/Http/Controllers/UserController.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/app/Http/Controllers/UserController.php)
- **`index()`**: Mengambil seluruh pengguna dari database (`User::latest()->get()`).
- **`create()`**: Menampilkan formulir pendaftaran pengguna baru.
- **`store()`**: 
  - Memvalidasi input request.
  - Memasukkan atribut aman (`name`, `email`, `nim_nip`).
  - Mengisi password yang otomatis ter-hash via casting Laravel 12.
  - **Penegakan Keamanan Eksplisit:**
    ```php
    $user->role = $validated['role']; // Tidak melalui mass assignment
    $user->save();
    ```
- **`show()`**: Menampilkan profil pengguna dan relasi mata kuliah yang diajar (dosen) atau mata kuliah yang diikuti (mahasiswa).
- **`edit()`**: Menampilkan formulir edit profil pengguna.
- **`update()`**: Memperbarui data pengguna, dengan `role` diatur eksplisit dan password hanya diubah jika diisi baru.
- **`destroy()`**: Menghapus pengguna secara aman menggunakan `SoftDeletes`.

### C. Tampilan Antarmuka (Blade Views)

1. **[resources/views/components/layout.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/components/layout.blade.php)**
   - Menambahkan menu navigasi "Pengguna" di navbar utama.
   - Menambahkan komponen alert flash message (`session('success')` dan `$errors->any()`) untuk memberikan umpan balik visual saat aksi CRUD berhasil atau gagal.
2. **[resources/css/app.css](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/css/app.css)**
   - Menambahkan utility class untuk form input (`.form-group`, `.form-control`, `.form-select`), tabel data (`.table-container`, `.data-table`), dan badge status/role.
3. **Views Mata Kuliah:**
   - [courses/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/index.blade.php): Tabel daftar mata kuliah dengan relasi dosen dan aksi (Detail, Edit, Hapus).
   - [courses/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/create.blade.php): Form tambah mata kuliah dengan dropdown dosen.
   - [courses/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/edit.blade.php): Form edit mata kuliah.
   - [courses/show.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/courses/show.blade.php): Halaman detail menampilkan dosen pengampu, materi perkuliahan, dan tugas akademik.
4. **Views Pengguna:**
   - [users/index.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/index.blade.php): Tabel daftar pengguna lengkap dengan badge role dan aksi CRUD.
   - [users/create.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/create.blade.php): Form pendaftaran pengguna baru.
   - [users/edit.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/edit.blade.php): Form edit profil dan role pengguna.
   - [users/show.blade.php](file:///d:/Semester%205/Pemweb/Week1/kampuslms-kelompok-02/resources/views/users/show.blade.php): Detail profil pengguna beserta daftar kelas yang diampu atau diikuti.

---

## 3. Hasil Pengujian Fungsional

Seluruh aksi CRUD telah diuji dan diverifikasi secara otomatis:
- **Course CRUD:**
  - `index`: Berhasil membaca seluruh mata kuliah aktif dari database.
  - `create` & `store`: Berhasil menyimpan mata kuliah baru dengan relasi dosen.
  - `show`: Berhasil menampilkan relasi materi dan tugas mata kuliah.
  - `edit` & `update`: Berhasil memperbarui data dan bobot SKS.
  - `destroy`: Berhasil menghapus mata kuliah dari database.
- **User CRUD:**
  - `index`: Berhasil membaca seluruh 34 pengguna dari database seeder.
  - `create` & `store`: Berhasil menyimpan user baru dengan penugasan `role` secara aman.
  - `show`: Berhasil memuat relasi kelas mengajar atau kelas terdaftar.
  - `edit` & `update`: Berhasil memperbarui nama dan peran pengguna.
  - `destroy`: Berhasil melakukan soft delete (kolom `deleted_at` terisi dan akun disembunyikan dari query aktif).

---

## 4. Cara Mencoba di Browser

Jalankan server aplikasi Anda:
```bash
php artisan serve
```

Lalu buka alamat berikut di browser:
- **Daftar Mata Kuliah:** `http://127.0.0.1:8000/courses`
- **Tambah Mata Kuliah:** `http://127.0.0.1:8000/courses/create`
- **Daftar Pengguna:** `http://127.0.0.1:8000/users`
- **Tambah Pengguna:** `http://127.0.0.1:8000/users/create`
