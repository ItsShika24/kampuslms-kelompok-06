<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Semua role terautentikasi dapat melihat daftar dan detail.
    Route::get('/mata-kuliah', [CourseController::class, 'index'])->name('mata-kuliah.index');
    Route::get('/mata-kuliah/{course}', [CourseController::class, 'show'])->name('mata-kuliah.show');

    // Admin dan dosen dapat mengelola mata kuliah.
    Route::middleware('role:admin,dosen')->group(function () {
        Route::get('/mata-kuliah/create', [CourseController::class, 'create'])->name('mata-kuliah.create');
        Route::post('/mata-kuliah', [CourseController::class, 'store'])->name('mata-kuliah.store');
        Route::get('/mata-kuliah/{course}/edit', [CourseController::class, 'edit'])->name('mata-kuliah.edit');
        Route::put('/mata-kuliah/{course}', [CourseController::class, 'update'])->name('mata-kuliah.update');
        Route::delete('/mata-kuliah/{course}', [CourseController::class, 'destroy'])->name('mata-kuliah.destroy');
    });

    // Dosen dan admin mengelola tugas; mahasiswa hanya mengumpulkan jawaban.
    Route::middleware('role:admin,dosen')->group(function () {
        Route::get('/mata-kuliah/{course}/tugas/create', [AssignmentController::class, 'create'])->name('tugas.create');
        Route::post('/mata-kuliah/{course}/tugas', [AssignmentController::class, 'store'])->name('tugas.store');
        Route::get('/tugas/{assignment}/edit', [AssignmentController::class, 'edit'])->name('tugas.edit');
        Route::put('/tugas/{assignment}', [AssignmentController::class, 'update'])->name('tugas.update');
    });
    Route::get('/tugas/{assignment}', [AssignmentController::class, 'show'])->name('tugas.show');
    Route::post('/tugas/{assignment}/submit', [AssignmentController::class, 'submit'])
        ->middleware('role:mahasiswa')
        ->name('tugas.submit');

    // Semua role dapat membuka materi; hanya admin/dosen yang mengelolanya.
    Route::middleware('role:admin,dosen')->group(function () {
        Route::get('/mata-kuliah/{course}/materi/create', [MaterialController::class, 'create'])->name('materi.create');
        Route::post('/mata-kuliah/{course}/materi', [MaterialController::class, 'store'])->name('materi.store');
        Route::delete('/materi/{material}', [MaterialController::class, 'destroy'])->name('materi.destroy');
    });
    Route::get('/materi/{material}/download', [MaterialController::class, 'download'])->name('materi.download');

    // Hanya admin yang dapat mengelola akun.
    Route::middleware('role:admin')->group(function () {
        Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna.index');
        Route::get('/pengguna/create', [UserController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [UserController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{user}/edit', [UserController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('pengguna.destroy');
        Route::get('/pengguna/{user}', [UserController::class, 'show'])->name('pengguna.show');
    });
});

// ==================== ERROR ====================

// Menampilkan halaman error 404.
Route::get('/error', function () {
    abort(404);
})->name('error');