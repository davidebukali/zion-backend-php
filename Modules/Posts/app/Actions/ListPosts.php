<?php

namespace Modules\Posts\Actions;

use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Illuminate\Pagination\CursorPaginator;

class ListPosts
{
    /**
     * Retrieve a cursor-paginated list of posts for the feed.
     */
    public function __invoke(int $perPage = 15, ?User $viewer = null): CursorPaginator
    {
        $query = Post::feed()->with('media');

        if ($viewer) {
            $query->withExists([
                'likes as is_liked' => fn ($q) => $q->where('user_id', $viewer->id),
            ]);
        }

        return $query->cursorPaginate($perPage)->withQueryString();
    }
}
