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
            ->withTrashed()
            ->with('user')
            ->where(function ($query) {
                $query->whereNull('deleted_at')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('deleted_at')
                            ->where('replies_count', '>', 0);
                    });
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }
}
