<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Auth\Models\User;
use Modules\Interactions\Actions\LikePosts;
use Modules\Interactions\Actions\UnlikePost;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class GroupedPostLikedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $liker1;

    private User $liker2;

    private User $liker3;

    private User $liker4;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker1 = User::create([
            'name' => 'Liker One',
            'email' => 'liker1@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker2 = User::create([
            'name' => 'Liker Two',
            'email' => 'liker2@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker3 = User::create([
            'name' => 'Liker Three',
            'email' => 'liker3@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker4 = User::create([
            'name' => 'Liker Four',
            'email' => 'liker4@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->author->posts()->create([
            'content' => 'Sample test post for grouped notifications',
            'visibility' => 'public',
        ]);
    }

    public function test_single_like_creates_grouped_notification_with_correct_payload(): void
    {
        $likeAction = app(LikePosts::class);
        $likeAction($this->liker1, $this->post);

        $notification = Notification::query()
            ->where('user_id', $this->author->id)
            ->where('group_key', 'post_liked:'.$this->post->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals($this->author->id, $notification->user_id);
        $this->assertEquals($this->liker1->id, $notification->actor_id);
        $this->assertEquals('post_liked', $notification->type);
        $this->assertEquals($this->post->getMorphClass(), $notification->notifiable_type);
        $this->assertEquals($this->post->id, $notification->notifiable_id);
        $this->assertNull($notification->read_at);

        $this->assertSame(1, $notification->data['actors_count']);
        $this->assertEquals([$this->liker1->id], $notification->data['actor_ids']);
        $this->assertEquals('post_liked', $notification->data['event']);
        $this->assertEquals($this->post->id, $notification->data['post_id']);
    }

    public function test_multiple_likes_update_existing_notification_without_creating_new_record(): void
    {
        $likeAction = app(LikePosts::class);

        $likeAction($this->liker1, $this->post);
        $likeAction($this->liker2, $this->post);

        $this->assertEquals(
            1,
            Notification::query()->where('user_id', $this->author->id)->count()
        );

        $notification = Notification::query()
            ->where('user_id', $this->author->id)
            ->where('group_key', 'post_liked:'.$this->post->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals($this->liker2->id, $notification->actor_id);
        $this->assertSame(2, $notification->data['actors_count']);
        $this->assertEquals([$this->liker2->id, $this->liker1->id], $notification->data['actor_ids']);
    }

    public function test_more_than_three_likes_keeps_latest_three_actor_ids(): void
    {
        $likeAction = app(LikePosts::class);

        $likeAction($this->liker1, $this->post);
        $likeAction($this->liker2, $this->post);
        $likeAction($this->liker3, $this->post);
        $likeAction($this->liker4, $this->post);

        $this->assertEquals(
            1,
            Notification::query()->where('user_id', $this->author->id)->count()
        );

        $notification = Notification::query()
            ->where('user_id', $this->author->id)
            ->where('group_key', 'post_liked:'.$this->post->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(4, $notification->data['actors_count']);
        $this->assertEquals(
            [$this->liker4->id, $this->liker3->id, $this->liker2->id],
            $notification->data['actor_ids']
        );
        $this->assertEquals($this->liker4->id, $notification->actor_id);
    }

    public function test_author_liking_own_post_is_excluded_from_notifications_and_count(): void
    {
        $likeAction = app(LikePosts::class);

        // Author likes own post
        $likeAction($this->author, $this->post);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->author->id,
            'group_key' => 'post_liked:'.$this->post->id,
        ]);

        // Another user likes the post
        $likeAction($this->liker1, $this->post);

        $notification = Notification::query()
            ->where('user_id', $this->author->id)
            ->where('group_key', 'post_liked:'.$this->post->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(1, $notification->data['actors_count']);
        $this->assertEquals([$this->liker1->id], $notification->data['actor_ids']);
    }

    public function test_new_like_after_notification_read_resets_read_at_and_bumps_created_at(): void
    {
        $likeAction = app(LikePosts::class);

        $likeAction($this->liker1, $this->post);

        $notification = Notification::query()
            ->where('user_id', $this->author->id)
            ->where('group_key', 'post_liked:'.$this->post->id)
            ->firstOrFail();

        $pastTime = Carbon::now()->subHours(2);
        $notification->update([
            'read_at' => $pastTime,
            'created_at' => $pastTime,
        ]);

        $this->assertNotNull($notification->fresh()->read_at);

        // A new like arrives
        $likeAction($this->liker2, $this->post);

        $updatedNotification = $notification->fresh();

        $this->assertNull($updatedNotification->read_at);
        $this->assertSame(2, $updatedNotification->data['actors_count']);
        $this->assertTrue($updatedNotification->created_at->greaterThan($pastTime));
    }

    public function test_unliking_decrements_count_and_preserves_read_status(): void
    {
        $likeAction = app(LikePosts::class);
        $unlikeAction = app(UnlikePost::class);

        $likeAction($this->liker1, $this->post);
        $likeAction($this->liker2, $this->post);

        $notification = Notification::query()
            ->where('user_id', $this->author->id)
            ->where('group_key', 'post_liked:'.$this->post->id)
            ->firstOrFail();

        // Mark as read
        $readTime = Carbon::now()->subHour();
        $notification->update(['read_at' => $readTime]);

        // Liker 2 unlikes
        $unlikeAction($this->liker2, $this->post);

        $refreshed = $notification->fresh();

        $this->assertNotNull($refreshed);
        $this->assertSame(1, $refreshed->data['actors_count']);
        $this->assertEquals([$this->liker1->id], $refreshed->data['actor_ids']);
        $this->assertEquals($this->liker1->id, $refreshed->actor_id);
        // read_at should remain unchanged, not reset to null
        $this->assertNotNull($refreshed->read_at);
    }

    public function test_unliking_all_likes_deletes_notification(): void
    {
        $likeAction = app(LikePosts::class);
        $unlikeAction = app(UnlikePost::class);

        $likeAction($this->liker1, $this->post);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->author->id,
            'group_key' => 'post_liked:'.$this->post->id,
        ]);

        $unlikeAction($this->liker1, $this->post);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->author->id,
            'group_key' => 'post_liked:'.$this->post->id,
        ]);
    }
}
