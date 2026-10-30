<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MINGGU 11 — Optimisasi Performa Database (Tika Mila Wahyuni)
     * Menambahkan indeks tertarget pada kolom yang sering difilter, diurutkan, dan di-polling.
     */
    public function up(): void
    {
        // 1. Indeks pada tabel `courses`
        Schema::table('courses', function (Blueprint $table) {
            // Menguntungkan query filter status mata kuliah aktif/arsip:
            // Contoh: Course::where('status', 'active') dan filter di CourseController@index
            $table->index('status', 'idx_courses_status');
        });

        // 2. Indeks pada tabel `assignments`
        Schema::table('assignments', function (Blueprint $table) {
            // Menguntungkan query filter status tugas dan pengelompokan per kelas:
            // Contoh: $course->assignments()->where('status', 'active') di DashboardController
            // dan Assignment::whereIn('status', ['published', 'active']) untuk mahasiswa
            $table->index('status', 'idx_assignments_status');
            $table->index(['course_id', 'status'], 'idx_assignments_course_status');
        });

        // 3. Indeks pada tabel `submissions`
        Schema::table('submissions', function (Blueprint $table) {
            // Menguntungkan query pencarian submission milik mahasiswa:
            // Contoh: Submission::where('user_id', $user->id) di SubmissionController@index.
            // CATATAN: Indeks komposit bawaan adalah (assignment_id, user_id),
            // sehingga pencarian berdasarkan user_id saja memerlukan full table scan tanpa indeks ini.
            $table->index('user_id', 'idx_submissions_user_id');

            // Menguntungkan pengurutan riwayat pengumpulan tugas terbaru:
            // Contoh: Submission::latest('submitted_at') pada Pusat Pengumpulan & Dashboard
            $table->index('submitted_at', 'idx_submissions_submitted_at');
        });

        // 4. Indeks pada tabel `notifications`
        Schema::table('notifications', function (Blueprint $table) {
            // Menguntungkan query auto-polling lonceng notifikasi setiap 30 detik:
            // Contoh: $user->unreadNotifications()->count()
            // SQL: WHERE notifiable_type = ? AND notifiable_id = ? AND read_at IS NULL
            // Indeks komposit ini mengeliminasi pemindaian seluruh notifikasi yang sudah terbaca.
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_unread_polling');
        });
    }

    /**
     * Reversible down() — benar-benar mengembalikan keadaan database (Spesifikasi §4.2).
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_status');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_status');
            $table->dropIndex('idx_assignments_course_status');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_user_id');
            $table->dropIndex('idx_submissions_submitted_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_unread_polling');
        });
    }
};
