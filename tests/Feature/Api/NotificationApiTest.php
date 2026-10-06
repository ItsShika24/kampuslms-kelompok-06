<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_view_own_notifications(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\AssignmentCreated',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => ['message' => 'Tugas baru telah ditambahkan'],
            'read_at'         => null,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.data.message', 'Tugas baru telah ditambahkan');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = Notification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\AssignmentCreated',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => ['message' => 'Pengumuman nilai tugas'],
            'read_at'         => null,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_read_other_users_notification(): void
    {
        $userA = User::factory()->create(['nim_nip' => '99999901']);
        $userB = User::factory()->create(['nim_nip' => '99999902']);

        $notificationB = Notification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\AssignmentCreated',
            'notifiable_type' => User::class,
            'notifiable_id'   => $userB->id,
            'data'            => ['message' => 'Pesan privat'],
            'read_at'         => null,
        ]);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->postJson("/api/v1/notifications/{$notificationB->id}/read");

        $response->assertStatus(404);
    }
}
