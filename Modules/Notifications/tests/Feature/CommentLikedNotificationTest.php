<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Events\CommentLiked;
use Modules\Notifications\Actions\CreateNotification;
use Modules\Notifications\Listeners\SendCommentLikedNotification;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class CommentLikedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $commentAuthor;
    private User $liker;
    private Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commentAuthor = User::create([
            'name' => 'Comment Author',
            'email' => 'comment_author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker = User::create([
            'name' => 'Liker User',
            'email' => 'liker@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $this->commentAuthor->posts()->create([
            'content' => 'Post Content',
            'visibility' => 'public',
        ]);

        $this->comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $this->commentAuthor->id,
            'content' => 'Great post!',
        ]);
    }

    public function test_liking_comment_creates_notification_for_author(): void
    {
        $listener = app(SendCommentLikedNotification::class);
        $event = new CommentLiked($this->comment, $this->liker);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->commentAuthor->id,
            'actor_id' => $this->liker->id,
            'type' => 'comment_liked',
            'notifiable_type' => $this->comment->getMorphClass(),
            'notifiable_id' => $this->comment->id,
        ]);
    }

    public function test_liking_own_comment_does_not_create_notification(): void
    {
        $listener = app(SendCommentLikedNotification::class);
        $event = new CommentLiked($this->comment, $this->commentAuthor);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->commentAuthor->id,
            'actor_id' => $this->commentAuthor->id,
            'type' => 'comment_liked',
        ]);
    }
}
