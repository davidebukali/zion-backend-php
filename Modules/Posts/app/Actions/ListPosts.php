<?php

namespace Modules\Posts\Actions;

use Modules\Posts\Models\Post;
use Illuminate\Pagination\CursorPaginator;

class ListPosts
{
    /**
     * Retrieve a cursor-paginated list of posts for the feed.
     */
    public function __invoke(int $perPage = 15): CursorPaginator
    {
        return Post::feed()->cursorPaginate($perPage);
    }
}
