<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'created_by',
        'title',
        'instructions',
        'due_at',
        'max_score',
        'allow_late',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_at'     => 'datetime',
            'max_score'  => 'decimal:2',
            'allow_late' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Semua nilai untuk tugas ini melalui submission.
     * Sesuai spec §4.3: hasManyThrough(Grade, Submission).
     */
    public function grades()
    {
        return $this->hasManyThrough(Grade::class, Submission::class);
    }
}