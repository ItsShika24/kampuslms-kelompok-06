<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_open_login_and_is_redirected_there_from_dashboard(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke akun');
        $this->get('/dashboard')->assertRedirectToRoute('login');
    }

    public function test_login_authenticates_user_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'dosen@example.test',
            'password' => 'correct-password',
            'role' => 'dosen',
        ]);

        $this->post('/login', [
            'email' => 'dosen@example.test',
            'password' => 'correct-password',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_does_not_authenticate_or_disclose_email_existence(): void
    {
        User::factory()->create([
            'email' => 'known@example.test',
            'password' => 'correct-password',
        ]);

        $knownEmailResponse = $this->from('/login')->post('/login', [
            'email' => 'known@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
        $knownEmailError = session('errors')->first('email');

        $unknownEmailResponse = $this->from('/login')->post('/login', [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
        $unknownEmailError = session('errors')->first('email');

        $this->assertSame($knownEmailError, $unknownEmailError);
        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function test_only_admin_can_open_user_management(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get('/pengguna')
            ->assertForbidden();
    }

    public function test_student_submission_is_saved_for_the_authenticated_student(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->post(route('tugas.submit', $assignment), ['note' => 'Jawaban mahasiswa aktif'])
            ->assertRedirectToRoute('tugas.show', $assignment);

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'note' => 'Jawaban mahasiswa aktif',
        ]);
        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $otherStudent->id,
        ]);
    }
}
