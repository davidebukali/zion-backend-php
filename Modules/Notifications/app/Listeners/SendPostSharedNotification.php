<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Interactions\Events\PostShared;
use Modules\Notifications\Actions\CreateNotification;

class SendPostSharedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(PostShared $event): void
    {
        $post = $event->post;
        $actor = $event->user;

        ($this->createNotification)(
            user: $post->user_id,
            type: 'post_shared',
            notifiable: $post,
            actor: $actor,
            data: [
                'actor_name' => $actor->name,
                'post_id' => $post->id,
                'share_type' => $event->type->value ?? (string) $event->type,
            ]
        );
    }
}
