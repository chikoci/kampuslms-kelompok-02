<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerTentang;
use App\Http\Controllers\CourseController;

Route::get('/', function () {
    return view('dashboard');
})->name('home');

Route::get('/tentang', [ControllerTentang::class, 'index'])->name('tentang');

// APA: Route untuk halaman daftar mata kuliah menggunakan method GET.
// KENAPA: Karena kita hanya ingin MENGAMBIL (Read) data, bukan mengubahnya.
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');

// APA: Route spesifik (seperti /courses/create) ditaruh DI ATAS route dinamis.
// KENAPA: Agar tidak ditangkap sebagai parameter {course} oleh rute di bawahnya.
Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');

// APA: Route dinamis {course} untuk menampilkan detail satu mata kuliah.
// KENAPA: Agar satu route bisa menangani semua ID (contoh: /courses/1, /courses/2).
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

// Route pelengkap untuk form edit, update, dan delete (belum kita buat view-nya)
Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
