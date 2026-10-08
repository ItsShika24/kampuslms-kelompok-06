<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    /**
     * Tampilkan daftar mata kuliah sesuai peran pengguna (dosen/mahasiswa/admin).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        if ($user->role === 'dosen') {
            $query = Course::where('lecturer_id', $user->id);
        } elseif ($user->role === 'mahasiswa') {
            $query = $user->courses();
        } else {
            $query = Course::query();
        }

        $courses = $query->with('lecturer')
            ->withCount(['materials', 'assignments'])
            ->orderBy('id')
            ->paginate(15);

        return CourseResource::collection($courses);
    }

    /**
     * Tampilkan detail mata kuliah berserta jumlah materi & tugas.
     */
    public function show(Request $request, Course $course): CourseResource
    {
        $this->authorizeCourseAccess($request->user(), $course);

        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }

    /**
     * Tampilkan daftar materi mata kuliah.
     */
    public function materials(Request $request, Course $course): AnonymousResourceCollection
    {
        $this->authorizeCourseAccess($request->user(), $course);

        $materials = $course->materials()
            ->with('uploader')
            ->orderBy('week_number')
            ->latest('id')
            ->get();

        return MaterialResource::collection($materials);
    }

    /**
     * Tampilkan daftar tugas mata kuliah dengan filter status & pagination.
     */
    public function assignments(Request $request, Course $course): AnonymousResourceCollection
    {
        $user = $request->user();
        $this->authorizeCourseAccess($user, $course);

        $query = $course->assignments()
            ->with('creator')
            ->withCount('submissions');

        if ($user->role === 'mahasiswa') {
            // Mahasiswa hanya boleh melihat tugas yang sudah dipublikasi/aktif/closed (bukan draft)
            $query->whereIn('status', ['published', 'active', 'closed']);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->latest('due_at')->paginate(15);

        return AssignmentResource::collection($assignments);
    }

    /**
     * Validasi hak akses pengguna terhadap mata kuliah.
     */
    private function authorizeCourseAccess(User $user, Course $course): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen' && $course->lecturer_id === $user->id) {
            return;
        }

        if ($user->role === 'mahasiswa' && $course->students()->where('users.id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
    }
}
