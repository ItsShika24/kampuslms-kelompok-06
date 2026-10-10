<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — KampusLMS (v1)
|--------------------------------------------------------------------------
|
| Kontrak API sesuai Bagian 5 Spesifikasi Proyek KampusLMS.
| Autentikasi: Laravel Sanctum (Bearer Token).
| Rate Limiting: 60/menit umum, 5/menit untuk login.
|
*/

Route::prefix('v1')->group(function () {
    // 1. Autentikasi Publik (Rate Limit: 5 percobaan / menit)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // 2. Endpoint Terlindungi (auth:sanctum + Rate Limit: 60 permintaan / menit)
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Sesi & Profil
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Mata Kuliah
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('/courses/{course}/materials', [CourseController::class, 'materials']);
        Route::get('/courses/{course}/assignments', [CourseController::class, 'assignments']);

        // Tugas (Assignments)
        Route::post('/assignments', [AssignmentController::class, 'store']);
        Route::match(['put', 'patch'], '/assignments/{assignment}', [AssignmentController::class, 'update']);
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);

        // Pengumpulan Tugas (Submissions) & Penilaian
        Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions']);
        Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store']);
        Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'grade']);

        // Notifikasi
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);
    });
});
