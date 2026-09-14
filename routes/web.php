<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

// ==================== MATA KULIAH ====================

// Menampilkan daftar mata kuliah.
Route::get('/mata-kuliah', [CourseController::class, 'index'])
    ->name('mata-kuliah.index');

// Menampilkan form tambah mata kuliah.
Route::get('/mata-kuliah/create', [CourseController::class, 'create'])
    ->name('mata-kuliah.create');

// Menyimpan mata kuliah baru.
Route::post('/mata-kuliah', [CourseController::class, 'store'])
    ->name('mata-kuliah.store');

// Menampilkan form edit mata kuliah.
Route::get('/mata-kuliah/{mataKuliah}/edit', [CourseController::class, 'edit'])
    ->name('mata-kuliah.edit');

// Memperbarui data mata kuliah.
Route::put('/mata-kuliah/{mataKuliah}', [CourseController::class, 'update'])
    ->name('mata-kuliah.update');

// Menghapus mata kuliah.
Route::delete('/mata-kuliah/{mataKuliah}', [CourseController::class, 'destroy'])
    ->name('mata-kuliah.destroy');

// Menampilkan detail satu mata kuliah.
Route::get('/mata-kuliah/{mataKuliah}', [CourseController::class, 'show'])
    ->name('mata-kuliah.show');

// ==================== PENGGUNA ====================

// Menampilkan daftar pengguna.
Route::get('/pengguna', [UserController::class, 'index'])
    ->name('pengguna.index');

// Menampilkan form tambah pengguna.
Route::get('/pengguna/create', [UserController::class, 'create'])
    ->name('pengguna.create');

// Menyimpan pengguna baru.
Route::post('/pengguna', [UserController::class, 'store'])
    ->name('pengguna.store');

// Menampilkan form edit pengguna.
Route::get('/pengguna/{pengguna}/edit', [UserController::class, 'edit'])
    ->name('pengguna.edit');

// Memperbarui data pengguna.
Route::put('/pengguna/{pengguna}', [UserController::class, 'update'])
    ->name('pengguna.update');

// Menghapus pengguna.
Route::delete('/pengguna/{pengguna}', [UserController::class, 'destroy'])
    ->name('pengguna.destroy');

// Menampilkan detail satu pengguna.
Route::get('/pengguna/{pengguna}', [UserController::class, 'show'])
    ->name('pengguna.show');

// ==================== ERROR ====================

// Menampilkan halaman error 404.
Route::get('/error', function () {
    abort(404);
})->name('error');