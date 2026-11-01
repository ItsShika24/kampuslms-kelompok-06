<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'file_path',
        'original_name',
        'file_size',
        'note',
        'submitted_at',
        'is_late',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    /**
     * MINGGU 9: Otomatis menghapus berkas fisik di disk privat saat record dihapus.
     * MINGGU 11 (Shabrina): Pembersihan cache otomatis (Cache Invalidation) saat status submission berubah.
     */
    protected static function booted(): void
    {
        static::saved(function (Submission $submission) {
            Cache::forget("dashboard.stats.user.{$submission->user_id}");
            if ($submission->assignment) {
                Cache::forget("dashboard.stats.user.{$submission->assignment->created_by}");
            }
        });

        static::deleting(function (Submission $submission) {
            Cache::forget("dashboard.stats.user.{$submission->user_id}");
            if ($submission->assignment) {
                Cache::forget("dashboard.stats.user.{$submission->assignment->created_by}");
            }

            if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
                Storage::disk('local')->delete($submission->file_path);
            }
        });
    }

    /**
     * Mengembalikan ukuran berkas dalam format terbaca manusia (B, KB, MB).
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->file_size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Mahasiswa yang membuat submission ini.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias ke User untuk kompatibilitas.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade()
    {
        return $this->hasOne(Grade::class);
    }
}
