<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportReason;
use Modules\Interactions\Events\PostReported;
use Modules\Notifications\Listeners\SendPostReportedNotification;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostReportedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $postAuthor;
    private User $reporter;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postAuthor = User::create([
            'name' => 'Post Author',
            'email' => 'author_reported@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->reporter = User::create([
            'name' => 'Post Reporter',
            'email' => 'reporter@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->postAuthor->posts()->create([
            'content' => 'Inappropriate Content Post',
            'visibility' => 'public',
        ]);
    }

    public function test_reporting_post_creates_notification_for_author(): void
    {
        $listener = app(SendPostReportedNotification::class);
        $event = new PostReported(
            post: $this->post,
            user: $this->reporter,
            reason: ReportReason::SPAM,
            description: 'This is spam content.'
        );

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->reporter->id,
            'type' => 'post_reported',
            'notifiable_type' => $this->post->getMorphClass(),
            'notifiable_id' => $this->post->id,
        ]);

        $notification = Notification::where('user_id', $this->postAuthor->id)->first();
        $this->assertEquals(ReportReason::SPAM->value, $notification->data['reason']);
        $this->assertEquals('This is spam content.', $notification->data['description']);
    }

    public function test_reporting_own_post_does_not_create_notification(): void
    {
        $listener = app(SendPostReportedNotification::class);
        $event = new PostReported(
            post: $this->post,
            user: $this->postAuthor,
            reason: ReportReason::SPAM,
            description: 'Self report.'
        );

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->postAuthor->id,
            'actor_id' => $this->postAuthor->id,
            'type' => 'post_reported',
        ]);
    }
}
