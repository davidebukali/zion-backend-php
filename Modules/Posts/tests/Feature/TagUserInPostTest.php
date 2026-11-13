<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class TagUserInPostTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $taggedUser;

    private User $blockedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Author Jane',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->author->id,
            'username' => 'author_jane',
            'display_name' => 'Author J',
        ]);

        $this->taggedUser = User::create([
            'name' => 'Tagged Tom',
            'email' => 'tagged@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->taggedUser->id,
            'username' => 'tagged_tom',
            'display_name' => 'Tom T',
        ]);

        $this->blockedUser = User::create([
            'name' => 'Blocked Bob',
            'email' => 'blocked_bob@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->blockedUser->id,
            'username' => 'blocked_bob',
            'display_name' => 'Bob B',
        ]);

        UserBlock::create([
            'blocker_id' => $this->author->id,
            'blocked_id' => $this->blockedUser->id,
        ]);
    }

    public function test_creating_post_with_tagged_user_creates_notification(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'Hello @tagged_tom check this out!',
            'tagged_user_ids' => [$this->taggedUser->id],
        ]);

        $response->assertStatus(201);
        $postId = $response->json('data.id');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->taggedUser->id,
            'actor_id' => $this->author->id,
            'type' => 'user_tagged',
            'notifiable_type' => 'post',
            'notifiable_id' => $postId,
        ]);

        // Tagged user can fetch notifications
        Sanctum::actingAs($this->taggedUser);
        $notificationResponse = $this->getJson(route('api.notifications.index'));

        $notificationResponse->assertStatus(200);
        $notification = $notificationResponse->json('data.0');

        $this->assertEquals('user_tagged', $notification['type']);
        $this->assertEquals($this->author->id, $notification['actor']['id']);
        $this->assertEquals('Author Jane', $notification['data']['actor_name']);
        $this->assertEquals($postId, $notification['data']['post_id']);
        $this->assertEquals('tagged you in a post', $notification['data']['details']);
    }

    public function test_tagging_blocked_user_silently_suppresses_notification(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'Hello @blocked_bob',
            'tagged_user_ids' => [$this->blockedUser->id],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->blockedUser->id,
            'actor_id' => $this->author->id,
            'type' => 'user_tagged',
        ]);
    }

    public function test_tagging_self_does_not_create_notification(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'Hello myself @author_jane',
            'tagged_user_ids' => [$this->author->id],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->author->id,
            'actor_id' => $this->author->id,
            'type' => 'user_tagged',
        ]);
    }
}
