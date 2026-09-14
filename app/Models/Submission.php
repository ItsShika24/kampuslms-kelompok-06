<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
            'is_late'      => 'boolean',
        ];
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