<?php

namespace Modules\Comments\Actions;

use Modules\Posts\Models\Post;
use Modules\Comments\Models\Comment;
use Illuminate\Pagination\CursorPaginator;

class ListComments
{
    /**
     * Retrieve a cursor-paginated list of comments for a post.
     */
    public function __invoke(Post $post, int $perPage = 15): CursorPaginator
    {
        return $post->comments()
            ->withTrashed()
            ->with('user')
            ->whereNull('parent_comment_id')
            ->where(function ($query) {
                $query->whereNull('deleted_at')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('deleted_at')
                            ->where('replies_count', '>', 0);
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
