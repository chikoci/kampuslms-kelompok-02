<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — KampusLMS (Laravel 12)
|--------------------------------------------------------------------------
| Kontrak API resmi Bagian 5 Spesifikasi Proyek.
| Prefix global: /api/v1
| Autentikasi: Laravel Sanctum (Bearer Token)
| Rate Limiting: 5/menit untuk login, 60/menit untuk rute terautentikasi.
*/

Route::prefix('v1')->group(function () {
    // 1. Rute Publik (Autentikasi Login dengan throttle 5 permintaan per menit)
    // Berikan nama unik 'api.v1.login' agar tidak menimpa rute web 'login' (/login)
    // yang digunakan oleh controller untuk redirect pengguna browser.
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.v1.login');

    // 2. Rute Terlindungi Sanctum (Throttle 60 permintaan per menit)
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Autentikasi & Profil
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Modul Mata Kuliah
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('/courses/{course}/materials', [CourseController::class, 'materials']);
        Route::get('/courses/{course}/assignments', [CourseController::class, 'assignments']);

        // Modul Tugas (Assignments)
        Route::post('/assignments', [AssignmentController::class, 'store']);
        Route::match(['put', 'patch'], '/assignments/{assignment}', [AssignmentController::class, 'update']);
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);
        Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions']);
        Route::post('/assignments/{assignment}/submissions', [AssignmentController::class, 'storeSubmission']);

        // Modul Pengumpulan & Penilaian (Submissions & Grades)
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
        Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'grade']);

        // Modul Notifikasi
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    });
});
