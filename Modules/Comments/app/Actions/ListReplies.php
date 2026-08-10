<?php

namespace Modules\Comments\Actions;

use Modules\Comments\Models\Comment;
use Illuminate\Pagination\CursorPaginator;

class ListReplies
{
    /**
     * Retrieve a cursor-paginated list of replies for a comment.
     */
    public function __invoke(Comment $comment, int $perPage = 15): CursorPaginator
    {
        return $comment->replies()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }
}
