<?php

namespace Modules\SocialGraph\Listeners;

use Modules\SocialGraph\Events\UserUnfollowed;
use Modules\SocialGraph\Jobs\RemoveUnfollowedUserPostsFromFeedJob;

class CleanUnfollowedUserFeedListener
{
    /**
     * Handle the event.
     */
    public function handle(UserUnfollowed $event): void
    {
        RemoveUnfollowedUserPostsFromFeedJob::dispatch(
            $event->followerId,
            $event->unfollowedUserId
        );
    }
}
