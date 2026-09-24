<?php

namespace Modules\Comments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Modules\Comments\Models\Comment;
use Modules\Comments\Actions\AttachMediaToComment;

class CreateComment
{
    public function __construct(
        private readonly AttachMediaToComment $attachMediaToComment
    ) {
    }

    /**
     * Create a new comment for a post by a user.
     */
    public function __invoke(User $user, Post $post, array $data): Comment
    {
        return DB::transaction(function () use ($user, $post, $data) {
            $mediaIds = $data['media_ids'] ?? [];

            unset($data['media_ids']);

            $comment = Comment::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'parent_comment_id' => $data['parent_comment_id'] ?? null,
                'content' => $data['content'],
            ]);

            $post->increment('comments_count');

            $this->attachMediaToComment->handle(
                comment: $comment,
                mediaIds: $mediaIds,
                userId: $user->id
            );

            return $comment->load('media');
        });
    }
}
