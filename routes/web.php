<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — KampusLMS (Laravel 12)
|--------------------------------------------------------------------------
| Modul Minggu 7: Autentikasi, Otorisasi, Policy & Session Regeneration.
| Menutup seluruh celah IDOR dan melindungi akses sesuai peran.
|--------------------------------------------------------------------------
*/

// ==================== HALAMAN PUBLIK & AUTENTIKASI ====================

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

// Rute Autentikasi Tamu (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Logout (Hanya pengguna terotentikasi)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


// ==================== ROUTE TERAUTENTIKASI (AUTH) ====================

Route::middleware('auth')->group(function () {

    // Dashboard Utama (Terproteksi Auth)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pusat Pengumpulan & Penilaian Tugas
    Route::get('/submissions', [SubmissionController::class, 'index'])
        ->name('submissions.index');
    Route::get('/pengumpulan-tugas', [SubmissionController::class, 'index'])
        ->name('pengumpulan.index');

    // Detail Submission & Penilaian (IDOR Mitigation: diperiksa di SubmissionController)
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])
        ->name('submissions.show');

    Route::post('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])
        ->middleware('role:admin,dosen')
        ->name('submissions.grade');


    // ----------------------------------------------------------------------
    // GRUP 1: ADMINISTRATOR (prefix: /admin, name: admin., middleware: role:admin)
    // ----------------------------------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // CRUD Pengguna (users) & Role
        Route::resource('users', UserController::class);

        // CRUD Seluruh Mata Kuliah
        Route::resource('courses', CourseController::class);
    });


    // ----------------------------------------------------------------------
    // GRUP 2: DOSEN (prefix: /dosen, name: dosen., middleware: role:dosen)
    // ----------------------------------------------------------------------
    Route::middleware('role:dosen')->prefix('dosen')->name('dosen.')->group(function () {
        // Dosen mengelola mata kuliah miliknya
        Route::resource('courses', CourseController::class)->only(['index', 'show', 'edit', 'update']);

        // Nested resource untuk Materi & Tugas dengan ->shallow() dan scopeBindings()
        Route::scopeBindings()->group(function () {
            Route::resource('courses.assignments', AssignmentController::class)->shallow();
            Route::resource('courses.materials', MaterialController::class)->shallow();

            // Mendukung eksplisit URL nested scoped (Skenario 4: scopeBindings)
            Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])
                ->name('courses.assignments.show.scoped');
            Route::get('/courses/{course}/materials/{material}', [MaterialController::class, 'show'])
                ->name('courses.materials.show.scoped');
        });
    });


    // ----------------------------------------------------------------------
    // GRUP 3: MAHASISWA (prefix: /mahasiswa, name: mahasiswa., middleware: role:mahasiswa)
    // ----------------------------------------------------------------------
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        // Mata kuliah yang diikuti
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

        // Scoped nested route
        Route::scopeBindings()->group(function () {
            Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])
                ->name('courses.assignments.show.scoped');
        });

        // Detail tugas & Pengumpulan
        Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');

        // Unduh materi mata kuliah
        Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    });

});


// ==================== ALIAS ROUTE KOMPATIBILITAS VIEW ====================
// Menjaga kompatibilitas penuh dengan template blade yang ada saat ini
// tanpa mengubah fungsionalitas keamanan (seluruh controller telah diproteksi abort_unless)

Route::middleware('auth')->group(function () {

    // Alias Mata Kuliah
    Route::get('/mata-kuliah', [CourseController::class, 'index'])->name('mata-kuliah.index');
    Route::get('/mata-kuliah/create', [CourseController::class, 'create'])->name('mata-kuliah.create');
    Route::post('/mata-kuliah', [CourseController::class, 'store'])->name('mata-kuliah.store');
    Route::get('/mata-kuliah/{course}', [CourseController::class, 'show'])->name('mata-kuliah.show');
    Route::get('/mata-kuliah/{course}/edit', [CourseController::class, 'edit'])->name('mata-kuliah.edit');
    Route::put('/mata-kuliah/{course}', [CourseController::class, 'update'])->name('mata-kuliah.update');
    Route::delete('/mata-kuliah/{course}', [CourseController::class, 'destroy'])->name('mata-kuliah.destroy');

    // Alias Tugas (Nested & Standalone)
    Route::get('/mata-kuliah/{course}/tugas/create', [AssignmentController::class, 'create'])->name('tugas.create');
    Route::post('/mata-kuliah/{course}/tugas', [AssignmentController::class, 'store'])->name('tugas.store');
    Route::get('/tugas/{assignment}', [AssignmentController::class, 'show'])->name('tugas.show');
    Route::get('/tugas/{assignment}/edit', [AssignmentController::class, 'edit'])->name('tugas.edit');
    Route::put('/tugas/{assignment}', [AssignmentController::class, 'update'])->name('tugas.update');
    Route::delete('/tugas/{assignment}', [AssignmentController::class, 'destroy'])->name('tugas.destroy');
    Route::post('/tugas/{assignment}/submit', [AssignmentController::class, 'submit'])->name('tugas.submit');

    // Scoped nested route (Skenario 4: scopeBindings)
    Route::scopeBindings()->group(function () {
        Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show']);
        Route::get('/mata-kuliah/{course}/tugas/{assignment}', [AssignmentController::class, 'show']);
    });

    // Alias Materi (Nested & Standalone)
    Route::get('/mata-kuliah/{course}/materi/create', [MaterialController::class, 'create'])->name('materi.create');
    Route::post('/mata-kuliah/{course}/materi', [MaterialController::class, 'store'])->name('materi.store');
    Route::get('/materi/{material}/download', [MaterialController::class, 'download'])->name('materi.download');
    Route::delete('/materi/{material}', [MaterialController::class, 'destroy'])->name('materi.destroy');

    // Alias Pengguna (Khusus Admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna.index');
        Route::get('/pengguna/create', [UserController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [UserController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{user}', [UserController::class, 'show'])->name('pengguna.show');
        Route::get('/pengguna/{user}/edit', [UserController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('pengguna.destroy');
    });

});