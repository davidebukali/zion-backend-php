<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::create([
            'name' => 'User A',
            'email' => 'user_a@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->userB = User::create([
            'name' => 'User B',
            'email' => 'user_b@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->userA->posts()->create([
            'content' => 'Sample post',
            'visibility' => 'public',
        ]);
    }

    public function test_user_can_get_paginated_notifications(): void
    {
        Notification::create([
            'user_id' => $this->userA->id,
            'actor_id' => $this->userB->id,
            'type' => 'post_liked',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
            'data' => ['post_id' => $this->post->id],
        ]);

        Notification::create([
            'user_id' => $this->userB->id,
            'actor_id' => $this->userA->id,
            'type' => 'user_followed',
            'notifiable_type' => $this->userA->getMorphClass(),
            'notifiable_id' => $this->userA->id,
            'data' => ['follower_id' => $this->userA->id],
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(route('api.notifications.index'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'actor_id',
                        'actor',
                        'type',
                        'notifiable_type',
                        'notifiable_id',
                        'data',
                        'read_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.user_id', $this->userA->id)
            ->assertJsonPath('data.0.actor.id', $this->userB->id);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->userA->id,
            'actor_id' => $this->userB->id,
            'type' => 'post_liked',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
            'data' => ['post_id' => $this->post->id],
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.notifications.read', $notification));

        $response->assertStatus(204);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->userB->id,
            'actor_id' => $this->userA->id,
            'type' => 'post_liked',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
            'data' => ['post_id' => $this->post->id],
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.notifications.read', $notification));

        $response->assertStatus(403);

        $notification->refresh();
        $this->assertNull($notification->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $notificationA1 = Notification::create([
            'user_id' => $this->userA->id,
            'actor_id' => $this->userB->id,
            'type' => 'post_liked',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
            'data' => ['post_id' => $this->post->id],
            'read_at' => null,
        ]);

        $notificationA2 = Notification::create([
            'user_id' => $this->userA->id,
            'actor_id' => $this->userB->id,
            'type' => 'user_followed',
            'notifiable_type' => $this->userB->getMorphClass(),
            'notifiable_id' => $this->userB->id,
            'data' => ['follower_id' => $this->userB->id],
            'read_at' => null,
        ]);

        $notificationB = Notification::create([
            'user_id' => $this->userB->id,
            'actor_id' => $this->userA->id,
            'type' => 'user_followed',
            'notifiable_type' => $this->userA->getMorphClass(),
            'notifiable_id' => $this->userA->id,
            'data' => ['follower_id' => $this->userA->id],
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.notifications.read-all'));

        $response->assertStatus(204);

        $notificationA1->refresh();
        $notificationA2->refresh();
        $notificationB->refresh();

        $this->assertNotNull($notificationA1->read_at);
        $this->assertNotNull($notificationA2->read_at);
        $this->assertNull($notificationB->read_at);
    }

    public function test_unauthenticated_user_cannot_access_notification_endpoints(): void
    {
        $notification = Notification::create([
            'user_id' => $this->userA->id,
            'actor_id' => $this->userB->id,
            'type' => 'post_liked',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
            'data' => ['post_id' => $this->post->id],
        ]);

        $this->getJson(route('api.notifications.index'))->assertStatus(401);
        $this->postJson(route('api.notifications.read', $notification))->assertStatus(401);
        $this->postJson(route('api.notifications.read-all'))->assertStatus(401);
    }
}
