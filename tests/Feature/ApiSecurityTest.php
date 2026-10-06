<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_endpoint_requires_sanctum_authentication(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_user_resources_never_expose_password_or_remember_token(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_user_show_forbids_reading_another_users_account(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/users/'.$otherStudent->id)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Forbidden.']);
    }

    public function test_course_store_returns_201_and_destroy_returns_empty_204(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        Sanctum::actingAs($lecturer);

        $response = $this->postJson('/api/v1/courses', [
            'code' => 'API-101',
            'name' => 'API Fundamentals',
            'description' => 'API response contract test',
            'sks' => 3,
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ])->assertCreated();

        $courseId = $response->json('data.id');

        $this->deleteJson('/api/v1/courses/'.$courseId)
            ->assertNoContent()
            ->assertContent('');
    }

    public function test_login_uses_the_same_error_for_unknown_email_and_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'known@example.test',
            'password' => 'correct-password',
        ]);

        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.test',
            'password' => 'incorrect-password',
        ])->assertUnprocessable();

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'known@example.test',
            'password' => 'incorrect-password',
        ])->assertUnprocessable();

        $this->assertSame($unknownEmail->json('errors.email'), $wrongPassword->json('errors.email'));
        $this->assertSame(['Email atau kata sandi tidak sesuai.'], $unknownEmail->json('errors.email'));
    }

    public function test_successful_login_returns_token_and_safe_user_resource(): void
    {
        User::factory()->create([
            'email' => 'api-user@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.test',
            'password' => 'correct-password',
        ])->assertOk();

        $this->assertNotEmpty($response->json('token'));
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('user'));
    }

    public function test_login_is_throttled_after_five_attempts_per_minute(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'unknown@example.test',
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_course_collection_eager_loads_lecturers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturers = User::factory()->count(3)->create(['role' => 'dosen']);

        for ($index = 1; $index <= 15; $index++) {
            Course::create([
                'code' => sprintf('API-%03d', $index),
                'name' => 'Eager loading test '.$index,
                'description' => null,
                'sks' => 3,
                'lecturer_id' => $lecturers[($index - 1) % $lecturers->count()]->id,
                'status' => 'active',
            ]);
        }

        Sanctum::actingAs($admin);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/v1/courses')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $lecturerQueries = array_filter($queries, function (array $query): bool {
            return str_contains(strtolower($query['query']), 'users')
                && preg_match('/\bin\s*\(/i', $query['query']) === 1;
        });

        $this->assertCount(15, $response->json('data'));
        $this->assertArrayNotHasKey('password', $response->json('data.0.lecturer'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data.0.lecturer'));
        $this->assertCount(1, $lecturerQueries);
    }
}