<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Membuat 1 akun admin.
        User::factory()->create([
            'name' => 'Admin Kampus LMS',
            'email' => 'admin@kampuslms.test',
            'role' => 'admin',
            'nim_nip' => null,
        ]);

       // Membuat 3 akun dosen.
        $dosen = User::factory()->count(3)->create([
            'role' => 'dosen',
        ]);

        $dosen[0]->update([
            'email' => 'dosen@kampuslms.test',
        ]);

        // Membuat 30 akun mahasiswa.
        $mahasiswa = User::factory()->count(30)->create([
            'role' => 'mahasiswa',
        ]);

        $mahasiswa[0]->update([
            'email' => 'mahasiswa@kampuslms.test',
        ]);

        // Membuat 5 mata kuliah dan membagikan dosen sebagai pengampu.
        $courses = Course::factory()->count(5)->make();

        foreach ($courses as $index => $course) {
            $course->lecturer_id = $dosen[$index % $dosen->count()]->id;
            $course->save();
        }

        // Setiap mata kuliah memiliki minimal 15 mahasiswa.
        foreach ($courses as $course) {
            $course->students()->attach(
                $mahasiswa->random(15)->pluck('id')->toArray(),
                [
                    'enrolled_at' => now(),
                ]
            );
        }

        // Membuat 3 assignment untuk setiap mata kuliah.
        foreach ($courses as $course) {
            Assignment::factory()->count(3)->create([
                'course_id' => $course->id,
                'created_by' => $course->lecturer_id,
            ]);
        }

        // Mengambil seluruh assignment yang sudah dibuat.
        $assignments = Assignment::all();

        // Membuat minimal 100 submission dari mahasiswa.
        $submissionCount = 0;

        foreach ($assignments as $assignment) {
            $students = $assignment->course->students;

            foreach ($students as $student) {
                if ($submissionCount >= 100) {
                    break 2;
                }

                Submission::factory()->create([
                    'assignment_id' => $assignment->id,
                    'user_id' => $student->id,
                ]);

                $submissionCount++;
            }
        }

        // Mengambil seluruh submission untuk membuat data nilai.
        $submissions = Submission::all();

        // Memberikan nilai pada sekitar 60% submission.
        $gradedSubmissions = $submissions->random(
            (int) floor($submissions->count() * 0.6)
        );

        foreach ($gradedSubmissions as $submission) {
            Grade::create([
                'submission_id' => $submission->id,
                'graded_by' => $submission->assignment->created_by,
                'score' => fake()->numberBetween(60, 100),
                'feedback' => fake()->sentence(),
                'graded_at' => now(),
            ]);
        }
    }
}