<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'budi@kampuslms.test',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email'    => 'budi@kampuslms.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_uniform_message_for_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'budi@kampuslms.test',
            'password' => 'password123',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email'    => 'budi@kampuslms.test',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak valid.']);
        $this->assertGuest();
    }

    public function test_login_fails_with_uniform_message_for_nonexistent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email'    => 'tidakada@kampuslms.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak valid.']);
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_user_can_request_password_reset_token(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'forgot-test@kampuslms.test'],
            ['name' => 'Forgot Tester', 'role' => 'mahasiswa', 'password' => 'secret']
        );

        $response = $this->post('/forgot-password', [
            'email' => 'forgot-test@kampuslms.test',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'forgot-test@kampuslms.test',
        ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'resetme@kampuslms.test',
            'password' => 'oldpassword',
        ]);

        $plainToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email'      => 'resetme@kampuslms.test',
            'token'      => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->post('/reset-password', [
            'token'                 => $plainToken,
            'email'                 => 'resetme@kampuslms.test',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        // Pastikan password baru bisa dipakai login
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
