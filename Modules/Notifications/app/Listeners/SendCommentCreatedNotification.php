<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Comments\Events\CommentCreated;
use Modules\Notifications\Actions\CreateNotification;

class SendCommentCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;

        // Skip reply comments if we only notify on top-level post comments
        if ($comment->parent_comment_id !== null) {
            return;
        }

        $post = $comment->post ?? $comment->post()->first();
        $actor = $comment->user ?? $comment->user()->first();

        if (! $post || ! $actor) {
            return;
        }

        ($this->createNotification)(
            user: $post->user_id,
            type: 'post_commented',
            notifiable: $comment,
            actor: $actor,
            data: [
                'actor_name' => $actor->name,
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
                'snippet' => str($comment->content)->limit(100)->toString(),
            ]
        );
    }
}
