<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RubricController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TentangController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. Rute Publik & Navigasi Utama
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::get('/tentang', [TentangController::class, 'index'])->name('tentang');

/*
|--------------------------------------------------------------------------
| 2. Autentikasi Pengguna & Reset Kata Sandi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Alur Lupa & Reset Kata Sandi
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| 3. Fitur Utama Terproteksi (Wajib Login - auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard Multi-Peran (Admin, Dosen, Mahasiswa)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Registrasi Pengguna Baru (Khusus Admin yang mendaftarkan)
    Route::get('/admin/register', [AuthController::class, 'showRegisterForm'])->name('admin.register');
    Route::post('/admin/register', [AuthController::class, 'register'])->name('admin.register.store');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::post('/register/submit', [AuthController::class, 'register'])->name('register.post');

    // CRUD Pengguna (Otorisasi via UserPolicy)
    Route::resource('users', UserController::class);

    // CRUD Mata Kuliah (Otorisasi via CoursePolicy)
    Route::resource('courses', CourseController::class);
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::delete('/courses/{course}/unenroll/{student}', [CourseController::class, 'unenroll'])->name('courses.unenroll');

    // Rute Bersarang (Materi & Tugas)
    Route::scopeBindings()->group(function () {
        // Materi Perkuliahan
        Route::resource('courses.materials', MaterialController::class)->shallow();
        Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');

        // Tugas Perkuliahan
        Route::resource('courses.assignments', AssignmentController::class)->shallow();

        // Fitur Diferensiasi: Rubrik Penilaian Bertingkat
        Route::get('/assignments/{assignment}/rubric', [RubricController::class, 'manage'])->name('assignments.rubric.manage');
        Route::post('/assignments/{assignment}/rubric', [RubricController::class, 'store'])->name('assignments.rubric.store');
        Route::delete('/assignments/{assignment}/rubric', [RubricController::class, 'destroy'])->name('assignments.rubric.destroy');

        // Pengumpulan Tugas (Submissions)
        Route::get('/courses/{course}/submissions', [SubmissionController::class, 'courseIndex'])->name('courses.submissions');
        Route::get('/assignments/{assignment}/submissions', [SubmissionController::class, 'index'])->name('assignments.submissions.index');
        Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])->name('assignments.submissions.store');
    });

    // Detail Pengumpulan, Unduhan, dan Penilaian (Grade)
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');
    Route::delete('/submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
    Route::post('/submissions/{submission}/grades', [SubmissionController::class, 'grade'])->name('submissions.grades.store');
    Route::delete('/submissions/{submission}/grades', [SubmissionController::class, 'destroyGrade'])->name('submissions.grades.destroy');

    // Rekap Nilai Akademik (Milestone M3)
    Route::get('/courses/{course}/grades', [GradeController::class, 'courseGrades'])->name('courses.grades');
    Route::get('/my-grades', [GradeController::class, 'studentGrades'])
        ->middleware('role:mahasiswa')
        ->name('grades.student');

    // Modul Notifikasi Asinkron (Milestone M3)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
