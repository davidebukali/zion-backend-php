<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ShareType;
use Modules\Interactions\Events\PostShared;
use Modules\Notifications\Listeners\SendPostSharedNotification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostSharedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $postAuthor;
    private User $sharer;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postAuthor = User::create([
            'name' => 'Post Author',
            'email' => 'post_author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->sharer = User::create([
            'name' => 'Post Sharer',
            'email' => 'post_sharer@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->postAuthor->posts()->create([
            'content' => 'Sample Post for Sharing',
            'visibility' => 'public',
        ]);
    }

    public function test_sharing_post_creates_notification_for_author(): void
    {
        $listener = app(SendPostSharedNotification::class);
        $event = new PostShared($this->post, $this->sharer, ShareType::INTERNAL);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->sharer->id,
            'type' => 'post_shared',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
        ]);
    }

    public function test_sharing_own_post_does_not_create_notification(): void
    {
        $listener = app(SendPostSharedNotification::class);
        $event = new PostShared($this->post, $this->postAuthor, ShareType::INTERNAL);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->postAuthor->id,
            'type' => 'post_shared',
        ]);
    }
}
