<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostLiked;
use Modules\Notifications\Listeners\SendPostLikedNotification;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostLikedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $postAuthor;
    private User $liker;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postAuthor = User::create([
            'name' => 'Post Author',
            'email' => 'post_author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker = User::create([
            'name' => 'Post Liker',
            'email' => 'post_liker@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->postAuthor->posts()->create([
            'content' => 'Sample Post for Liking',
            'visibility' => 'public',
        ]);
    }

    public function test_liking_post_creates_notification_for_author(): void
    {
        $listener = app(SendPostLikedNotification::class);
        $event = new PostLiked($this->post, $this->liker);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->liker->id,
            'type' => 'post_liked',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
        ]);
    }

    public function test_liking_own_post_does_not_create_notification(): void
    {
        $listener = app(SendPostLikedNotification::class);
        $event = new PostLiked($this->post, $this->postAuthor);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->postAuthor->id,
            'type' => 'post_liked',
        ]);
    }
}
