<?php

namespace Modules\SocialGraph\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Models\FeedItem;

class BackfillFollowerFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $followerId,
        public readonly string $followingId
    ) {
        $this->onQueue('feed');
    }

    public function handle(): void
    {
        $posts = Post::query()
            ->where('user_id', $this->followingId)
            ->whereIn('visibility', [
                PostVisibility::PUBLIC->value,
                PostVisibility::FOLLOWERS->value,
            ])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'created_at']);

        if ($posts->isEmpty()) {
            return;
        }

        $now = now();
        $records = $posts->map(function ($post) use ($now) {
            return [
                'user_id' => $this->followerId,
                'post_id' => $post->id,
                'published_at' => $post->created_at?->toDateTimeString() ?? $now->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        FeedItem::insertOrIgnore($records);
    }
}
