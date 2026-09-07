<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControllerTentang;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tentang', [ControllerTentang::class, 'index']);