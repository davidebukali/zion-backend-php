<?php

namespace Modules\SocialGraph\Listeners;

use Modules\Interactions\Events\PostReposted;
use Modules\Posts\Events\PostCreated;
use Modules\SocialGraph\Jobs\FanOutPostJob;

class FanOutPostListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof PostCreated) {
            FanOutPostJob::dispatch(
                postId: $event->post->id,
                type: 'post',
                actorId: $event->post->user_id,
                publishedAt: $event->post->created_at?->toDateTimeString()
            );
        } elseif ($event instanceof PostReposted) {
            FanOutPostJob::dispatch(
                postId: $event->post->id,
                type: 'repost',
                actorId: $event->user->id,
                publishedAt: now()->toDateTimeString()
            );
        }
    }
}
