<?php

namespace Tests\Feature;

use App\Http\Controllers\GradeController;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\NotificationAndGradeSeeder;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class NotificationAndQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_creation_dispatches_event_and_notifies_enrolled_students(): void
    {
        $this->requireNotificationFeature([
            'App\\Events\\AssignmentPublished',
            'App\\Notifications\\NewAssignmentNotification',
        ]);

        $lecturer = User::factory()->dosen()->create();
        $students = User::factory()->mahasiswa()->count(2)->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($students->modelKeys(), ['enrolled_at' => now()]);

        $this->actingAs($lecturer)->post(route('tugas.store', $course), [
            'title' => 'Tugas Week 10: Integrasi Event',
            'instructions' => 'Selesaikan tugas demonstrasi.',
            'due_at' => now()->addWeek()->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => false,
            'status' => 'active',
        ])->assertRedirect();

        $assignment = Assignment::query()
            ->where('course_id', $course->id)
            ->where('title', 'Tugas Week 10: Integrasi Event')
            ->firstOrFail();

        foreach ($students as $student) {
            $notification = Notification::query()
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $student->id)
                ->where('type', 'App\\Notifications\\NewAssignmentNotification')
                ->first();

            $this->assertNotNull($notification);
            $this->assertSame($assignment->id, $notification->data['assignment_id']);
        }
    }

    public function test_grading_submission_notifies_only_the_submission_owner(): void
    {
        $this->requireNotificationFeature([
            'App\\Events\\GradeSubmitted',
            'App\\Notifications\\GradeGivenNotification',
        ]);

        $lecturer = User::factory()->dosen()->create();
        $owner = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $owner->id,
        ]);
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $otherStudent->id,
        ]);

        $this->actingAs($lecturer)
            ->post(route('submissions.grade', $submission), [
                'score' => 87,
                'feedback' => 'Bagus, lengkapi analisis pada bagian kesimpulan.',
            ])
            ->assertRedirect();

        $ownerNotifications = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $owner->id)
            ->where('type', 'App\\Notifications\\GradeGivenNotification')
            ->get();
        $otherStudentNotifications = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $otherStudent->id)
            ->where('type', 'App\\Notifications\\GradeGivenNotification')
            ->count();

        $this->assertCount(1, $ownerNotifications);
        $this->assertSame(0, $otherStudentNotifications);
        $this->assertArrayNotHasKey('score', $ownerNotifications->first()->data);
        $this->assertArrayNotHasKey('feedback', $ownerNotifications->first()->data);
    }

    public function test_student_cannot_mark_other_students_notification_as_read(): void
    {
        $studentA = User::factory()->mahasiswa()->create();
        $studentB = User::factory()->mahasiswa()->create();
        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\GradeGivenNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $studentB->id,
            'data' => ['assignment_id' => 1, 'title' => 'Nilai tugas'],
            'read_at' => null,
        ]);

        Sanctum::actingAs($studentA, ['*']);

        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notification_payload_does_not_leak_sensitive_grade_data(): void
    {
        $course = Course::query()->where('code', 'SI101')->first();
        if (! $course) {
            $lecturer = User::factory()->dosen()->create();
            $course = Course::factory()->create([
                'code' => 'SI101',
                'lecturer_id' => $lecturer->id,
            ]);
        }

        $students = $course->students()->where('users.role', 'mahasiswa')->take(2)->get();
        if ($students->count() < 2) {
            $newStudents = User::factory()->mahasiswa()->count(2 - $students->count())->create();
            $course->students()->syncWithoutDetaching($newStudents->modelKeys());
            $students = $course->students()->where('users.role', 'mahasiswa')->take(2)->get();
        }

        $this->seed(NotificationAndGradeSeeder::class);

        $notifications = Notification::query()
            ->whereIn('notifiable_id', $students->modelKeys())
            ->get();

        $this->assertNotEmpty($notifications);
        $this->assertTrue($notifications->contains(fn (Notification $notification) => $notification->read_at === null));
        $this->assertTrue($notifications->contains(fn (Notification $notification) => $notification->read_at !== null));
        foreach ($notifications as $notification) {
            $this->assertArrayNotHasKey('score', $notification->data);
            $this->assertArrayNotHasKey('feedback', $notification->data);
        }

        $this->assertTrue(Grade::query()
            ->whereHas('submission.assignment', fn ($query) => $query->whereIn('title', [
                'Demo Week 10: Analisis Kebutuhan Sistem',
                'Demo Week 10: Rancangan Basis Data',
            ]))
            ->exists());

        $this->assertSame(4, Grade::query()
            ->whereHas('submission.assignment', fn ($query) => $query->whereIn('title', [
                'Demo Week 10: Analisis Kebutuhan Sistem',
                'Demo Week 10: Rancangan Basis Data',
            ]))
            ->count());
    }

    public function test_grades_recap_is_properly_scoped_by_role(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $unrelatedCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $course->students()->attach([$student->id, $otherStudent->id], ['enrolled_at' => now()]);
        $otherCourse->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $otherAssignment = Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'created_by' => $otherLecturer->id,
        ]);

        $ownSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $otherStudent->id,
        ]);
        $ownOtherCourseSubmission = Submission::factory()->create([
            'assignment_id' => $otherAssignment->id,
            'user_id' => $student->id,
        ]);
        Grade::factory()->create([
            'submission_id' => $ownSubmission->id,
            'graded_by' => $lecturer->id,
            'score' => 91,
        ]);

        View::shouldReceive('make')
            ->times(4)
            ->with('grades.index', Mockery::type('array'), [])
            ->andReturnUsing(function (string $name, array $data): ViewContract {
                $view = Mockery::mock(ViewContract::class);
                $view->shouldReceive('getData')->andReturn($data);

                return $view;
            });

        $lecturerCourses = $this->getRecapFor($lecturer)->getData()['courses'];
        $this->assertSame([$course->id], $lecturerCourses->modelKeys());

        $studentCourses = $this->getRecapFor($student)->getData()['courses'];
        $this->assertEqualsCanonicalizing(
            [$course->id, $otherCourse->id],
            $studentCourses->modelKeys()
        );
        $this->assertCount(
            0,
            $this->getRecapFor($student, ['course_id' => $unrelatedCourse->id])->getData()['courses']
        );

        $studentSubmissionIds = $studentCourses
            ->flatMap(fn (Course $recapCourse) => $recapCourse->assignments)
            ->flatMap(fn (Assignment $recapAssignment) => $recapAssignment->submissions)
            ->pluck('id')
            ->all();
        $this->assertEqualsCanonicalizing(
            [$ownSubmission->id, $ownOtherCourseSubmission->id],
            $studentSubmissionIds
        );

        $adminCourses = $this->getRecapFor(User::factory()->admin()->create())->getData()['courses'];
        $this->assertContains($course->id, $adminCourses->modelKeys());
        $this->assertContains($otherCourse->id, $adminCourses->modelKeys());
    }

    private function getRecapFor(User $user, array $query = [])
    {
        $this->actingAs($user);
        $request = Request::create('/grades', 'GET', $query);
        $request->setUserResolver(fn () => $user);

        return (new GradeController)->index($request);
    }

    private function requireNotificationFeature(array $classes): void
    {
        foreach ($classes as $class) {
            if (! class_exists($class)) {
                $this->markTestSkipped("Menunggu integrasi komponen Week 10: {$class}.");
            }
        }
    }
}
