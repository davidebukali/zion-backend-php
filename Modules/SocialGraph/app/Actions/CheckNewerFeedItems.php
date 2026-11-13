<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\FeedItem;

class CheckNewerFeedItems
{
    /**
     * Count newer feed items for a user after a given feed item ID.
     */
    public function __invoke(User $user, int $afterId): int
    {
        return FeedItem::query()
            ->where('user_id', $user->id)
            ->where('id', '>', $afterId)
            ->whereHas('post')
            ->count();
    }
}
