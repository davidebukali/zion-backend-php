<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Events\FollowRequestAccepted;
use Modules\SocialGraph\Jobs\BackfillFollowerFeedJob;
use Modules\SocialGraph\Models\FeedItem;
use Modules\SocialGraph\Models\Follow;
use Tests\TestCase;

class BackfillFollowerFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $david;
    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->david = User::create([
            'name' => 'David',
            'email' => 'david_backfill@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->alice = User::create([
            'name' => 'Alice',
            'email' => 'alice_backfill@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_accepting_follow_request_dispatches_event(): void
    {
        Event::fake([FollowRequestAccepted::class]);

        $follow = Follow::create([
            'follower_id' => $this->david->id,
            'following_id' => $this->alice->id,
            'status' => FollowStatus::PENDING,
        ]);

        Sanctum::actingAs($this->alice);

        $response = $this->postJson(route('api.follows.accept', $follow));

        $response->assertStatus(200);

        Event::assertDispatched(FollowRequestAccepted::class, function ($event) {
            return $event->followerId === $this->david->id
                && $event->followingId === $this->alice->id;
        });
    }

    public function test_backfill_inserts_latest_20_posts_and_excludes_private_posts(): void
    {
        // Alice creates 25 posts (23 public, 2 private)
        for ($i = 1; $i <= 23; $i++) {
            $this->alice->posts()->create([
                'content' => "Public Post {$i}",
                'visibility' => PostVisibility::PUBLIC->value,
                'created_at' => now()->subMinutes(100 - $i),
            ]);
        }

        $privatePost1 = $this->alice->posts()->create([
            'content' => 'Private Post 1',
            'visibility' => PostVisibility::PRIVATE->value,
            'created_at' => now()->subMinutes(5),
        ]);

        $privatePost2 = $this->alice->posts()->create([
            'content' => 'Private Post 2',
            'visibility' => PostVisibility::PRIVATE->value,
            'created_at' => now()->subMinutes(2),
        ]);

        // Run backfill job directly
        $job = new BackfillFollowerFeedJob($this->david->id, $this->alice->id);
        $this->assertEquals('feed', $job->queue);
        $job->handle();

        // David's feed should have exactly 20 items (out of 23 public posts)
        $feedCount = FeedItem::where('user_id', $this->david->id)->count();
        $this->assertEquals(20, $feedCount);

        // Private posts must not be in David's feed
        $this->assertDatabaseMissing('feed_items', [
            'user_id' => $this->david->id,
            'post_id' => $privatePost1->id,
        ]);
        $this->assertDatabaseMissing('feed_items', [
            'user_id' => $this->david->id,
            'post_id' => $privatePost2->id,
        ]);
    }

    public function test_backfill_is_idempotent(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->alice->posts()->create([
                'content' => "Public Post {$i}",
                'visibility' => PostVisibility::PUBLIC->value,
            ]);
        }

        $job = new BackfillFollowerFeedJob($this->david->id, $this->alice->id);
        $job->handle();
        $job->handle(); // Run again

        $feedCount = FeedItem::where('user_id', $this->david->id)->count();
        $this->assertEquals(5, $feedCount);
    }
}
