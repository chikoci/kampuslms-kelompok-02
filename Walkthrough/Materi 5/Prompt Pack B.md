# Middleware Peran & Konfigurasi Laravel 12 (Minggu 5 - Prompt Pack B)

## Konteks & Prompt
```text
Buatkan middleware bernama EnsureUserHasRole untuk Laravel 12 yang
menerima parameter peran (bisa lebih dari satu, contoh: role:admin,dosen).

Ketentuan PENTING:
- Ini Laravel 12. app/Http/Kernel.php SUDAH TIDAK ADA.
  Tunjukkan pendaftaran alias middleware di bootstrap/app.php.
- Tangani kasus pengguna belum login secara berbeda dari pengguna
  yang login tapi perannya salah (401 vs 403).
- Jelaskan kenapa middleware ini saja TIDAK cukup untuk mengamankan
  data per-objek, dan apa yang masih dibutuhkan.
```

---

## 1. Kode Middleware `EnsureUserHasRole` (Laravel 12)

Lokasi berkas: `app/Http/Middleware/EnsureUserHasRole.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Menangani request masuk dan memverifikasi peran pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles (Bisa menerima lebih dari satu peran, contoh: 'admin', 'dosen')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Kasus Pengguna Belum Login
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated: Anda harus login terlebih dahulu.'
                ], 401);
            }

            // Untuk web request, abort 401 atau arahkan ke rute login
            abort(401, 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

        // 2. Kasus Pengguna Sudah Login, tapi Perannya Salah
        if (! in_array($request->user()->role, $roles, true)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses yang diizinkan untuk membuka halaman ini.');
        }

        // 3. Pengguna Sah & Peran Cocok
        return $next($request);
    }
}
```

---

## 2. Pendaftaran Alias Middleware di `bootstrap/app.php`

> **PERINGATAN PENTING:**  
> Pada Laravel 11 dan 12, berkas `app/Http/Kernel.php` **SUDAH TIDAK ADA (DIHAPUS)**.  
> Seluruh pendaftaran alias middleware dilakukan di dalam method `->withMiddleware(...)` pada berkas **`bootstrap/app.php`**.

### Penerapan pada `bootstrap/app.php`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Mendaftarkan alias middleware 'role'
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

Setelah didaftarkan, middleware ini dapat dipanggil di `routes/web.php` dengan sintaks:
* `Route::middleware('role:admin')` ➔ Hanya untuk admin.
* `Route::middleware('role:admin,dosen')` ➔ Untuk admin dan dosen.
* `Route::middleware('role:mahasiswa')` ➔ Hanya untuk mahasiswa.

---

## 3. Penanganan Kasus 401 Unauthorized vs 403 Forbidden

Middleware ini secara tegas membedakan dua kondisi kesalahan hak akses:

| Kondisi | Status HTTP | Makna Semantik | Penanganan Sistem |
|---|---|---|---|
| **Pengguna Belum Login** | **401 Unauthorized** | Server tidak mengenali identitas pemohon (*"Siapa kamu?"*). | Pengguna diarahkan ke form login untuk membuktikan identitasnya. |
| **Pengguna Sudah Login, Tapi Peran Salah** | **403 Forbidden** | Server mengenali identitas pemohon, namun peran/izinnya dilarang masuk (*"Kami tahu kamu Mahasiswa, tapi area ini khusus Admin"*). | Pengguna diblokir dengan halaman error 403. Percuma mencoba login ulang dengan akun yang sama karena perannya memang dilarang. |

Mengembalikan 401 untuk kasus pengguna yang salah peran adalah kesalahan semantik web, karena pengguna akan bingung disuruh login lagi padahal dia sudah memiliki sesi login aktif.

---

## 4. Kenapa Middleware Saja TIDAK Cukup Mengamankan Data Per-Objek?

### Analisis Keterbatasan Middleware:
Middleware bekerja di level **"Pintu Gerbang Utama" (Coarse-Grained Authorization)**:
* Middleware hanya memeriksa atribut global dari pengguna yang sedang login: *`auth()->user()->role`*.
* Middleware memeriksa apakah rute `/dosen/*` boleh dimasuki oleh orang yang berstatus dosen.

### Skenario Kegagalan (Batas Keamanan):
Misalkan ada rute:
```php
Route::middleware('role:dosen')->group(function () {
    Route::get('/dosen/courses/{course}/edit', [CourseController::class, 'edit']);
});
```
1. **Dosen A** login ke sistem (punya `role: 'dosen'`).
2. Dosen A membuka URL edit mata kuliah miliknya: `/dosen/courses/1/edit` ➔ **Lolos**.
3. Dosen A iseng mengganti URL menjadi mata kuliah milik **Dosen B**: `/dosen/courses/2/edit`.
4. **Apa yang terjadi di Middleware?**  
   Middleware mengecek: *"Apakah Dosen A punya role dosen?"* ➔ **YA!**  
   Maka middleware **MELOLOSKANNYA**!
5. Akibatnya, Dosen A bisa mengedit atau bahkan menghapus silabus mata kuliah milik Dosen B!

### Apa yang Masih Dibutuhkan?
Middleware **tidak memiliki akses ke model data spesifik** yang sedang diakses di database. Untuk mencegah kebocoran antar-pengguna dengan peran yang sama, dibutuhkan:
1. **Pemeriksaan Kepemilikan Sementara (Minggu 5):**  
   Menambahkan `abort_unless($course->lecturer_id === auth()->id(), 403)` langsung di dalam controller.
2. **Policy (Otorisasi Per-Objek / Fine-Grained Authorization di Minggu 7):**  
   Membuat kelas Policy tersendiri (`CoursePolicy`, `SubmissionPolicy`) yang mengecek relasi kepemilikan data sebelum aksi controller dieksekusi.
