# Perancangan Struktur Route & Audit IDOR (Minggu 5 - Prompt Pack A)

## Konteks & Prompt
```text
Konteks: LMS Laravel 12. Peran: admin, dosen, mahasiswa.
Modul: users, courses, enrollment, materials, assignments, submissions, grades, notifications.

Rancangkan struktur lengkap routes/web.php memakai route group.

Ketentuan:
- Kelompokkan berdasarkan peran dengan prefix dan name prefix.
- Gunakan Route::resource dan shallow() di tempat yang tepat.
- Untuk setiap grup, sebutkan middleware yang dipakai.
- Sajikan sebagai TABEL dulu (method, URI, nama, controller, peran), baru setelah saya setujui, tulis kodenya.
- Tandai route mana saja yang menerima parameter model dan karenanya berisiko IDOR.
```

---

## 1. Tabel Rancangan Struktur Route Lengkap

### A. Rute Publik & Tamu (Guest)
* **Middleware:** `guest`
* **Prefix:** `-` | **Name Prefix:** `-`

| Method | URI | Nama Route | Controller & Action | Peran | Risiko IDOR? |
|---|---|---|---|---|---|
| GET | `/` | `home` | `HomeController@index` | Publik | Tidak |
| GET | `/login` | `login` | `AuthController@showLogin` | Guest | Tidak |
| POST | `/login` | `login.attempt` | `AuthController@login` | Guest | Tidak |

### B. Rute Bersama (Semua Pengguna Terautentikasi)
* **Middleware:** `auth`
* **Prefix:** `-` | **Name Prefix:** `-`

| Method | URI | Nama Route | Controller & Action | Peran | Risiko IDOR? |
|---|---|---|---|---|---|
| POST | `/logout` | `logout` | `AuthController@logout` | Semua | Tidak |
| GET | `/dashboard` | `dashboard` | `DashboardController` | Semua | Tidak |
| GET | `/profile` | `profile.edit` | `ProfileController@edit` | Semua | Tidak |
| PATCH | `/profile` | `profile.update` | `ProfileController@update` | Semua | Tidak |
| GET | `/notifications` | `notifications.index` | `NotificationController@index` | Semua | Tidak |
| PATCH | `/notifications/{notification}/read` | `notifications.read` | `NotificationController@markAsRead` | Semua | ⚠️ **IDOR**: Pengguna bisa menandai notifikasi milik orang lain |

### C. Grup Admin (Master Data & Manajemen Sistem)
* **Middleware:** `['auth', 'role:admin']`
* **Prefix:** `/admin` | **Name Prefix:** `admin.`

| Method | URI | Nama Route | Controller & Action | Peran | Risiko IDOR? |
|---|---|---|---|---|---|
| GET | `/admin/users` | `admin.users.index` | `UserController@index` | Admin | Tidak |
| GET | `/admin/users/create` | `admin.users.create` | `UserController@create` | Admin | Tidak |
| POST | `/admin/users` | `admin.users.store` | `UserController@store` | Admin | Tidak |
| GET | `/admin/users/{user}` | `admin.users.show` | `UserController@show` | Admin | Aman (Hak Akses Admin Penuh) |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | `UserController@edit` | Admin | Aman (Hak Akses Admin Penuh) |
| PUT/PATCH | `/admin/users/{user}` | `admin.users.update` | `UserController@update` | Admin | Aman (Hak Akses Admin Penuh) |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | `UserController@destroy` | Admin | Aman (Hak Akses Admin Penuh) |
| GET | `/admin/courses` | `admin.courses.index` | `CourseController@index` | Admin | Tidak |
| GET | `/admin/courses/create` | `admin.courses.create` | `CourseController@create` | Admin | Tidak |
| POST | `/admin/courses` | `admin.courses.store` | `CourseController@store` | Admin | Tidak |
| GET | `/admin/courses/{course}` | `admin.courses.show` | `CourseController@show` | Admin | Aman (Hak Akses Admin Penuh) |
| GET | `/admin/courses/{course}/edit` | `admin.courses.edit` | `CourseController@edit` | Admin | Aman (Hak Akses Admin Penuh) |
| PUT/PATCH | `/admin/courses/{course}` | `admin.courses.update` | `CourseController@update` | Admin | Aman (Hak Akses Admin Penuh) |
| DELETE | `/admin/courses/{course}` | `admin.courses.destroy` | `CourseController@destroy` | Admin | Aman (Hak Akses Admin Penuh) |
| POST | `/admin/courses/{course}/enroll` | `admin.courses.enroll` | `EnrollmentController@store` | Admin | Aman |
| DELETE | `/admin/courses/{course}/enroll/{user}` | `admin.courses.unenroll` | `EnrollmentController@destroy` | Admin | Aman |

