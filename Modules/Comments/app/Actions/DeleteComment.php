<?php

namespace Modules\Comments\Actions;

use Modules\Comments\Models\Comment;

class DeleteComment
{
    /**
     * Delete a comment.
     */
    public function __invoke(Comment $comment): void
    {
        $comment->delete();
    }
}
