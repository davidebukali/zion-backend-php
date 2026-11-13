<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Comments\Events\CommentCreated;
use Modules\Notifications\Actions\CreateNotification;
use Modules\Posts\Events\PostCreated;

class SendUserTaggedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function __construct(
        protected CreateNotification $createNotification
    ) {}

    public function handle(PostCreated|CommentCreated $event): void
    {
        $taggedUserIds = $event->taggedUserIds ?? [];

        if (empty($taggedUserIds)) {
            return;
        }

        if ($event instanceof PostCreated) {
            $post = $event->post;
            $actor = $post->user ?? $post->user()->first();
            $notifiable = $post;
            $postId = $post->id;
            $commentId = null;
            $content = $post->content ?? '';
            $details = 'tagged you in a post';
        } else {
            $comment = $event->comment;
            $actor = $comment->user ?? $comment->user()->first();
            $notifiable = $comment;
            $postId = $comment->post_id;
            $commentId = $comment->id;
            $content = $comment->content ?? '';
            $details = $comment->parent_comment_id !== null ? 'tagged you in a reply' : 'tagged you in a comment';
        }

        if (! $actor) {
            return;
        }

        $actor->loadMissing(['profile.avatar']);
        $avatarUrl = $actor->profile?->avatar?->url ?? $actor->profile?->avatar?->path;

        foreach (array_unique($taggedUserIds) as $taggedId) {
            // Do not notify author about tagging themselves
            if ($taggedId === $actor->id) {
                continue;
            }

            // Silently suppress notification if there is an active block relationship
            if ($actor->hasBlockRelationshipWith($taggedId)) {
                continue;
            }

            ($this->createNotification)(
                user: $taggedId,
                type: 'user_tagged',
                notifiable: $notifiable,
                actor: $actor,
                data: [
                    'actor_name' => $actor->name,
                    'actor_avatar_url' => $avatarUrl,
                    'post_id' => $postId,
                    'comment_id' => $commentId,
                    'details' => $details,
                    'snippet' => str($content)->limit(100)->toString(),
                ]
            );
        }
    }
}
