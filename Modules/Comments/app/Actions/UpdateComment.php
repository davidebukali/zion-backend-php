<?php

namespace Modules\Comments\Actions;

use Modules\Comments\Models\Comment;

class UpdateComment
{
    /**
     * Update an existing comment.
     */
    public function __invoke(Comment $comment, array $data): Comment
    {
        $comment->update([
            'content' => $data['content'],
        ]);

        return $comment;
    }
}
