<?php

namespace Modules\SocialGraph\Listeners;

use Modules\SocialGraph\Events\FollowRequestAccepted;
use Modules\SocialGraph\Events\UserFollowed;
use Modules\SocialGraph\Jobs\BackfillFollowerFeedJob;

class BackfillFollowerFeedListener
{
    /**
     * Handle the event.
     */
    public function handle(UserFollowed|FollowRequestAccepted $event): void
    {
        BackfillFollowerFeedJob::dispatch(
            $event->followerId,
            $event->followingId
        );
    }
}
