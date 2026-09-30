<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Interactions\Events\CommentLiked;
use Modules\Notifications\Actions\CreateNotification;

class SendCommentLikedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CommentLiked $commentLiked): void
    {
        $comment = $commentLiked->comment;
        $actor = $commentLiked->user;

        ($this->createNotification)(
            user: $comment->user_id,
            type: 'comment_liked',
            notifiable: $comment,
            actor: $actor,
            data: [
                'actor_name' => $actor->name,
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
            ]
        );
    }
}
