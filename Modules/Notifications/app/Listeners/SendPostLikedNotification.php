<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Interactions\Events\PostLiked;
use Modules\Notifications\Actions\CreateNotification;

class SendPostLikedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(PostLiked $event): void
    {
        $post = $event->post;
        $actor = $event->user;

        ($this->createNotification)(
            user: $post->user_id, // post author gets notified
            type: 'post_liked',
            notifiable: $post,
            actor: $actor,
            data: [
                'actor_name' => $actor->name,
                'post_id' => $post->id,
            ]
        );
    }
}
