<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notifications\Models\Notification;
use Modules\SocialGraph\Events\UserUnfollowed;

class RemoveFollowNotificationOnUnfollow implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(UserUnfollowed $event): void
    {
        Notification::query()
            ->where('user_id', $event->unfollowedUserId)
            ->whereIn('group_key', [
                'user_followed:'.$event->followerId,
                'follow_requested:'.$event->followerId,
            ])
            ->delete();
    }
}
