<?php

namespace Modules\SocialGraph\Listeners;

use Modules\Posts\Events\PostCreated;
use Modules\SocialGraph\Jobs\FanOutPostJob;

class FanOutPostListener
{
    /**
     * Handle the event.
     */
    public function handle(PostCreated $event): void
    {
        FanOutPostJob::dispatch($event->post->id);
    }
}
