<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Comments\Events\CommentCreated;
use Modules\Comments\Models\Comment;
use Modules\Notifications\Listeners\SendCommentCreatedNotification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class CommentCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $postAuthor;
    private User $commenter;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postAuthor = User::create([
            'name' => 'Post Author',
            'email' => 'post_author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->commenter = User::create([
            'name' => 'Commenter',
            'email' => 'commenter@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->postAuthor->posts()->create([
            'content' => 'Sample Post for Comments',
            'visibility' => 'public',
        ]);
    }

    public function test_creating_comment_creates_notification_for_post_author(): void
    {
        $comment = Comment::create([
            'user_id' => $this->commenter->id,
            'post_id' => $this->post->id,
            'content' => 'Great post!',
        ]);

        $listener = app(SendCommentCreatedNotification::class);
        $event = new CommentCreated($comment);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->commenter->id,
            'type' => 'post_commented',
            'notifiable_type' => $comment->getMorphClass(),
            'notifiable_id' => $comment->id,
        ]);
    }

    public function test_commenting_on_own_post_does_not_create_notification(): void
    {
        $comment = Comment::create([
            'user_id' => $this->postAuthor->id,
            'post_id' => $this->post->id,
            'content' => 'Commenting on my own post',
        ]);

        $listener = app(SendCommentCreatedNotification::class);
        $event = new CommentCreated($comment);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->postAuthor->id,
            'type' => 'post_commented',
        ]);
    }
}
