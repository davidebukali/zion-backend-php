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
            ->with('user')
            ->whereNull('parent_comment_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
