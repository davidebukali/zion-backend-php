<?php

namespace Modules\SocialGraph\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\SocialGraph\Models\FeedItem;

class RemoveUnfollowedUserPostsFromFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $followerId,
        public readonly string $unfollowedUserId
    ) {
        $this->onQueue('feed');
    }

    public function handle(): void
    {
        FeedItem::where('user_id', $this->followerId)
            ->whereIn('post_id', function ($query) {
                $query->select('id')
                    ->from('posts')
                    ->where('user_id', $this->unfollowedUserId);
            })
            ->delete();
    }
}
