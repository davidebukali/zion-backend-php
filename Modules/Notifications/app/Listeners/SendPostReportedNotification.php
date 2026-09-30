<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Interactions\Events\PostReported;
use Modules\Notifications\Actions\CreateNotification;

class SendPostReportedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PostReported $event): void
    {
        $post = $event->post;
        $reporter = $event->user;

        $reason = $event->reason instanceof \BackedEnum
            ? $event->reason->value
            : (string) $event->reason;

        ($this->createNotification)(
            user: $post->user_id, // post author is notified
            type: 'post_reported',
            notifiable: $post,
            actor: $reporter,
            data: [
                'actor_name' => $reporter->name,
                'post_id' => $post->id,
                'reason' => $reason,
                'description' => $event->description,
            ]
        );
    }
}
