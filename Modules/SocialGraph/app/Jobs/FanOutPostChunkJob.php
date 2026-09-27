<?php

namespace Modules\SocialGraph\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\SocialGraph\Models\FeedItem;

class FanOutPostChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $postId
     * @param array<string> $followerIds
     * @param string $publishedAt
     */
    public function __construct(
        public string $postId,
        public array $followerIds,
        public string $publishedAt
    ) {
        $this->onQueue('feed');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        if (empty($this->followerIds)) {
            return;
        }

        $now = now();
        $records = array_map(function (string $followerId) use ($now) {
            return [
                'user_id' => $followerId,
                'post_id' => $this->postId,
                'published_at' => $this->publishedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $this->followerIds);

        FeedItem::insertOrIgnore($records);
    }
}
