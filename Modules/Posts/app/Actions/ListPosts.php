<?php

namespace Modules\Posts\Actions;

use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class ListPosts
{
    /**
     * Retrieve a cursor-paginated list of posts for the feed.
     */
    public function __invoke(int $perPage = 15, ?User $viewer = null): CursorPaginator
    {
        return Post::feed()
            ->with('media')
            ->withViewerState($viewer)
            ->cursorPaginate($perPage)
            ->withQueryString();
    }
}
