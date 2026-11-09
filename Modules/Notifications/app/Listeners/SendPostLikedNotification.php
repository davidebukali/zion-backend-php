<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Interactions\Events\PostLiked;
use Modules\Interactions\Events\PostUnliked;
use Modules\Notifications\Actions\UpdateGroupedPostLikesNotification;

class SendPostLikedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected UpdateGroupedPostLikesNotification $action
    ) {}

    public function handle(PostLiked|PostUnliked $event): void
    {
        ($this->action)($event->post);
    }
}
