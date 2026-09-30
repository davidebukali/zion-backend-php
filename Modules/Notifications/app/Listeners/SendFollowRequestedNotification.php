<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Models\User;
use Modules\Notifications\Actions\CreateNotification;
use Modules\SocialGraph\Events\FollowRequested;

class SendFollowRequestedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(FollowRequested $event): void
    {
        $follower = User::find($event->followerId);

        if (! $follower) {
            return;
        }

        ($this->createNotification)(
            user: $event->followingId,
            type: 'follow_requested',
            notifiable: $follower,
            actor: $follower,
            data: [
                'actor_name' => $follower->name,
                'follower_id' => $follower->id,
                'follow_id' => $event->followId,
            ]
        );
    }
}
