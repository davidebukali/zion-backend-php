<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Events\NewFeedItemBroadcast;
use Modules\SocialGraph\Jobs\FanOutPostChunkJob;
use Modules\SocialGraph\Jobs\FanOutPostJob;
use Tests\TestCase;

class NewFeedItemBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $follower1;

    private User $follower2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->follower1 = User::create([
            'name' => 'Follower 1',
            'email' => 'follower1@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->follower2 = User::create([
            'name' => 'Follower 2',
            'email' => 'follower2@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_new_feed_item_broadcast_event_properties(): void
    {
        $event = new NewFeedItemBroadcast(
            recipientUserIds: [$this->follower1->id, $this->follower2->id],
            postId: 'test-post-id',
            type: 'post',
            actorId: $this->author->id,
            publishedAt: '2026-11-05 19:30:00'
        );

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertSame('private-users.'.$this->follower1->id, $channels[0]->name);
        $this->assertSame('private-users.'.$this->follower2->id, $channels[1]->name);

        $this->assertSame('feed.new_item', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue);

        $payload = $event->broadcastWith();
        $this->assertSame('test-post-id', $payload['post_id']);
        $this->assertSame('post', $payload['type']);
        $this->assertSame($this->author->id, $payload['actor_id']);
        $this->assertSame('2026-11-05 19:30:00', $payload['published_at']);
    }

    public function test_fan_out_chunk_job_dispatches_new_feed_item_broadcast(): void
    {
        Event::fake([NewFeedItemBroadcast::class]);

        $post = $this->author->posts()->create([
            'content' => 'Chunk test post',
            'visibility' => 'public',
        ]);

        $job = new FanOutPostChunkJob(
            postId: $post->id,
            followerIds: [$this->follower1->id, $this->follower2->id],
            publishedAt: now()->toDateTimeString(),
            type: 'post',
            actorId: $this->author->id
        );

        $job->handle();

        Event::assertDispatched(NewFeedItemBroadcast::class, function ($event) {
            return count($event->recipientUserIds) === 2
                && in_array($this->follower1->id, $event->recipientUserIds)
                && in_array($this->follower2->id, $event->recipientUserIds)
                && $event->actorId === $this->author->id;
        });
    }

    public function test_fan_out_post_job_dispatches_new_feed_item_broadcast_for_actor(): void
    {
        Event::fake([NewFeedItemBroadcast::class]);

        $post = $this->author->posts()->create([
            'content' => 'Actor test post',
            'visibility' => 'public',
        ]);

        $job = new FanOutPostJob(
            postId: $post->id,
            type: 'post',
            actorId: $this->author->id,
            publishedAt: now()->toDateTimeString()
        );

        $job->handle();

        Event::assertDispatched(NewFeedItemBroadcast::class, function ($event) {
            return count($event->recipientUserIds) === 1
                && $event->recipientUserIds[0] === $this->author->id
                && $event->actorId === $this->author->id;
        });
    }
}
