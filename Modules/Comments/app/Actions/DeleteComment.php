<?php

namespace Modules\Comments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Comments\Models\Comment;

class DeleteComment
{
    /**
     * Delete a comment.
     */
    public function __invoke(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $comment->delete();

            if ($comment->parent_comment_id === null) {
                $comment->post->decrement('comments_count');
            } else {
                $comment->parent->decrement('replies_count');
            }
        });
    }
}