### D. Grup Dosen (Pengelolaan Pembelajaran Mata Kuliah Sendiri)
* **Middleware:** `['auth', 'role:dosen']`
* **Prefix:** `/dosen` | **Name Prefix:** `dosen.`

| Method | URI | Nama Route | Controller & Action | Peran | Risiko IDOR? |
|---|---|---|---|---|---|
| GET | `/dosen/courses` | `dosen.courses.index` | `CourseController@index` | Dosen | Tidak |
| GET | `/dosen/courses/{course}` | `dosen.courses.show` | `CourseController@show` | Dosen | ⚠️ **IDOR**: Dosen A bisa melihat detail MK milik Dosen B |
| GET | `/dosen/courses/{course}/materials/create` | `dosen.courses.materials.create` | `MaterialController@create` | Dosen | ⚠️ **IDOR**: Dosen A mengunggah materi ke MK milik Dosen B |
| POST | `/dosen/courses/{course}/materials` | `dosen.courses.materials.store` | `MaterialController@store` | Dosen | ⚠️ **IDOR**: Dosen A menyimpan materi ke MK milik Dosen B |
| GET | `/dosen/materials/{material}/edit` | `dosen.materials.edit` | `MaterialController@edit` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A mengedit materi milik Dosen B |
| PUT/PATCH | `/dosen/materials/{material}` | `dosen.materials.update` | `MaterialController@update` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A memperbarui materi milik Dosen B |
| DELETE | `/dosen/materials/{material}` | `dosen.materials.destroy` | `MaterialController@destroy` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A menghapus materi milik Dosen B |
| GET | `/dosen/courses/{course}/assignments/create` | `dosen.courses.assignments.create` | `AssignmentController@create` | Dosen | ⚠️ **IDOR**: Dosen A membuat tugas di MK milik Dosen B |
| POST | `/dosen/courses/{course}/assignments` | `dosen.courses.assignments.store` | `AssignmentController@store` | Dosen | ⚠️ **IDOR**: Dosen A menyimpan tugas di MK milik Dosen B |
| GET | `/dosen/assignments/{assignment}` | `dosen.assignments.show` | `AssignmentController@show` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A melihat tugas di MK milik Dosen B |
| GET | `/dosen/assignments/{assignment}/edit` | `dosen.assignments.edit` | `AssignmentController@edit` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A mengedit tugas milik Dosen B |
| PUT/PATCH | `/dosen/assignments/{assignment}` | `dosen.assignments.update` | `AssignmentController@update` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A memperbarui tugas milik Dosen B |
| DELETE | `/dosen/assignments/{assignment}` | `dosen.assignments.destroy` | `AssignmentController@destroy` *(shallow)* | Dosen | ⚠️ **IDOR**: Dosen A menghapus tugas milik Dosen B |
| GET | `/dosen/assignments/{assignment}/submissions` | `dosen.assignments.submissions.index` | `SubmissionController@index` | Dosen | ⚠️ **IDOR**: Dosen A melihat tugas mahasiswa di kelas Dosen B |
| POST | `/dosen/submissions/{submission}/grade` | `dosen.submissions.grade` | `GradeController@store` | Dosen | ⚠️ **IDOR**: Dosen A menilai tugas mahasiswa di kelas Dosen B |

### E. Grup Mahasiswa (Aktivitas Belajar & Pengumpulan Tugas)
* **Middleware:** `['auth', 'role:mahasiswa']`
* **Prefix:** `/mahasiswa` | **Name Prefix:** `mahasiswa.`

| Method | URI | Nama Route | Controller & Action | Peran | Risiko IDOR? |
|---|---|---|---|---|---|
| GET | `/mahasiswa/courses` | `mahasiswa.courses.index` | `CourseController@index` | Mhs | Tidak |
| GET | `/mahasiswa/courses/{course}` | `mahasiswa.courses.show` | `CourseController@show` | Mhs | ⚠️ **IDOR**: Mahasiswa melihat MK yang tidak diikutinya |
| GET | `/mahasiswa/courses/{course}/materials/{material}/download` | `mahasiswa.courses.materials.download` | `MaterialController@download` | Mhs | ⚠️ **IDOR**: Mengunduh materi dari kelas yang tidak diambil |
| GET | `/mahasiswa/courses/{course}/assignments/{assignment}` | `mahasiswa.courses.assignments.show` | `AssignmentController@show` | Mhs | ⚠️ **IDOR**: Membaca soal tugas dari kelas yang tidak diambil |
| POST | `/mahasiswa/courses/{course}/assignments/{assignment}/submissions` | `mahasiswa.courses.assignments.submissions.store` | `SubmissionController@store` | Mhs | ⚠️ **IDOR**: Mengumpulkan tugas ke MK yang bukan haknya |
| GET | `/mahasiswa/submissions/{submission}` | `mahasiswa.submissions.show` | `SubmissionController@show` | Mhs | ⚠️ **BAHAYA KRITIS (IDOR)**: Mahasiswa A melihat hasil tugas mahasiswa B dengan mengganti ID URL |
| GET | `/mahasiswa/grades` | `mahasiswa.grades.index` | `GradeController@index` | Mhs | Tidak (Otomatis difilter `auth()->id()`) |

