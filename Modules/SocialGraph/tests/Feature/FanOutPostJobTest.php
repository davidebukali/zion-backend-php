<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Jobs\FanOutPostChunkJob;
use Modules\SocialGraph\Jobs\FanOutPostJob;
use Modules\SocialGraph\Models\Follow;
use Tests\TestCase;

class FanOutPostJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_fan_out_creates_author_feed_item_and_dispatches_batches(): void
    {
        Bus::fake();

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $follower1 = User::create([
            'name' => 'Follower 1',
            'email' => 'follower1@example.com',
            'password' => bcrypt('password'),
        ]);

        $follower2 = User::create([
            'name' => 'Follower 2',
            'email' => 'follower2@example.com',
            'password' => bcrypt('password'),
        ]);

        $pendingFollower = User::create([
            'name' => 'Pending Follower',
            'email' => 'pending@example.com',
            'password' => bcrypt('password'),
        ]);

        // Accepted follows
        Follow::create([
            'follower_id' => $follower1->id,
            'following_id' => $author->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        Follow::create([
            'follower_id' => $follower2->id,
            'following_id' => $author->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        // Pending follow (should not be included)
        Follow::create([
            'follower_id' => $pendingFollower->id,
            'following_id' => $author->id,
            'status' => FollowStatus::PENDING,
        ]);

        $post = $author->posts()->create([
            'content' => 'Hello World Post',
            'visibility' => 'public',
        ]);

        $job = new FanOutPostJob($post->id);
        $job->handle();

        // Check author has feed item
        $this->assertDatabaseHas('feed_items', [
            'user_id' => $author->id,
            'post_id' => $post->id,
        ]);

        // Check batch was dispatched with chunk job
        Bus::assertBatched(function ($batch) use ($post, $follower1, $follower2, $pendingFollower) {
            $chunkJob = $batch->jobs->first();
            return $chunkJob instanceof FanOutPostChunkJob
                && $chunkJob->postId === $post->id
                && in_array($follower1->id, $chunkJob->followerIds)
                && in_array($follower2->id, $chunkJob->followerIds)
                && !in_array($pendingFollower->id, $chunkJob->followerIds);
        });
    }

    public function test_chunk_job_bulk_inserts_feed_items(): void
    {
        $author = User::create([
            'name' => 'Author User',
            'email' => 'author2@example.com',
            'password' => bcrypt('password'),
        ]);

        $follower = User::create([
            'name' => 'Follower',
            'email' => 'follower3@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $author->posts()->create([
            'content' => 'Bulk Insert Test Post',
            'visibility' => 'public',
        ]);

        $chunkJob = new FanOutPostChunkJob(
            postId: $post->id,
            followerIds: [$follower->id],
            publishedAt: now()->toDateTimeString()
        );

        $chunkJob->handle();

        $this->assertDatabaseHas('feed_items', [
            'user_id' => $follower->id,
            'post_id' => $post->id,
        ]);
    }
}
