<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Interactions\Events\PostReposted;
use Modules\Notifications\Actions\CreateNotification;

class SendPostRepostedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(PostReposted $event): void
    {
        $post = $event->post;
        $actor = $event->user;

        ($this->createNotification)(
            user: $post->user_id,
            type: 'post_reposted',
            notifiable: $post,
            actor: $actor,
            data: [
                'actor_name' => $actor->name,
                'post_id' => $post->id,
            ]
        );
    }
}