---

## 2. Implementasi Kode Lengkap `routes/web.php`

Berikut kode implementasi berdasarkan tabel arsitektur di atas:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Dosen\CourseController as DosenCourseController;
use App\Http\Controllers\Dosen\MaterialController as DosenMaterialController;
use App\Http\Controllers\Dosen\AssignmentController as DosenAssignmentController;
use App\Http\Controllers\Dosen\SubmissionController as DosenSubmissionController;
use App\Http\Controllers\Dosen\GradeController as DosenGradeController;
use App\Http\Controllers\Mahasiswa\CourseController as MahasiswaCourseController;
use App\Http\Controllers\Mahasiswa\MaterialController as MahasiswaMaterialController;
use App\Http\Controllers\Mahasiswa\AssignmentController as MahasiswaAssignmentController;
use App\Http\Controllers\Mahasiswa\SubmissionController as MahasiswaSubmissionController;
use App\Http\Controllers\Mahasiswa\GradeController as MahasiswaGradeController;

/*
|--------------------------------------------------------------------------
| 1. Rute Publik & Guest (Belum Login)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

/*
|--------------------------------------------------------------------------
| 2. Rute Global Terautentikasi (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    /*
    |--------------------------------------------------------------------------
    | 3. Grup Admin (role:admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', AdminUserController::class);
        Route::resource('courses', AdminCourseController::class);

        // Pengelolaan Enrollment Mahasiswa ke Mata Kuliah
        Route::post('/courses/{course}/enroll', [AdminEnrollmentController::class, 'store'])->name('courses.enroll');
        Route::delete('/courses/{course}/enroll/{user}', [AdminEnrollmentController::class, 'destroy'])->name('courses.unenroll');
    });

    /*
    |--------------------------------------------------------------------------
    | 4. Grup Dosen (role:dosen)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:dosen')->prefix('dosen')->name('dosen.')->group(function () {
        Route::resource('courses', DosenCourseController::class)->only(['index', 'show']);

        // Rute Bersarang (Nested) dengan shallow() dan scopeBindings()
        Route::scopeBindings()->group(function () {
            Route::resource('courses.materials', DosenMaterialController::class)->shallow();
            Route::resource('courses.assignments', DosenAssignmentController::class)->shallow();
        });

        // Pengelolaan Pengumpulan Tugas & Penilaian Mahasiswa
        Route::get('/assignments/{assignment}/submissions', [DosenSubmissionController::class, 'index'])->name('assignments.submissions.index');
        Route::post('/submissions/{submission}/grade', [DosenGradeController::class, 'store'])->name('submissions.grade');
    });

    /*
    |--------------------------------------------------------------------------
    | 5. Grup Mahasiswa (role:mahasiswa)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::resource('courses', MahasiswaCourseController::class)->only(['index', 'show']);

        // Akses Materi & Tugas Kelas yang Diikuti
        Route::scopeBindings()->group(function () {
            Route::get('/courses/{course}/materials/{material}/download', [MahasiswaMaterialController::class, 'download'])->name('courses.materials.download');
            Route::get('/courses/{course}/assignments/{assignment}', [MahasiswaAssignmentController::class, 'show'])->name('courses.assignments.show');
            Route::post('/courses/{course}/assignments/{assignment}/submissions', [MahasiswaSubmissionController::class, 'store'])->name('courses.assignments.submissions.store');
        });

        // Detail Submission & Rekap Nilai Pribadi
        Route::get('/submissions/{submission}', [MahasiswaSubmissionController::class, 'show'])->name('submissions.show');
        Route::get('/grades', [MahasiswaGradeController::class, 'index'])->name('grades.index');
    });
});
```

---

## 3. Analisis Kritis Titik Rawan IDOR

1. **Kenapa Route Model Binding Memicu IDOR?**
   Route model binding (seperti `{submission}`) secara default hanya menjalankan `Submission::findOrFail($id)`. Selama record itu ada di database, data langsung disajikan ke browser. Sistem tidak memeriksa apakah user yang login adalah pemilik submission tersebut atau bukan.

2. **Dua Titik Rawan Paling Berbahaya:**
   * `/mahasiswa/submissions/{submission}`: Jika tidak dicek kepemilikan `submission->user_id === auth()->id()`, seorang mahasiswa cukup mengganti angka URL untuk mengintip tugas mahasiswa lain.
   * `/dosen/materials/{material}` & `/dosen/assignments/{assignment}`: Dosen A yang login bisa mengedit atau menghapus materi milik Dosen B jika controller tidak memeriksa relasi dosen terhadap mata kuliah tersebut.
