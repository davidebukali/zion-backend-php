<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostReposted;
use Modules\Notifications\Listeners\SendPostRepostedNotification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostRepostedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $postAuthor;
    private User $reposter;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postAuthor = User::create([
            'name' => 'Post Author',
            'email' => 'post_author_repost@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->reposter = User::create([
            'name' => 'Post Reposter',
            'email' => 'post_reposter@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->postAuthor->posts()->create([
            'content' => 'Sample Post for Reposting',
            'visibility' => 'public',
        ]);
    }

    public function test_reposting_post_creates_notification_for_author(): void
    {
        $listener = app(SendPostRepostedNotification::class);
        $event = new PostReposted($this->post, $this->reposter);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->reposter->id,
            'type' => 'post_reposted',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
        ]);
    }

    public function test_reposting_own_post_does_not_create_notification(): void
    {
        $listener = app(SendPostRepostedNotification::class);
        $event = new PostReposted($this->post, $this->postAuthor);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->postAuthor->id,
            'type' => 'post_reposted',
        ]);
    }
}

