<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Simulasi pergantian role (demo) — tidak dipakai di production.
Route::get('/set-role/{role}', [DashboardController::class, 'setRole'])->name('set-role');

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

// ==================== TUGAS ====================

// Dosen: form buat tugas baru pada MK tertentu.
Route::get('/mata-kuliah/{course}/tugas/create', [AssignmentController::class, 'create'])
    ->name('tugas.create');

// Dosen: simpan tugas baru.
Route::post('/mata-kuliah/{course}/tugas', [AssignmentController::class, 'store'])
    ->name('tugas.store');

// Semua role: lihat detail satu tugas.
Route::get('/tugas/{id}', [AssignmentController::class, 'show'])
    ->name('tugas.show');

// Dosen: form edit tugas.
Route::get('/tugas/{id}/edit', [AssignmentController::class, 'edit'])
    ->name('tugas.edit');

// Dosen: simpan perubahan tugas.
Route::put('/tugas/{id}', [AssignmentController::class, 'update'])
    ->name('tugas.update');

// Mahasiswa: kumpulkan jawaban tugas.
Route::post('/tugas/{id}/submit', [AssignmentController::class, 'submit'])
    ->name('tugas.submit');

// ==================== MATERI ====================

// Dosen: form tambah materi.
Route::get('/mata-kuliah/{course}/materi/create', [MaterialController::class, 'create'])
    ->name('materi.create');

// Dosen: simpan materi baru.
Route::post('/mata-kuliah/{course}/materi', [MaterialController::class, 'store'])
    ->name('materi.store');

// Semua role: download/buka materi.
Route::get('/materi/{id}/download', [MaterialController::class, 'download'])
    ->name('materi.download');

// Dosen: hapus materi.
Route::delete('/materi/{id}', [MaterialController::class, 'destroy'])
    ->name('materi.destroy');

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