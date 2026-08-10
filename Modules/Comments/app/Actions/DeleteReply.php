<?php

namespace Modules\Comments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Comments\Models\Comment;

class DeleteReply
{
    /**
     * Delete a reply comment.
     */
    public function __invoke(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $comment->parent->decrement('replies_count');

            $comment->post->decrement('comments_count');

            $comment->delete();
        });
    }
}
