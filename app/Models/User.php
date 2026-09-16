<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nim_nip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Courses taught by this user (as lecturer).
     */
    public function taughtCourses()
    {
        return $this->hasMany(Course::class, 'lecturer_id');
    }

    /**
     * Courses enrolled by this user (as student).
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    /**
     * Submissions made by this user.
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Grades given by this user (as grader).
     */
    public function gradesGiven()
    {
        return $this->hasMany(Grade::class, 'graded_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Ambil pengguna demo berdasarkan role aktif.
     */
    public static function getDemoUser(string $role): ?self
    {
        if ($role === 'mahasiswa') {
            return static::where('role', 'mahasiswa')
                ->where(function ($query) {
                    $query->where('name', 'like', '%Raihandy%')
                        ->orWhere('nim_nip', '10241064')
                        ->orWhere('email', '10241064@kampuslms.tes')
                        ->orWhere('email', 'mahasiswa@kampuslms.test');
                })
                ->first()
                ?? static::where('role', 'mahasiswa')->first();
        }

        $emails = [
            'admin' => 'admin@kampuslms.test',
            'dosen' => 'dosen@kampuslms.test',
        ];

        return static::where('email', $emails[$role] ?? null)->first()
            ?? static::where('role', $role)->first();
    }
}