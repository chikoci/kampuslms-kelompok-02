<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerTentang;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('dashboard');
})->name('home');

Route::get('/tentang', [ControllerTentang::class, 'index'])->name('tentang');

// Resource route lengkap untuk Mata Kuliah (CRUD)
Route::resource('courses', CourseController::class);

// Resource route lengkap untuk Pengguna (CRUD)
Route::resource('users', UserController::class);

