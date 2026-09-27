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
        public string $postId
    ) {
        $this->onQueue('feed');
    }

    public function handle(): void
    {
        $post = Post::findOrFail($this->postId);
        $publishedAt = $post->created_at?->toDateTimeString() ?? now()->toDateTimeString();

        // 1. Insert feed item for the author
        FeedItem::insertOrIgnore([
            [
                'user_id' => $post->user_id,
                'post_id' => $post->id,
                'published_at' => $publishedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 2. Prepare chunk jobs for followers
        $jobs = [];
        Follow::query()
            ->where('following_id', $post->user_id)
            ->where('status', 'accepted')
            ->chunkById(1000, function ($follows) use (&$jobs, $post, $publishedAt) {
                $followerIds = $follows->pluck('follower_id')->all();

                if (!empty($followerIds)) {
                    $jobs[] = new FanOutPostChunkJob(
                        postId: $post->id,
                        followerIds: $followerIds,
                        publishedAt: $publishedAt
                    );
                }
            });

        // 3. Dispatch batched jobs if there are followers
        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->onQueue('feed')
                ->name("fanout-post-{$post->id}")
                ->allowFailures()
                ->dispatch();
        }
    }
}
