<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Material $material): void {
            if ($material->file_path) {
                Storage::disk('local')->delete($material->file_path);
            }
        });
    }

    protected $fillable = [
        'course_id',
        'uploaded_by',
        'title',
        'description',
        'type',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
        'external_url',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
