<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TentangController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;

/*
|--------------------------------------------------------------------------
| 1. Rute Publik & Navigasi Utama
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('dashboard');
})->name('home');

Route::get('/tentang', [TentangController::class, 'index'])->name('tentang');

// Rute Mata Kuliah & Pengguna (CRUD Penuh)
Route::resource('courses', CourseController::class);
Route::resource('users', UserController::class);

/*
|--------------------------------------------------------------------------
| 2. Rute Bersarang (Nested Routes) dengan shallow() dan scopeBindings()
|--------------------------------------------------------------------------
| Menjamin materi dan tugas terikat secara hierarkis ke mata kuliah induk.
*/
Route::scopeBindings()->group(function () {
    // Nested: /courses/{course}/materials & shallow: /materials/{material}
    Route::resource('courses.materials', MaterialController::class)->shallow();
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');

    // Nested: /courses/{course}/assignments & shallow: /assignments/{assignment}
    Route::resource('courses.assignments', AssignmentController::class)->shallow();

    // Nested: /assignments/{assignment}/submissions
    Route::get('/assignments/{assignment}/submissions', [SubmissionController::class, 'index'])->name('assignments.submissions.index');
    Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])->name('assignments.submissions.store');
});

// Detail submission dan download (dilindungi IDOR check di controller)
Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');

/*
|--------------------------------------------------------------------------
| 3. Grup Berdasarkan Peran (Role Group dengan Prefix & Name Prefix)
|--------------------------------------------------------------------------
| Dilindungi oleh middleware 'role' yang didaftarkan di bootstrap/app.php
*/

// Grup Admin: Mengelola seluruh pengguna dan mata kuliah sistem
Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('courses', CourseController::class);
});

// Grup Dosen: Mengelola pembelajaran dan tugas mata kuliah yang diampu
Route::middleware(['role:dosen'])->prefix('dosen')->name('dosen.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('courses', CourseController::class)->only(['index', 'show', 'edit', 'update']);

    Route::scopeBindings()->group(function () {
        Route::resource('courses.materials', MaterialController::class)->shallow();
        Route::resource('courses.assignments', AssignmentController::class)->shallow();
    });
});

// Grup Mahasiswa: Mengakses mata kuliah terdaftar dan pengumpulan tugas
Route::middleware(['role:mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('courses', CourseController::class)->only(['index', 'show']);
});


/*
|--------------------------------------------------------------------------
| 4. Rute Pembantu Pengujian (Khusus Praktikum Minggu 5)
|--------------------------------------------------------------------------
| Berguna untuk berpindah peran saat menguji skenario BREAK & IDOR di browser.
*/
Route::prefix('dev')->group(function () {
    // Login cepat sebagai Mahasiswa (User ID 3)
    Route::get('/login-mahasiswa', function () {
        Auth::loginUsingId(3);
        return redirect('/courses')->with('success', 'Sekarang login sebagai: Mahasiswa');
    });

    // Login cepat sebagai Dosen (User ID 2)
    Route::get('/login-dosen', function () {
        Auth::loginUsingId(2);
        return redirect('/courses')->with('success', 'Sekarang login sebagai: Dosen');
    });

    // Login cepat sebagai Admin (User ID 1)
    Route::get('/login-admin', function () {
        Auth::loginUsingId(1);
        return redirect('/courses')->with('success', 'Sekarang login sebagai: Admin');
    });

    // Logout kembali menjadi Tamu (Guest)
    Route::get('/logout', function () {
        Auth::logout();
        return redirect('/courses')->with('success', 'Sudah logout (status: Guest)');
    });
});
