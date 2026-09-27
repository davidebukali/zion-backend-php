<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Events\UserUnfollowed;
use Modules\SocialGraph\Jobs\RemoveUnfollowedUserPostsFromFeedJob;
use Modules\SocialGraph\Models\FeedItem;
use Modules\SocialGraph\Models\Follow;
use Tests\TestCase;

class UnfollowFeedCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_unfollowing_dispatches_user_unfollowed_event(): void
    {
        Event::fake([UserUnfollowed::class]);

        $david = User::create([
            'name' => 'David',
            'email' => 'david@example.com',
            'password' => bcrypt('password'),
        ]);

        $alice = User::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
        ]);

        Follow::create([
            'follower_id' => $david->id,
            'following_id' => $alice->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        Sanctum::actingAs($david);

        $response = $this->deleteJson(route('api.users.unfollow', $alice));

        $response->assertStatus(200);

        Event::assertDispatched(UserUnfollowed::class, function ($event) use ($david, $alice) {
            return $event->followerId === $david->id && $event->unfollowedUserId === $alice->id;
        });
    }

    public function test_cleanup_job_removes_unfollowed_user_posts_from_feed(): void
    {
        $david = User::create([
            'name' => 'David',
            'email' => 'david2@example.com',
            'password' => bcrypt('password'),
        ]);

        $alice = User::create([
            'name' => 'Alice',
            'email' => 'alice2@example.com',
            'password' => bcrypt('password'),
        ]);

        $bob = User::create([
            'name' => 'Bob',
            'email' => 'bob2@example.com',
            'password' => bcrypt('password'),
        ]);

        // Alice creates 2 posts
        $alicePost1 = $alice->posts()->create(['content' => 'Alice Post 1', 'visibility' => 'public']);
        $alicePost2 = $alice->posts()->create(['content' => 'Alice Post 2', 'visibility' => 'public']);

        // Bob creates 1 post
        $bobPost = $bob->posts()->create(['content' => 'Bob Post 1', 'visibility' => 'public']);

        // David creates 1 post
        $davidPost = $david->posts()->create(['content' => 'David Post 1', 'visibility' => 'public']);

        // Populate David's feed
        FeedItem::create(['user_id' => $david->id, 'post_id' => $alicePost1->id, 'published_at' => now()]);
        FeedItem::create(['user_id' => $david->id, 'post_id' => $alicePost2->id, 'published_at' => now()]);
        FeedItem::create(['user_id' => $david->id, 'post_id' => $bobPost->id, 'published_at' => now()]);
        FeedItem::create(['user_id' => $david->id, 'post_id' => $davidPost->id, 'published_at' => now()]);

        // Alice's own feed
        FeedItem::create(['user_id' => $alice->id, 'post_id' => $alicePost1->id, 'published_at' => now()]);

        // Execute cleanup job on feed queue
        $job = new RemoveUnfollowedUserPostsFromFeedJob($david->id, $alice->id);
        $job->handle();

        // Alice's posts must be removed from David's feed
        $this->assertDatabaseMissing('feed_items', [
            'user_id' => $david->id,
            'post_id' => $alicePost1->id,
        ]);
        $this->assertDatabaseMissing('feed_items', [
            'user_id' => $david->id,
            'post_id' => $alicePost2->id,
        ]);

        // Bob's and David's posts must remain in David's feed
        $this->assertDatabaseHas('feed_items', [
            'user_id' => $david->id,
            'post_id' => $bobPost->id,
        ]);
        $this->assertDatabaseHas('feed_items', [
            'user_id' => $david->id,
            'post_id' => $davidPost->id,
        ]);

        // Alice's own feed item must remain intact
        $this->assertDatabaseHas('feed_items', [
            'user_id' => $alice->id,
            'post_id' => $alicePost1->id,
        ]);
    }
}
