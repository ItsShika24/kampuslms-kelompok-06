<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasFactory;

    /**
     * Pembersihan cache otomatis (Cache Invalidation) saat data mata kuliah berubah atau dihapus (docs/performa.md §6).
     */
    protected static function booted(): void
    {
        static::saved(function (Course $course) {
            if ($course->lecturer_id) {
                Cache::forget("dashboard.stats.user.{$course->lecturer_id}");
            }
            foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                Cache::forget("dashboard.stats.user.{$adminId}");
            }
            Cache::forget("course.{$course->id}.stats");
        });

        static::deleting(function (Course $course) {
            // MINGGU 13 Review Fix (Tanggapan Review Nova - Kelompok 05):
            // Bersihkan berkas fisik privat materi dan submission tugas di model event
            // untuk mencegah orphaned files jika mata kuliah dihapus di luar controller (Seeder/Artisan/API).
            $course->loadMissing(['materials', 'assignments.submissions']);

            foreach ($course->materials as $mat) {
                if ($mat->file_path && Storage::disk('local')->exists($mat->file_path)) {
                    Storage::disk('local')->delete($mat->file_path);
                }
            }

            foreach ($course->assignments as $asg) {
                foreach ($asg->submissions as $sub) {
                    if ($sub->file_path && Storage::disk('local')->exists($sub->file_path)) {
                        Storage::disk('local')->delete($sub->file_path);
                    }
                }
            }
        });

        static::deleted(function (Course $course) {
            if ($course->lecturer_id) {
                Cache::forget("dashboard.stats.user.{$course->lecturer_id}");
            }
            foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                Cache::forget("dashboard.stats.user.{$adminId}");
            }
            Cache::forget("course.{$course->id}.stats");
        });
    }

    protected $fillable = [
        'code',
        'name',
        'description',
        'sks',
        'lecturer_id',
        'status',
    ];

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}