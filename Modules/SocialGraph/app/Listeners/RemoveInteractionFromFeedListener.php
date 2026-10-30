<?php

namespace Modules\SocialGraph\Listeners;

use Modules\Interactions\Events\PostUnliked;
use Modules\Interactions\Events\PostUnreposted;
use Modules\SocialGraph\Models\FeedItem;

class RemoveInteractionFromFeedListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof PostUnreposted) {
            FeedItem::where('post_id', $event->post->id)
                ->where('type', 'repost')
                ->where('actor_id', $event->user->id)
                ->delete();
        } elseif ($event instanceof PostUnliked) {
            FeedItem::where('post_id', $event->post->id)
                ->where('type', 'like')
                ->where('actor_id', $event->user->id)
                ->delete();
        }
    }
}

