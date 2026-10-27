<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        Gate::authorize('viewAny', Grade::class);

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer'],
        ]);

        $relations = [
            'assignments' => fn (HasMany $query) => $query->orderBy('due_at'),
            'assignments.submissions' => function (HasMany $query) use ($user): void {
                $query->with(['student', 'grade']);

                if ($user->role === 'mahasiswa') {
                    $query->where('user_id', $user->id);
                }
            },
        ];

        if ($user->role !== 'mahasiswa') {
            $relations['students'] = fn (BelongsToMany $query) => $query->orderBy('name');
        }

        $courses = match ($user->role) {
            'admin' => Course::query()->with($relations),
            'dosen' => Course::query()
                ->where('lecturer_id', $user->id)
                ->with($relations),
            'mahasiswa' => Course::query()
                ->whereHas('students', fn (Builder $query) => $query->whereKey($user->id))
                ->with($relations),
        };

        if (! empty($validated['course_id'])) {
            $courses->whereKey($validated['course_id']);
        }

        return view('grades.index', [
            'courses' => $courses->orderBy('name')->get(),
            'role' => $user->role,
            'selectedCourseId' => $validated['course_id'] ?? null,
        ]);
    }
}
