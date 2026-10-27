<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class NotificationAndGradeSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->where('code', 'SI101')->firstOrFail();
        $lecturer = $course->lecturer;
        $students = $course->students()
            ->where('users.role', 'mahasiswa')
            ->orderBy('users.id')
            ->take(2)
            ->get();

        if (! $lecturer || $students->count() < 2) {
            throw new RuntimeException('Seeder Week 10 memerlukan dosen dan minimal dua mahasiswa terdaftar di SI101.');
        }

        $assignments = collect([
            'Demo Week 10: Analisis Kebutuhan Sistem',
            'Demo Week 10: Rancangan Basis Data',
        ])->map(fn (string $title) => Assignment::firstOrCreate(
            ['course_id' => $course->id, 'title' => $title],
            [
                'created_by' => $lecturer->id,
                'instructions' => 'Tugas contoh untuk demonstrasi rekap nilai Week 10.',
                'due_at' => now()->addWeek(),
                'max_score' => 100,
                'allow_late' => false,
                'status' => 'active',
            ]
        ));

        foreach ($students as $studentIndex => $student) {
            foreach ($assignments as $assignmentIndex => $assignment) {
                $submission = Submission::firstOrCreate(
                    ['assignment_id' => $assignment->id, 'user_id' => $student->id],
                    [
                        'original_name' => 'submission-demo-week-10.pdf',
                        'file_size' => 1024,
                        'note' => 'Pengumpulan contoh untuk demonstrasi rekap nilai.',
                        'submitted_at' => now()->subDays(2),
                        'is_late' => false,
                    ]
                );

                Grade::updateOrCreate(
                    ['submission_id' => $submission->id],
                    [
                        'graded_by' => $lecturer->id,
                        'score' => 80 + (($studentIndex + $assignmentIndex) * 5),
                        'feedback' => 'Nilai contoh untuk demonstrasi. Periksa kembali kelengkapan analisis.',
                        'graded_at' => now()->subDay(),
                    ]
                );

                $readAt = ($studentIndex + $assignmentIndex) % 2 === 0 ? null : now()->subHours(2);

                $this->upsertNotification(
                    $student,
                    'App\\Notifications\\GradeGivenNotification',
                    $assignment,
                    $readAt
                );
            }

            $assignment = $assignments->first();
            $this->upsertNotification(
                $student,
                'App\\Notifications\\NewAssignmentNotification',
                $assignment,
                $studentIndex % 2 === 0 ? null : now()->subHours(3)
            );
        }
    }

    private function upsertNotification(
        User $student,
        string $type,
        Assignment $assignment,
        mixed $readAt
    ): void {
        $notification = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $student->id)
            ->where('type', $type)
            ->get()
            ->first(fn (Notification $candidate) => ($candidate->data['assignment_id'] ?? null) === $assignment->id);

        if (! $notification) {
            $notification = new Notification(['id' => (string) Str::uuid()]);
        }

        $notification->fill([
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
            'data' => [
                'assignment_id' => $assignment->id,
                'course_name' => $assignment->course()->value('name'),
                'title' => $assignment->title,
                'url' => route('tugas.show', $assignment, false),
            ],
            'read_at' => $readAt,
        ])->save();
    }
}
