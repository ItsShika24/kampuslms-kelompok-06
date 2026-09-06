<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

// Route untuk halaman utama.
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Route untuk halaman Tentang.
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

// Route untuk menampilkan daftar mata kuliah.
Route::get('/mata-kuliah', [CourseController::class, 'index'])
    ->name('mata-kuliah.index');

// Route untuk menampilkan detail satu mata kuliah.
Route::get('/mata-kuliah/{mataKuliah}', [CourseController::class, 'show'])
    ->name('mata-kuliah.show');