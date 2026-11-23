<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_returns_token_and_user_without_sensitive_data(): void
    {
        $user = User::factory()->create([
            'email'    => 'testuser@kampuslms.test',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'       => 'testuser@kampuslms.test',
            'password'    => 'secret123',
            'device_name' => 'phpunit',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'nim_nip',
                ],
            ])
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['remember_token']);
    }

    public function test_login_fails_with_uniform_message_for_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'registered@kampuslms.test',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'registered@kampuslms.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
    }

    public function test_login_fails_with_uniform_message_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'doesnotexist@kampuslms.test',
            'password' => 'anypassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Berhasil logout.']);
    }

    public function test_me_returns_profile_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Tika Mahasiswa',
            'role' => 'mahasiswa',
        ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Tika Mahasiswa')
            ->assertJsonPath('data.role', 'mahasiswa');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
