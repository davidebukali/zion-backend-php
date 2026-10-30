<?php

namespace Modules\SocialGraph\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Jobs\FanOutPostChunkJob;
use Modules\SocialGraph\Models\FeedItem;
use Modules\SocialGraph\Models\Follow;

class FanOutPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $postId,
        public string $type = 'post',
        public ?string $actorId = null,
        public ?string $publishedAt = null
    ) {
        $this->onQueue('feed');
    }

    public function handle(): void
    {
        $post = Post::withoutGlobalScopes()->findOrFail($this->postId);
        $actorId = $this->actorId ?? $post->user_id;
        $publishedAt = $this->publishedAt ?? ($post->created_at?->toDateTimeString() ?? now()->toDateTimeString());

        // 1. Insert feed item for the actor
        FeedItem::insertOrIgnore([
            [
                'user_id' => $actorId,
                'post_id' => $post->id,
                'type' => $this->type,
                'actor_id' => $actorId,
                'published_at' => $publishedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 2. Prepare chunk jobs for followers of the actor
        $jobs = [];
        $type = $this->type;
        Follow::query()
            ->where('following_id', $actorId)
            ->where('status', 'accepted')
            ->chunkById(1000, function ($follows) use (&$jobs, $post, $publishedAt, $type, $actorId) {
                $followerIds = $follows->pluck('follower_id')->all();

                if (!empty($followerIds)) {
                    $jobs[] = new FanOutPostChunkJob(
                        postId: $post->id,
                        followerIds: $followerIds,
                        publishedAt: $publishedAt,
                        type: $type,
                        actorId: $actorId
                    );
                }
            });

        // 3. Dispatch batched jobs if there are followers
        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->onQueue('feed')
                ->name("fanout-{$this->type}-{$post->id}-actor-{$actorId}")
                ->allowFailures()
                ->dispatch();
        }
    }
}
