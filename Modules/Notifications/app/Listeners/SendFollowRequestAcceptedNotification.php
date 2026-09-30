<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Models\User;
use Modules\Notifications\Actions\CreateNotification;
use Modules\SocialGraph\Events\FollowRequestAccepted;

class SendFollowRequestAcceptedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(FollowRequestAccepted $event): void
    {
        $approver = User::find($event->followingId);

        if (! $approver) {
            return;
        }

        ($this->createNotification)(
            user: $event->followerId,
            type: 'follow_request_accepted',
            notifiable: $approver,
            actor: $approver,
            data: [
                'actor_name' => $approver->name,
                'following_id' => $approver->id,
            ]
        );
    }
}
